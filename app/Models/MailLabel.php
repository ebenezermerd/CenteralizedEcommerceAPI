<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MailLabel extends Model
{
    protected $fillable = [
        'type',
        'name',
        'color',
    ];

    public function mails(): BelongsToMany
    {
        return $this->belongsToMany(Mail::class, 'mail_label_assignments', 'mail_label_id', 'mail_id')
            ->withTimestamps();
    }
}
