<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreConnectorRequest;
use App\Models\Connector;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationWithdrawnNotification;
use App\Services\Connectors\ConnectorRegistrationService;
use App\Support\ConnectorOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        $cities = DB::table(config('psgc.tables.cities', 'cities'))
            ->where(function ($query): void {
                $query->where('province_code', ConnectorOptions::CAVITE_PROVINCE_CODE)
                    ->orWhere('code', 'like', ConnectorOptions::CAVITE_CITY_CODE_PREFIX.'%');
            })
            ->orderBy('name')
            ->get(['code', 'name']);

        $selectedCity = old('city_code');
        $barangays = $selectedCity
            ? DB::table(config('psgc.tables.barangays', 'barangays'))
                ->where('city_code', $selectedCity)
                ->orderBy('name')
                ->get(['code', 'name', 'city_code'])
            : collect();

        return view('connectors.register', [
            'categories' => ConnectorOptions::categories(),
            'cities' => $cities,
            'barangays' => $barangays,
        ]);
    }

    public function store(StoreConnectorRequest $request, ConnectorRegistrationService $service): RedirectResponse
    {
        $connector = $service->register($request->user(), $request->validated());

        return redirect()->route('connector.status', $connector)
            ->with('success', 'Connector registration submitted for review.');
    }

    public function status(Request $request, Connector $connector): View
    {
        abort_unless(
            $connector->created_by === $request->user()->id
                || $connector->primary_representative_user_id === $request->user()->id
                || $connector->memberships()->where('user_id', $request->user()->id)->exists(),
            403
        );

        return view('connectors.status', [
            'connector' => $connector->load(['memberships.role', 'reviews.reviewer']),
        ]);
    }

    public function withdraw(Request $request, Connector $connector): RedirectResponse
    {
        abort_unless(
            $connector->created_by === $request->user()->id
                || $connector->primary_representative_user_id === $request->user()->id,
            403
        );

        abort_unless($connector->status === 'pending', 403);

        DB::transaction(function () use ($request, $connector): void {
            $from = $connector->status;

            $connector->update([
                'status' => 'withdrawn',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);

            $connector->memberships()
                ->where('status', 'pending')
                ->update(['status' => 'removed', 'removed_at' => now()]);

            $connector->reviews()->create([
                'reviewed_by' => $request->user()->id,
                'from_status' => $from,
                'to_status' => 'withdrawn',
                'reason' => 'Connector application withdrawn by applicant.',
                'reviewed_at' => now(),
            ]);
        });

        $this->adminRecipients()->each(
            fn (User $admin) => $admin->notify(new ConnectorApplicationWithdrawnNotification($connector->fresh()))
        );

        return redirect()->route('connector.status', $connector)
            ->with('success', 'Connector application withdrawn.');
    }

    private function adminRecipients()
    {
        return User::query()
            ->where('role', 'admin')
            ->orWhereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();
    }
}
