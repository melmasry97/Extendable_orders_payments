<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'total_amount'
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'total_amount' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function canBeDeleted(): bool
    {
        return !$this->payments()->exists();
    }

    public function canProcessPayment(): bool
    {
        return $this->status === OrderStatus::CONFIRMED;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (!isset($order->status)) {
                $order->status = OrderStatus::PENDING;
            }
        });
    }
}
