<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTestimonialRequest;
use App\Http\Requests\Admin\UpdateTestimonialRequest;
use App\Models\Testimonial;
use App\Services\Support\TestimonialPublicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(IndexTestimonialRequest $request, TestimonialPublicationService $publication): View
    {
        $filters = $request->validated();
        $testimonials = Testimonial::query()
            ->with(['user.learnerProfile', 'user.instructorProfile', 'user.profile'])
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['role']), fn ($query) => $query->whereHas('user', fn ($user) => $user->where('role', $filters['role'])))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when(! empty($filters['search']), function ($query) use ($filters): void {
                $search = '%'.addcslashes(substr(trim((string) $filters['search']), 0, 100), '%_\\').'%';
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('display_name', 'like', $search)
                        ->orWhere('quotation', 'like', $search)
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $search)->orWhere('email', 'like', $search));
                });
            })
            ->latest()
            ->paginate(25);
        $publicationEligibility = $testimonials->getCollection()
            ->mapWithKeys(fn (Testimonial $testimonial): array => [
                $testimonial->id => $publication->eligibility($testimonial),
            ]);

        return view('admin.testimonials.index', compact('testimonials', 'publicationEligibility', 'filters'));
    }

    public function publish(Request $request, Testimonial $testimonial, TestimonialPublicationService $publication): RedirectResponse
    {
        $this->authorize('publish', $testimonial);
        $testimonial->load('user');
        $eligibility = $publication->eligibility($testimonial);

        if (! $eligibility['eligible']) {
            return back()->with('error', $eligibility['message']);
        }

        $publication->publish($testimonial, $request->user());

        return back()->with('success', 'Testimonial published.');
    }

    public function withdraw(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('withdraw', $testimonial);
        app(TestimonialPublicationService::class)->withdraw($testimonial);

        return back()->with('success', 'Testimonial withdrawn.');
    }

    public function edit(Testimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);

        return view('admin.testimonials.edit', ['testimonial' => $testimonial]);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);
        $testimonial->update($request->validated());

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated.');
    }

    public function order(Request $request): RedirectResponse
    {
        foreach ((array) $request->input('items', []) as $item) {
            Testimonial::query()->whereKey((int) ($item['id'] ?? 0))->update(['sort_order' => (int) ($item['sort_order'] ?? 0)]);
        }

        return back()->with('success', 'Testimonials reordered.');
    }

    public function reject(Request $request, Testimonial $testimonial, TestimonialPublicationService $publication): RedirectResponse
    {
        $this->authorize('reject', $testimonial);
        $publication->reject($testimonial, $request->user());

        return back()->with('success', 'Testimonial rejected.');
    }

    public function preview(Testimonial $testimonial, TestimonialPublicationService $publication): View
    {
        $this->authorize('view', $testimonial);
        $testimonial->load(['user.learnerProfile', 'user.instructorProfile', 'user.profile']);
        $publicationEligibility = $publication->eligibility($testimonial);

        return view('admin.testimonials.preview', compact('testimonial', 'publicationEligibility'));
    }
}
