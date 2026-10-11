<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestimonialRequest;
use App\Models\Testimonial;
use App\Services\Support\TestimonialEligibility;
use App\Services\Support\TestimonialPublicationService;
use App\Services\Support\TestimonialSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly TestimonialEligibility $eligibility,
        private readonly TestimonialSubmissionService $submission,
        private readonly TestimonialPublicationService $publication,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Testimonial::class);

        return view('testimonials.index', [
            'testimonials' => Testimonial::query()->where('user_id', $request->user()->id)->latest()->paginate(15),
            'eligible' => $this->eligibility->canSubmit($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Testimonial::class);

        return view('testimonials.create', [
            'submissionToken' => old('submission_token', (string) Str::uuid()),
            'publicDisplayName' => $this->submission->publicDisplayName($request->user()),
        ]);
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        $this->authorize('create', Testimonial::class);
        $testimonial = $this->submission->submit($request->user(), $request->validated());

        return redirect()->route('testimonials.show', $testimonial)->with('success', 'Your testimonial was submitted for review.');
    }

    public function show(Request $request, Testimonial $testimonial): View
    {
        $this->authorize('view', $testimonial);

        return view('testimonials.show', compact('testimonial'));
    }

    public function withdraw(Request $request, Testimonial $testimonial): RedirectResponse
    {
        abort_if($request->user()->hasRole('admin'), 403);
        $this->authorize('withdraw', $testimonial);
        $this->publication->withdraw($testimonial, true);

        return back()->with('success', 'Your testimonial was withdrawn and is no longer public.');
    }
}
