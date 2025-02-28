<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AddressBook extends Model
{
    use HasFactory;

    protected $table = 'address_books';

    protected $fillable = [
        'user_id', 'name', 'email',
         'is_primary', 'full_address', 'phone_number', 'address_type'
    ];

    protected $casts = ['is_primary' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
