<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    // this is something about user id from User table
    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }
}
