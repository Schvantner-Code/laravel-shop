<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
