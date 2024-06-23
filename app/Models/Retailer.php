<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retailer extends Model
{
    use HasFactory;

        protected $table = 'retailers';
        protected $fillable = ['product_id', 'user_id', 'category_id'];

        // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define the relationship to the Product model
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Define the relationship to the Category model
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
