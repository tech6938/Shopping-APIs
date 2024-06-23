<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = [
        'title',
        'price',
        'units',
        'color',
        'storage',
        'screenSize',
        'screenResolution',
        'camera',
        'cameraLens',
        'Ram',
        'processor',
        'battery',
        'charging',
        'productID',
        'image',
        'category_id',
    ];
    public function retailers()
    {
        return $this->belongsToMany(User::class, 'retailers');
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}

