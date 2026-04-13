<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachParentChildRequest;
use App\Http\Requests\Admin\ChangeUserRoleRequest;
use App\Http\Requests\Admin\DetachParentChildRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\ToggleParentChildVerificationRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use App\Services\Admin\UserRelationshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use InvalidArgumentException;

class UserAdminController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly UserRelationshipService $userRelationshipService,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeUserManagementAccess($request, 'view users');

        $filters = [
            'search' => $request->string('search')->toString(),
            'segment' => $request->string('segment')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->string('status')->toString(),
            'account_type' => $request->string('account_type')->toString(),
            'age_bracket' => $request->string('age_bracket')->toString(),
        ];

        $users = $this->userManagementService->paginateForAdmin($filters, 15)->withQueryString();
        $stats = $this->userManagementService->stats();

        return view('admin.users.index', compact('users', 'stats', 'filters'));
    }

    public function create(Request $request)
    {
        $this->authorizeUserManagementAccess($request, 'create users');

        $roles = Role::query()->pluck('name')->all();

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userManagementService->createUser(
            payload: $request->validated(),
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(Request $request, User $user)
    {
        $this->authorizeUserManagementAccess($request, 'view users');

        $user->load([
            'subscription',
            'gamification',
            'moduleEnrollments',
            'quizAttempts',
            'certificates.module',
            'payments',
            'instructorApplications.approvedBy',
            'instructorApplications.reviews.reviewedBy',
            'roleTransitions.approvedBy',
        ]);

        $parentRelationships = $user->parentLinks()->with('parent')->latest()->get();
        $childRelationships = $user->childLinks()->with('child')->latest()->get();
        $linkedParent = optional($parentRelationships->first())->parent;

        $stats = [
            'total_payments' => $user->payments()->sum('amount'),
            'completed_modules' => $user->moduleEnrollments()->whereNotNull('completed_at')->count(),
            'quiz_attempts' => $user->quizAttempts()->count(),
            'certificates' => $user->certificates()->count(),
        ];

        $instructorLineage = $user->instructorApplications;
        $roleTransitions = $user->roleTransitions->sortByDesc('transitioned_at')->values();

        return view('admin.users.show', compact(
            'user',
            'stats',
            'parentRelationships',
            'childRelationships',
            'linkedParent',
            'instructorLineage',
            'roleTransitions',
        ));
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeUserManagementAccess($request, 'edit users');

        $roles = Role::query()->pluck('name')->all();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->userManagementService->updateUser(
                user: $user,
                payload: $request->validated(),
                actorId: (int) $request->user()->id,
                request: $request,
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['role_change_reason' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $this->userManagementService->updateStatus(
            user: $user,
            status: (string) $request->string('status'),
            reason: $request->filled('reason') ? (string) $request->string('reason') : null,
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User status updated successfully.');
    }

    public function changeRole(ChangeUserRoleRequest $request, User $user)
    {
        $this->userManagementService->changeRole(
            user: $user,
            newRole: (string) $request->string('role'),
            reason: (string) $request->string('reason'),
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User role changed successfully.');
    }

    public function attachParentChild(AttachParentChildRequest $request)
    {
        try {
            $this->userRelationshipService->attachParentChild(
                payload: $request->validated(),
                actorId: (int) $request->user()->id,
                request: $request,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['relationship' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Parent-child relationship attached successfully.');
    }

    public function detachParentChild(DetachParentChildRequest $request)
    {
        try {
            $this->userRelationshipService->detachParentChild(
                parentId: (int) $request->integer('parent_user_id'),
                childId: (int) $request->integer('child_user_id'),
                actorId: (int) $request->user()->id,
                request: $request,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['relationship' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Parent-child relationship detached successfully.');
    }

    public function toggleParentChildVerification(ToggleParentChildVerificationRequest $request)
    {
        try {
            $this->userRelationshipService->setRelationshipVerification(
                parentId: (int) $request->integer('parent_user_id'),
                childId: (int) $request->integer('child_user_id'),
                isVerified: (bool) $request->boolean('is_verified'),
                actorId: (int) $request->user()->id,
                request: $request,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['relationship' => $exception->getMessage()])->withInput();
        }

        return back()->with('success', 'Relationship verification updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account!');
        }

        $this->userManagementService->deleteUser(
            user: $user,
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    private function authorizeUserManagementAccess(Request $request, string $permission): void
    {
        $user = $request->user();

        // Admin routes are already role-protected, but this fallback prevents false 403s
        // when permission cache/assignment is temporarily out of sync.
        abort_unless(
            (bool) $user?->hasRole('admin') || (bool) $user?->can($permission),
            403
        );
    }
}
