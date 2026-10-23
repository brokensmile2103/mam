<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'category', 'unit', 'low_stock_threshold', 'note'];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'avg_cost' => 'decimal:2',
            'low_stock_threshold' => 'decimal:3',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }

    public function isLowStock(): bool
    {
        return (float) $this->low_stock_threshold > 0 && (float) $this->current_stock <= (float) $this->low_stock_threshold;
    }

    public function categoryLabel(): string
    {
        return config('mam.item_categories.'.$this->category.'.label', 'Khác');
    }
}
