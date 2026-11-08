<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\User;
use App\Services\Chat\ConnectorConversationService;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Instructor\PublicInstructorProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EducatorController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorConversationService $conversations,
    ) {}

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_educators');
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($validated['q'] ?? '');
        $instructors = $this->conversations->instructors()->whereKeyNot($request->user()->id)
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->orderBy('id')->paginate(20, ['id', 'name'])->withQueryString();

        return view('connectors.educators.index', compact('connector', 'instructors', 'search'));
    }

    public function store(Request $request, Connector $connector, User $instructor): RedirectResponse
    {
        $conversation = $this->conversations->start($request->user(), $connector, $instructor);

        return redirect()->route('connector.educators.messages', [$connector, 'conversation_id' => $conversation->id]);
    }

    public function show(Request $request, Connector $connector, User $instructor, PublicInstructorProfileService $profiles): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_educators');
        abort_unless($this->conversations->instructors()->whereKeyNot($request->user()->id)->whereKey($instructor->id)->exists(), 404);

        return view('learner.instructors.show', ['connector' => $connector, ...$profiles->data($instructor)]);
    }

    public function messages(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_educators');
        $validated = $request->validate(['conversation_id' => ['nullable', 'integer', 'min:1']]);
        if (isset($validated['conversation_id'])) {
            abort_unless($this->conversations->visibleTo($request->user())->where('connector_id', $connector->id)
                ->whereKey($validated['conversation_id'])->exists(), 404);
        }

        return view('connectors.educators.messages', ['connector' => $connector, 'connectorChat' => true]);
    }
}
