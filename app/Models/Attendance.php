<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPresent(): bool
    {
        return $this->status === 'co_mat';
    }

    public function meta(): array
    {
        return config('mam.attendance_statuses.'.$this->status, ['label' => $this->status, 'short' => $this->status, 'icon' => 'fa-circle', 'color' => 'neutral']);
    }

    /** "07:30" thay vì "07:30:00". */
    public function checkInLabel(): ?string
    {
        return $this->check_in ? substr($this->check_in, 0, 5) : null;
    }

    public function checkOutLabel(): ?string
    {
        return $this->check_out ? substr($this->check_out, 0, 5) : null;
    }
}
