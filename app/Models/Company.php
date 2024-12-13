<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'description', 'email', 'phone', 'country', 'city', 'address', 'agreement'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
