<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['date', 'meal', 'dishes'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }
}
