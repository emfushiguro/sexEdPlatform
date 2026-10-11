<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TestimonialAvatarController extends Controller
{
    public function show(Request $request, Testimonial $testimonial): BinaryFileResponse
    {
        $isAdmin = $request->user()?->hasRole('admin') || $request->user()?->role === 'admin';
        if (! $isAdmin) {
            abort_unless(Testimonial::query()->publiclyVisible()->whereKey($testimonial->getKey())->exists(), 404);
        }

        return $this->serve($testimonial, ! $isAdmin);
    }

    public function adminShow(Testimonial $testimonial): BinaryFileResponse
    {
        return $this->serve($testimonial, false);
    }

    private function serve(Testimonial $testimonial, bool $public): BinaryFileResponse
    {
        abort_unless($testimonial->show_profile_image === true, 404);
        $testimonial->loadMissing(['user.learnerProfile', 'user.instructorProfile', 'user.profile']);
        $path = $testimonial->user?->learnerProfile?->avatar_path
            ?? $testimonial->user?->instructorProfile?->profile_photo_path
            ?? $testimonial->user?->profile?->avatar;
        $path = trim((string) $path, '/');
        abort_unless($path !== '' && ! Str::contains($path, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        $response = response()->file($disk->path($path), [
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $public ? 'public, max-age=3600' : 'private, no-store',
        ]);
        $response->headers->set('Cache-Control', $public ? 'public, max-age=3600' : 'private, no-store', true);

        return $response;
    }
}
