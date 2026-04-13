<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    public const TYPE_DIRECT = 'direct';
    public const TYPE_ADMIN_SUPPORT = 'admin_support_chat';
    public const TYPE_MODULE_CHAT = 'module_chat';
    public const TYPE_LESSON_CHAT = 'lesson_chat';
    public const TYPE_LESSON_TOPIC_CHAT = 'lesson_topic_chat';
    public const TYPE_QUIZ_HELP = 'quiz_help';

    public const STATUS_PENDING_REQUEST = 'pending_request';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'pair_key',
        'conversation_type',
        'status',
        'module_id',
        'lesson_id',
        'lesson_topic_id',
        'quiz_id',
        'context_key',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public static function makePairKey(int $firstUserId, int $secondUserId): string
    {
        $ordered = [$firstUserId, $secondUserId];
        sort($ordered, SORT_NUMERIC);

        return $ordered[0].':'.$ordered[1];
    }

    public static function makeContextKey(string $conversationType, ?int $contextId): string
    {
        if ($conversationType === self::TYPE_DIRECT) {
            return self::TYPE_DIRECT;
        }

        return $conversationType.':'.(string) ($contextId ?? 0);
    }

    public static function supportedConversationTypes(): array
    {
        return [
            self::TYPE_DIRECT,
            self::TYPE_ADMIN_SUPPORT,
            self::TYPE_MODULE_CHAT,
            self::TYPE_LESSON_CHAT,
            self::TYPE_LESSON_TOPIC_CHAT,
            self::TYPE_QUIZ_HELP,
        ];
    }

    public static function isSupportedConversationType(string $conversationType): bool
    {
        return in_array($conversationType, self::supportedConversationTypes(), true);
    }

    public function participantOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_one_id');
    }

    public function participantTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_two_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function lessonTopic(): BelongsTo
    {
        return $this->belongsTo(LessonTopic::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class);
    }
}
