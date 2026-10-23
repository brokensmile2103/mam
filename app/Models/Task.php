<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'item_quantity' => 'decimal:3',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'template_id');
    }

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id')->withTrashed();
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_at->isPast();
    }

    public function meta(): array
    {
        return task_meta($this->type);
    }

    public function targetLabel(): ?string
    {
        return match ($this->target) {
            'child' => $this->child?->displayName(),
            'classroom' => $this->classroom?->name,
            'all' => 'Tất cả các bé',
            default => null,
        };
    }
}
