<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppealThreadMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'suspension_appeal_id',
        'sender_user_id',
        'sender_role',
        'message_body',
        'parent_message_id',
    ];

    public function appeal(): BelongsTo
    {
        return $this->belongsTo(SuspensionAppeal::class, 'suspension_appeal_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    public function childMessages(): HasMany
    {
        return $this->hasMany(self::class, 'parent_message_id');
    }
}
