<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Services\Auth\AdminMfaService;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

trait InteractsWithAdminMfa
{
    public function actingAs(UserContract $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && $user->hasRole('admin')) {
            $this->withSession(app(AdminMfaService::class)->verifiedSessionData($user));
        }

        return $this;
    }

    protected function actingAsWithoutAdminMfa(User $user)
    {
        parent::actingAs($user, 'web');

        return $this->withSession([
            AdminMfaService::VERIFIED_USER_KEY => null,
            AdminMfaService::VERIFIED_AT_KEY => null,
            AdminMfaService::VERIFIED_FINGERPRINT_KEY => null,
        ]);
    }
}
