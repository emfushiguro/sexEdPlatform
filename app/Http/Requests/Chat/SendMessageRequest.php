<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxAttachmentKb = (int) config('chat.max_attachment_kb', 10240);
        $maxAttachmentsPerMessage = (int) config('chat.max_attachments_per_message', 5);

        return [
            'message_body' => ['nullable', 'string', 'max:5000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:'.$maxAttachmentsPerMessage, 'required_without:message_body'],
            'attachments.*' => [
                'file',
                'max:'.$maxAttachmentKb,
                'mimes:jpg,jpeg,png,webp,gif,pdf,txt,csv,doc,docx,xls,xlsx,mp3,wav,ogg,m4a,mp4,webm,mov',
            ],
            'retry_of' => ['nullable', 'string', 'max:100'],
        ];
    }
}
