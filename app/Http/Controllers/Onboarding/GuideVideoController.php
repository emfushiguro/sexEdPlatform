<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\OnboardingGuideVersion;
use App\Services\Onboarding\OnboardingGuideAudienceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class GuideVideoController extends Controller
{
    private const ALLOWED_MIME_TYPES = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo',
        'video/webm',
    ];

    public function __construct(
        private readonly OnboardingGuideAudienceService $audience,
    ) {}

    public function __invoke(
        Request $request,
        OnboardingGuideVersion $version,
    ): BinaryFileResponse {
        $user = $request->user();
        $isAdminPreview = (bool) $user?->can('manage onboarding guides');

        if (! $isAdminPreview) {
            $this->audience->assertCanUseVersion($user, $version);
        }

        abort_unless($version->video_disk === 'local', 404);
        abort_unless(in_array($version->video_mime_type, self::ALLOWED_MIME_TYPES, true), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($version->video_path), 404);
        $absolutePath = $disk->path($version->video_path);
        abort_unless(is_file($absolutePath), 404);

        $response = response()->file($absolutePath);
        $response->headers->set('Content-Type', $version->video_mime_type);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition('inline', 'onboarding-guide')
        );

        return $response;
    }
}
