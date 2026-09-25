<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\RegistrationTempUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistrationTempDocumentController extends Controller
{
    public function show(Request $request, string $flow, string $step): BinaryFileResponse
    {
        if ($flow === 'parent' && $step === 'government_id') {
            abort_unless($request->user() === null, 403);
        } elseif ($flow === 'child' && $step === 'verification_document') {
            $guardian = $request->user();
            abort_unless(
                $guardian !== null
                    && $guardian->isParentRegistration()
                    && $guardian->isParentVerificationApproved(),
                403
            );
        } else {
            abort(404);
        }

        $metadata = app(RegistrationTempUploadService::class)->get($flow, $step);
        abort_unless(is_array($metadata) && ($metadata['disk'] ?? null) === 'local', 404);

        $path = (string) $metadata['path'];
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $response = response()->file($disk->path($path));
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
