<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignCategory extends Model
{
    use HasFactory;

    protected $table = 'assigncategories';
    protected $fillable = ['user_id', 'category_id'];

    // Define the relationship to the Category model
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
