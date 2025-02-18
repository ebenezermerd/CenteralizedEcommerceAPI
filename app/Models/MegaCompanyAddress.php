<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MegaCompanyAddress extends Model
{
    protected $fillable = [
        'name',
        'full_address',
        'phone_number',
        'email',
        'is_default',
        'type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
