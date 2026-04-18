<?php

namespace App\Http\Controllers\Moderation;

use App\Http\Controllers\Controller;
use App\Models\UserSuspension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class SuspensionStatusController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $suspension = UserSuspension::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->with(['enforcementAction', 'moderationCase'])
            ->latest('starts_at')
            ->first();

        if (!$suspension) {
            if ($user->isAdmin() && Route::has('admin.dashboard')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user->isInstructor() && Route::has('instructor.dashboard')) {
                return redirect()->route('instructor.dashboard');
            }

            if (Route::has('learner.dashboard')) {
                return redirect()->route('learner.dashboard');
            }

            return redirect()->route('home');
        }

        return view('moderation.suspension-status', [
            'suspension' => $suspension,
        ]);
    }
}
