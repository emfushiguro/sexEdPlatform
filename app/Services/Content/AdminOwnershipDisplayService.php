<?php

namespace App\Services\Content;

use App\Models\Module;

class AdminOwnershipDisplayService
{
    /**
     * @return array<string, mixed>
     */
    public function forModule(Module $module): array
    {
        $creator = $module->creator;
        $ownerType = in_array($module->content_owner_type, ['admin', 'instructor'], true)
            ? $module->content_owner_type
            : ((string) optional($creator)->role === 'admin' ? 'admin' : 'instructor');

        if ($ownerType === 'admin') {
            $profile = $creator?->adminCreatorProfile;
            $individualName = $profile?->public_display_name ?: ($creator?->full_name ?: $creator?->name ?: 'Platform Developer');
            $showIndividual = (bool) ($profile?->show_individual_attribution ?? false);

            return [
                'owner_type' => 'admin',
                'display_owner_name' => 'Conscious Connections Team',
                'show_individual_attribution' => $showIndividual,
                'individual_display_name' => $individualName,
                'individual_attribution_text' => $showIndividual ? ('by ' . $individualName) : null,
            ];
        }

        return [
            'owner_type' => 'instructor',
            'display_owner_name' => $creator?->full_name ?: $creator?->name ?: 'Instructor',
            'show_individual_attribution' => false,
            'individual_display_name' => null,
            'individual_attribution_text' => null,
        ];
    }
}
