<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
