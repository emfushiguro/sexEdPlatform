<?php

namespace App\Services\Connectors;

use App\Models\Connector;
use App\Models\User;
use App\Notifications\Connectors\ConnectorApplicationSubmittedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConnectorRegistrationService
{
    public function __construct(private readonly ConnectorRoleService $roles)
    {
    }

    public function register(User $user, array $data): Connector
    {
        $connector = DB::transaction(function () use ($user, $data) {
            $connector = Connector::create([
                ...$data,
                'slug' => $this->uniqueSlug($data['name']),
                'status' => 'pending',
                'created_by' => $user->id,
                'primary_representative_user_id' => $user->id,
            ]);

            $ownerRole = $this->roles->createDefaultOwnerRole($connector);

            $connector->memberships()->create([
                'user_id' => $user->id,
                'connector_role_id' => $ownerRole->id,
                'status' => 'pending',
            ]);

            $connector->reviews()->create([
                'from_status' => null,
                'to_status' => 'pending',
                'reason' => 'Connector registration submitted.',
                'reviewed_at' => now(),
            ]);

            return $connector->fresh(['roles.permissions', 'memberships']);
        });

        $user->notify(new ConnectorApplicationSubmittedNotification($connector));
        $this->adminRecipients()->each(
            fn (User $admin) => $admin->notify(new ConnectorApplicationSubmittedNotification($connector, 'admin'))
        );

        return $connector;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'connector';
        $slug = $base;
        $i = 1;

        while (Connector::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function adminRecipients()
    {
        return User::query()
            ->where('role', 'admin')
            ->orWhereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();
    }
}
