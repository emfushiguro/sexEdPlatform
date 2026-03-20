<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = substr(strip_tags($request->input('q', '')), 0, 100);

        if (strlen($q) < 2) {
            return response()->json(['modules' => []]);
        }

        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;

        if (!$learnerProfile) {
            return response()->json(['modules' => []]);
        }

        $learnerAge = $learnerProfile->getAge();

        $modules = Module::published()
            ->forAge($learnerAge)
            ->where('title', 'like', "%{$q}%")
            ->limit(6)
            ->get(['id', 'title', 'is_premium'])
            ->map(fn($m) => [
                'id'         => $m->id,
                'title'      => $m->title,
                'is_premium' => (bool) $m->is_premium,
                'url'        => route('learner.modules.show', $m),
            ]);

        return response()->json(compact('modules'));
    }
}
