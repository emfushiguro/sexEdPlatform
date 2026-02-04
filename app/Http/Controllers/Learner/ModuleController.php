<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userProfile = $user->learnerProfile;
        
        // Get age-appropriate modules
        $ageBracket = $userProfile ? $userProfile->age_bracket : 'adults';
        
        $modules = Module::where('is_published', true)
            ->where('age_bracket', $ageBracket)
            ->withCount('lessons')
            ->orderBy('order')
            ->get();
            
        $enrolledModuleIds = $user->moduleEnrollments()->pluck('module_id')->toArray();
        
        return view('learner.modules.index', compact('modules', 'enrolledModuleIds', 'ageBracket'));
    }
    
    public function show(Module $module)
    {
        $user = Auth::user();
        $enrollment = $user->moduleEnrollments()->where('module_id', $module->id)->first();
        
        $module->load(['lessons' => function($query) {
            $query->where('is_published', true)->orderBy('order');
        }]);
        
        return view('learner.modules.show', compact('module', 'enrollment'));
    }
    
    public function enroll(Request $request, Module $module)
    {
        $user = Auth::user();
        
        // Check if already enrolled
        if ($user->moduleEnrollments()->where('module_id', $module->id)->exists()) {
            return redirect()->back()->with('info', 'You are already enrolled in this module.');
        }
        
        // Create enrollment
        ModuleEnrollment::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'enrolled_at' => now(),
            'status' => 'active'
        ]);
        
        return redirect()->route('learner.modules.show', $module)
            ->with('success', 'Successfully enrolled in module!');
    }
}