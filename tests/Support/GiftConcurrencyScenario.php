<?php

// Run only against an explicitly isolated testing database; never bootstrap development fixtures here.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$identity = Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS db_name');
if (! $app->environment('testing') || $identity->db_name !== 'cc_db_test') {
    throw new RuntimeException('Gift concurrency scenario requires testing/cc_db_test.');
}
config(['paymongo.secret_key' => 'sk_test_gift', 'queue.default' => 'sync']);
Illuminate\Support\Facades\Bus::fake();
Illuminate\Support\Facades\Notification::fake();
Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-11-09 10:00:00'));

if (($argv[1] ?? '') === 'worker') {
    $event = json_decode(base64_decode($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    echo "READY\n";
    flush();
    if (in_array($event['operation'] ?? null, ['cancel', 'reconcile', 'expire'], true)) {
        $subscription = App\Models\Subscription::findOrFail($event['subscription_id']);
        try {
            $subscriptions = app(App\Services\SubscriptionService::class);
            if ($event['operation'] === 'cancel') {
                $subscriptions->cancel($subscription, 'Concurrent regular cancellation');
            } elseif ($event['operation'] === 'expire') {
                $subscriptions->expire($subscription);
            } else {
                $subscriptions->reconcileLifecycleState($subscription);
            }
            echo "DONE\n";
        } catch (RuntimeException $exception) {
            echo "BLOCKED\n";
        }
        exit(0);
    }
    if (isset($event['operation'])) {
        // Policy behavior has separate HTTP tests; this scenario isolates purchase serialization.
        Illuminate\Support\Facades\Gate::before(fn ($user, $ability) => $ability === 'giftSubscription' ? true : null);
        try {
            $dependent = App\Models\User::findOrFail($event['dependent_id']);
            $plan = App\Models\SubscriptionPlan::findOrFail($event['plan_id']);
            if ($event['operation'] === 'self') {
                app(App\Services\SubscriptionService::class)->create($dependent, $plan);
            } else {
                app(App\Services\GuardianSubscriptionGiftService::class)->prepare(
                    App\Models\User::findOrFail($event['guardian_id']), $dependent, $plan, $event['request_key']);
            }
            echo "DONE\n";
        } catch (Illuminate\Validation\ValidationException $exception) {
            echo "BLOCKED\n";
        }
        exit(0);
    }
    app(App\Services\GuardianSubscriptionGiftService::class)->completeFromWebhook($event);
    echo "DONE\n";
    exit(0);
}

$guardian = $dependent = $plan = $price = $subscription = $payment = null;
$processes = [];
$preparation = ($argv[1] ?? '') === 'prepare';
$lifecycle = in_array($argv[1] ?? '', ['cancel', 'reconcile', 'expire'], true);
$relationship = null;
try {
    $guardian = App\Models\User::factory()->create(['name' => 'Concurrency payer', 'status' => 'active']);
    $dependent = App\Models\User::factory()->create(['name' => 'Concurrency beneficiary', 'status' => 'active']);
    $plan = App\Models\SubscriptionPlan::create([
        'name' => 'Concurrency plan', 'slug' => 'concurrency-'.Illuminate\Support\Str::uuid(),
        'price' => 129, 'plan_audience' => 'learner', 'features' => [], 'is_active' => true,
    ]);
    $price = App\Models\PlanPrice::create([
        'plan_id' => $plan->id, 'duration_mode' => 'preset', 'duration_unit' => 'month', 'duration_count' => 1,
        'duration_label' => 'One month', 'amount_minor' => 12900, 'currency' => 'PHP', 'is_active' => true, 'is_default' => true,
    ]);
    $subscription = App\Models\Subscription::create([
        'user_id' => $dependent->id, 'plan_id' => $plan->id, 'plan_price_id' => $price->id,
        'plan' => $plan->slug, 'status' => 'pending', 'start_date' => now(), 'starts_at' => now(),
        'end_date' => now()->addMonth(), 'ends_at' => now()->addMonth(), 'price_paid' => 129,
        'auto_renew' => false, 'source_provider' => 'guardian_gift',
    ]);
    $payment = App\Models\Payment::create([
        'user_id' => $guardian->id, 'subscription_id' => $subscription->id, 'amount' => 129,
        'status' => 'pending', 'transaction_id' => 'GIFT-CONCURRENT-'.Illuminate\Support\Str::uuid(),
        'payment_details' => ['paymongo_checkout_session_id' => 'cs_concurrent', 'payment_scope' => 'subscription',
            'gift' => ['plan_id' => $plan->id, 'amount_minor' => 12900, 'currency' => 'PHP', 'livemode' => false,
                'duration_unit' => 'month', 'duration_count' => 1, 'beneficiary_name' => $dependent->name]],
    ]);
    $event = ['id' => 'evt_concurrent', 'attributes' => ['type' => 'checkout_session.payment.paid', 'livemode' => false,
        'data' => ['id' => 'cs_concurrent', 'attributes' => [
            'metadata' => ['payment_id' => $payment->id, 'subscription_id' => $subscription->id, 'user_id' => $guardian->id],
            'payments' => [['id' => 'pay_concurrent', 'attributes' => ['status' => 'paid', 'amount' => 12900, 'currency' => 'PHP']]],
        ]]]];

    if ($preparation) {
        $payment->delete();
        $subscription->delete();
        $relationship = App\Models\ParentChildAccount::create([
            'parent_user_id' => $guardian->id, 'child_user_id' => $dependent->id,
            'relationship_status' => App\Models\ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => App\Models\ParentChildAccount::VERIFICATION_VERIFIED,
            'verification_status' => 'approved', 'relationship_verified_at' => now(),
        ]);
        $event = ['guardian_id' => $guardian->id, 'dependent_id' => $dependent->id,
            'plan_id' => $plan->id, 'request_key' => (string) Illuminate\Support\Str::uuid()];
    }
    if ($lifecycle) {
        $oldEnd = $argv[1] === 'cancel' ? now()->addDays(2) : now()->subMinute();
        $subscription->update([
            'status' => 'active', 'source_provider' => null,
            'end_date' => $oldEnd, 'ends_at' => $oldEnd,
        ]);
        $event['operation'] = $argv[1];
        $event['subscription_id'] = $subscription->id;
    }
    Illuminate\Support\Facades\DB::beginTransaction();
    App\Models\User::query()->whereKey($dependent->id)->lockForUpdate()->firstOrFail();
    $workerCount = $lifecycle ? 1 : 2;
    for ($index = 0; $index < $workerCount; $index++) {
        if ($preparation) {
            $event['operation'] = $index === 0 ? 'self' : 'gift';
        }
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', base64_encode(json_encode($event))],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (! is_resource($process)) {
            throw new RuntimeException('Cannot start concurrency worker.');
        }
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    foreach ($processes as [$process, $pipes]) {
        stream_set_timeout($pipes[1], 20);
        if (trim((string) fgets($pipes[1])) !== 'READY') {
            throw new RuntimeException('Concurrency worker did not reach its ready barrier.');
        }
    }
    $connection = config('database.connections.mysql');
    $monitor = new PDO('mysql:host='.$connection['host'].';port='.$connection['port'].';dbname=cc_db_test',
        $connection['username'], $connection['password']);
    $deadline = microtime(true) + 10;
    do {
        // MariaDB can cache the information_schema lock tables while new waits exist.
        $status = $monitor->query('SHOW ENGINE INNODB STATUS')->fetch(PDO::FETCH_ASSOC);
        $transactions = explode("\nTRANSACTIONS\n", str_replace("\r\n", "\n", $status['Status']), 2)[1] ?? '';
        $transactions = explode("\nFILE I/O\n", $transactions, 2)[0];
        $waiters = preg_match_all('/LOCK WAIT \d+ lock struct/', $transactions);
        if ($waiters >= $workerCount) {
            break;
        }
        usleep(10000);
    } while (microtime(true) < $deadline);
    if ($waiters < $workerCount) {
        Illuminate\Support\Facades\DB::rollBack();
        $errors = [];
        foreach ($processes as [$process, $pipes]) {
            $errors[] = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
        $processes = [];
        throw new RuntimeException('All '.$workerCount.' workers must reach the row-lock barrier; observed '.$waiters.' waiters. '.implode("\n", $errors));
    }
    if ($lifecycle) {
        app(App\Services\GuardianSubscriptionGiftService::class)->completeFromWebhook($event);
        $paidEnd = $subscription->fresh()->ends_at->toIso8601String();
    }
    Illuminate\Support\Facades\DB::commit();
    $results = '';
    foreach ($processes as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        $results .= $output;
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || (! str_contains($output, 'DONE') && ! str_contains($output, 'BLOCKED'))) {
            throw new RuntimeException('Concurrent fulfillment failed: '.$error);
        }
    }
    $processes = [];
    if ($lifecycle) {
        $subscription->refresh();
        if ($subscription->status !== App\Enums\SubscriptionStatus::Active
            || $subscription->ends_at->toIso8601String() !== $paidEnd
            || ! data_get($payment->fresh()->payment_details, 'gift.applied_at')) {
            throw new RuntimeException('Stale regular lifecycle writes must not overwrite a paid gift term.');
        }
        echo "LIFECYCLE OK: regular operation waited for gift fulfillment and preserved paid access.\n";
    } elseif ($preparation) {
        $payments = App\Models\Payment::whereHas('subscription', fn ($query) => $query->where('user_id', $dependent->id))->get();
        if ($payments->count() !== 1 || substr_count($results, 'DONE') !== 1 || substr_count($results, 'BLOCKED') !== 1) {
            throw new RuntimeException('Competing self/gift purchases must reserve one checkout only. '.$results);
        }
        echo "PREPARATION OK: competing gift/self purchase reserved one checkout.\n";
    } else {
        $subscription->refresh();
        if ($subscription->ends_at->format('Y-m-d H:i:s') !== '2026-12-09 10:00:00'
            || ! data_get($payment->fresh()->payment_details, 'gift.applied_at')
            || $guardian->fresh()->isPremium() || ! $dependent->fresh()->isPremium()) {
            throw new RuntimeException('Concurrent confirmation must apply one term only to the dependent.');
        }
        echo "CONCURRENCY OK: two independent row-lock waiters; one paid term applied.\n";
    }
} finally {
    if (Illuminate\Support\Facades\DB::transactionLevel() > 0) {
        Illuminate\Support\Facades\DB::rollBack();
    }
    foreach ($processes as [$process, $pipes]) {
        if (is_resource($process)) {
            proc_terminate($process);
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($process);
        }
    }
    // Only these explicitly created test rows in the verified isolated database are removed.
    if ($dependent) {
        $subscriptionIds = Illuminate\Support\Facades\DB::table('subscribers')->where('user_id', $dependent->id)->pluck('id');
        Illuminate\Support\Facades\DB::table('payments')->whereIn('subscription_id', $subscriptionIds)->delete();
        Illuminate\Support\Facades\DB::table('subscribers')->whereIn('id', $subscriptionIds)->delete();
    }
    if ($relationship) {
        Illuminate\Support\Facades\DB::table('parent_child_accounts')->where('id', $relationship->id)->delete();
    }
    if ($price) {
        Illuminate\Support\Facades\DB::table('plan_prices')->where('id', $price->id)->delete();
    }
    if ($plan) {
        Illuminate\Support\Facades\DB::table('subscription_plans')->where('id', $plan->id)->delete();
    }
    foreach ([$guardian, $dependent] as $user) {
        if ($user) {
            Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->delete();
        }
    }
}
