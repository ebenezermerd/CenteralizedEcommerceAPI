<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mail extends Model
{
    protected $table = 'mails';

    protected $fillable = [
        'from_user_id',
        'folder',
        'subject',
        'message',
        'is_unread',
        'is_starred',
        'is_important',
        'created_at',
    ];

    protected $casts = [
        'is_unread' => 'boolean',
        'is_starred' => 'boolean',
        'is_important' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function from(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mail_recipients', 'mail_id', 'user_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(MailLabel::class, 'mail_label_assignments', 'mail_id', 'mail_label_id')
            ->withTimestamps();
    }

    public function scopeInbox($query)
    {
        return $query->whereHas('labels', function ($q) {
            $q->where('name', 'inbox');
        });
    }

    public function scopeSent($query)
    {
        return $query->whereHas('labels', function ($q) {
            $q->where('name', 'sent');
        });
    }

    public function scopeDrafts($query)
    {
        return $query->whereHas('labels', function ($q) {
            $q->where('name', 'draft');
        });
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MailAttachment::class);
    }
}
