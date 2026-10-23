<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id')->withTrashed();
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
