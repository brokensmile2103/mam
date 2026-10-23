<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function categoryLabel(): string
    {
        return config('mam.expense_categories.'.$this->category, 'Khác');
    }
}
