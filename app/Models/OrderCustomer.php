<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCustomer extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'name', 'email', 'avatar_url', 'ip_address'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
