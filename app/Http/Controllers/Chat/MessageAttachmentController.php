<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageAttachment;
use App\Services\Chat\ChatAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function __invoke(Request $request, MessageAttachment $attachment, ChatAuthorizationService $authorization): StreamedResponse
    {
        $message = $attachment->message;
        abort_unless($message && $message->conversation->conversation_type === Conversation::TYPE_CONNECTOR_NEGOTIATION, 404);
        abort_unless($authorization->canViewConversation($request->user(), $message->conversation), 403);
        abort_if($message->deleted_at !== null || $attachment->disk !== 'local', 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        $filename = basename(str_replace('\\', '/', $attachment->file_name));
        $inline = preg_match('~^(image/(jpeg|png|webp|gif)|audio/|video/)~', (string) $attachment->mime_type) === 1;

        return Storage::disk('local')->response($attachment->path, $filename, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }
}
