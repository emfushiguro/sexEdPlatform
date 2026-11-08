<?php

namespace App\Http\Controllers\InstructorApplication;

use App\Http\Controllers\Controller;
use App\Models\InstructorApplication;
use App\Services\InstructorApplication\InstructorApplicationDocumentStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(private readonly InstructorApplicationDocumentStorage $documents) {}

    public function show(Request $request, InstructorApplication $application, string $slot): Response
    {
        $user = $request->user();
        abort_unless(
            ((int) $application->user_id === (int) $user->id)
                || ($user->isAdmin() && $user->hasPermissionTo('review instructor applications')),
            403,
        );

        $column = $this->documents->columnFor($slot);
        $path = $application->{$column};
        abort_unless(is_string($path) && $path !== '', 404);

        $disk = $this->documents->diskFor($application, $slot);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
        $filename = $this->documents->displayNameFor($application, $slot);

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="'.addcslashes($filename, '"\\').'"',
        ]);
    }
}
