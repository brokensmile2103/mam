<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lớp / nhóm theo độ tuổi. */
class Classroom extends Model
{
    protected $fillable = ['name', 'age_label', 'capacity', 'note', 'sort_order'];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function children()
    {
        return $this->hasMany(Child::class);
    }

    public function activeChildren()
    {
        return $this->hasMany(Child::class)->where('status', 'active');
    }
}
