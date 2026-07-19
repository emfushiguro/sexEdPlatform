<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Notification\NotificationReadService;
use App\Support\NotificationDeepLinkResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly NotificationReadService $readService,
        private readonly NotificationDeepLinkResolver $deepLinkResolver,
    ) {
    }

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);

        /** @var User $user */
        $user = $request->user();
        $category = (string) $request->query('category', '');
        $search = trim((string) $request->query('search', ''));
        $categoryTypes = [
            'connector' => ['connector_approved', 'connector_rejected', 'connector_suspended'],
            'members' => ['connector_membership%', 'connector_role%'],
            'invitations' => ['connector_invitation%'],
            'seminars' => ['seminar_%'],
            'speakers' => ['seminar_speaker%'],
            'livestream' => ['livestream_%'],
            'platform' => ['subscription_%', 'payment_%', 'platform_%'],
        ];

        $notifications = $this->connectorNotifications($user, $connector)
            ->when(isset($categoryTypes[$category]), function ($query) use ($categoryTypes, $category): void {
                $query->where(function ($query) use ($categoryTypes, $category): void {
                    foreach ($categoryTypes[$category] as $type) {
                        $operator = str_contains($type, '%') ? 'like' : '=';
                        $query->orWhere('data->type', $operator, $type);
                    }
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('data->title', 'like', '%'.$search.'%')
                        ->orWhere('data->message', 'like', '%'.$search.'%');
                });
            })
            ->paginate(20)
            ->withQueryString();
        $connectorNotificationUnreadCount = $this->connectorNotifications($user, $connector)
            ->whereNull('read_at')
            ->count();

        return view('connectors.notifications.index', compact('connector', 'notifications', 'category', 'search', 'connectorNotificationUnreadCount'));
    }

    public function markAllRead(Request $request, Connector $connector)
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);

        /** @var User $user */
        $user = $request->user();
        $updated = $this->connectorNotifications($user, $connector)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'updated' => $updated]);
        }

        return back();
    }

    public function markDropdownRead(Request $request, Connector $connector)
    {
        return $this->markAllRead($request, $connector);
    }

    public function markRead(Request $request, Connector $connector, string $id)
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);

        /** @var User $user */
        $user = $request->user();
        $candidate = $user->notifications()->findOrFail($id);
        abort_unless($this->belongsToConnector((array) $candidate->data, $connector), 404);
        $notification = $this->readService->markOneRead($user, $id);

        $url = $this->deepLinkResolver->resolve(
            (array) $notification->data,
            route('connector.notifications.index', $connector)
        );

        return redirect($url);
    }

    private function connectorNotifications(User $user, Connector $connector)
    {
        $connectorPath = '%/connector/'.$connector->id.'/%';

        return $user->notifications()
            ->where(function ($query) use ($connector, $connectorPath): void {
                $query->where('data->connector_id', $connector->id)
                    ->orWhere('data->action_url', 'like', $connectorPath);
            });
    }

    private function belongsToConnector(array $payload, Connector $connector): bool
    {
        if ((int) ($payload['connector_id'] ?? 0) === $connector->id) {
            return true;
        }

        $actionUrl = $payload['action_url'] ?? null;

        if (! is_string($actionUrl)) {
            return false;
        }

        $path = parse_url($actionUrl, PHP_URL_PATH);

        return is_string($path) && str_contains($path.'/', '/connector/'.$connector->id.'/');
    }
}
