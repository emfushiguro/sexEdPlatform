<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Services\Connectors\ConnectorVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class VerificationDocumentController extends Controller
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly ConnectorVerificationService $verification,
    ) {}

    public function __invoke(
        Request $request,
        Connector $connector,
        ConnectorVerificationDocument $document,
    ): BinaryFileResponse {
        abort_unless((int) $document->connector_id === (int) $connector->id, 404);

        $user = $request->user();
        $isApplicant = $connector->created_by === $user->id
            || $connector->primary_representative_user_id === $user->id;
        $isAdmin = $user->role === 'admin' || $user->hasRole('admin');

        abort_unless($isApplicant || $isAdmin, 403);

        $disposition = $request->query('disposition', 'inline');
        abort_unless(is_string($disposition) && in_array($disposition, ['inline', 'attachment'], true), 400);

        $diskName = (string) config('connector_verification.evidence_disk', 'local');
        abort_unless($document->disk === $diskName, 404);
        abort_unless(in_array($document->mime_type, self::ALLOWED_MIME_TYPES, true), 404);

        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($document->path), 404);

        $absolutePath = $disk->path($document->path);
        abort_unless(is_file($absolutePath), 404);

        $filename = $this->safeFilename($document->original_name);
        $response = response()->file($absolutePath);
        $response->headers->set('Content-Type', $document->mime_type);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition($disposition, $filename),
        );

        $this->verification->recordDocumentAccess(
            $document,
            $user,
            $disposition === 'inline' ? 'document_previewed' : 'document_downloaded',
        );

        return $response;
    }

    private function safeFilename(?string $originalName): string
    {
        $filename = basename((string) $originalName);
        $filename = preg_replace('/[^\pL\pN._-]+/u', '_', $filename) ?: 'verification-evidence';
        $filename = trim($filename, '._ ');

        return $filename !== '' ? $filename : 'verification-evidence';
    }
}
