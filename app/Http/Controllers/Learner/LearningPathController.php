<?php

declare(strict_types=1);

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\LearningPath;
use App\Services\LearningPathPresentationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function __construct(
        private readonly LearningPathPresentationService $presentation,
    ) {}

    public function index(Request $request): View
    {
        $category = $request->user()->learnerProfile->getAgeBracket();
        $paths = LearningPath::query()
            ->published()
            ->forLearnerCategory($category)
            ->with(['learnerCategories', 'pathModules.module'])
            ->latest()
            ->paginate(12);

        return view('learner.learning-paths.index', [
            'paths' => $paths,
            'summaries' => $this->presentation->summariesFor($request->user(), $paths->getCollection()),
        ]);
    }

    public function show(Request $request, int $learningPath): View
    {
        $category = $request->user()->learnerProfile->getAgeBracket();
        $path = LearningPath::query()
            ->published()
            ->forLearnerCategory($category)
            ->findOrFail($learningPath);

        return view('learner.learning-paths.show', [
            'path' => $this->presentation->presentFor($request->user(), $path),
        ]);
    }
}
