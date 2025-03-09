<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailAttachment extends Model
{
    protected $fillable = [
        'name',
        'size',
        'type',
        'path',
        'preview',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'modified_at' => 'datetime',
    ];

    public function mail()
    {
        return $this->belongsTo(Mail::class);
    }
}
