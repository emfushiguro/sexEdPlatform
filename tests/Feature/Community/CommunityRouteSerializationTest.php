<?php

namespace Tests\Feature\Community;

use App\Http\Controllers\Connector\CommunityPinController;
use Tests\TestCase;

class CommunityRouteSerializationTest extends TestCase
{
    public function test_pin_and_unpin_routes_have_unique_names_and_handlers(): void
    {
        $routes = app('router')->getRoutes();

        $pin = $routes->getByName('connector.community.posts.pin');
        $unpin = $routes->getByName('connector.community.posts.unpin');

        $this->assertNotNull($pin);
        $this->assertNotNull($unpin);
        $this->assertSame(CommunityPinController::class.'@store', $pin?->getActionName());
        $this->assertSame(CommunityPinController::class.'@destroy', $unpin?->getActionName());
    }
}
