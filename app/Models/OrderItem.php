<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        // Calculate subtotal before saving
        static::saving(function ($orderItem) {
            $orderItem->subtotal = $orderItem->quantity * $orderItem->unit_price;
        });

        // Update order total after create/update/delete
        static::saved(function ($orderItem) {
            $orderItem->updateOrderTotal();
        });

        static::deleted(function ($orderItem) {
            $orderItem->updateOrderTotal();
        });
    }

    protected function updateOrderTotal(): void
    {
        $this->order->update([
            'total_amount' => $this->order->items()->sum('subtotal')
        ]);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
