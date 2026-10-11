<?php

namespace App\Services\Support;

use App\Models\Connector;

final class SupportRouteContext
{
    public function for(?Connector $connector = null): array
    {
        $parameters = $connector ? ['connector' => $connector] : [];
        $prefix = $connector ? 'connector.' : '';

        return [
            'help' => [
                'index' => ['name' => $prefix.'help.index', 'parameters' => $parameters],
                'show' => ['name' => $prefix.'help.show', 'parameters' => $parameters],
                'section_image' => ['name' => $prefix.'help.section.image', 'parameters' => $parameters],
            ],
            'feedback' => [
                'create' => ['name' => $prefix.'feedback.create', 'parameters' => $parameters],
                'store' => ['name' => $prefix.'feedback.store', 'parameters' => $parameters],
                'index' => ['name' => $prefix.'feedback.index', 'parameters' => $parameters],
                'show' => ['name' => $prefix.'feedback.show', 'parameters' => $parameters],
                'attachment' => ['name' => $prefix.'feedback.attachment.show', 'parameters' => $parameters],
                'message_store' => ['name' => $prefix.'feedback.messages.store', 'parameters' => $parameters],
                'withdraw' => ['name' => $prefix.'feedback.withdraw', 'parameters' => $parameters],
            ],
        ];
    }
}
