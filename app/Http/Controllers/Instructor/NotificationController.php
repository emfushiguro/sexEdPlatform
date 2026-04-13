<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notification\NotificationReadService;
use App\Support\NotificationDeepLinkResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationReadService $readService,
        private readonly NotificationDeepLinkResolver $deepLinkResolver,
    ) {
    }

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(20);

        return view('instructor.notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $updated = $this->readService->markAllRead($user);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'updated' => $updated,
            ]);
        }

        return back();
    }

    public function markDropdownRead(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $updated = $this->readService->markAllReadOnDropdownOpen($user);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'updated' => $updated,
            ]);
        }

        return back();
    }

    public function markRead(string $id)
    {
        /** @var User $user */
        $user = Auth::user();
        $notification = $this->readService->markOneRead($user, $id);

        $url = $this->deepLinkResolver->resolve(
            (array) $notification->data,
            route('instructor.notifications.index')
        );

        return redirect($url);
    }
}
