<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Công việc định kỳ (routine) — sinh ra các Task theo lịch. */
class TaskTemplate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'last_generated_on' => 'date',
            'is_active' => 'boolean',
            'item_quantity' => 'decimal:3',
        ];
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
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

    public function tasks()
    {
        return $this->hasMany(Task::class, 'template_id');
    }

    /** @return list<string> các giờ dạng "HH:MM", đã sắp xếp */
    public function timesList(): array
    {
        $times = array_values(array_filter(array_map('trim', explode(',', (string) $this->times))));
        sort($times);

        return $times ?: ['08:00'];
    }

    /** @return list<int> thứ ISO (1=T2..7=CN) */
    public function weekdaysList(): array
    {
        return array_values(array_unique(array_map('intval', array_filter(explode(',', (string) $this->weekdays)))));
    }

    public function meta(): array
    {
        return task_meta($this->type);
    }

    public function targetLabel(): string
    {
        return match ($this->target) {
            'child' => $this->child?->displayName() ?? '(bé đã xoá)',
            'classroom' => $this->classroom?->name ?? '(lớp đã xoá)',
            'all' => 'Tất cả các bé đang học',
            default => 'Việc chung',
        };
    }

    /** Mô tả lịch lặp bằng tiếng Việt, VD: "Mỗi ngày lúc 08:00, 14:00". */
    public function scheduleLabel(): string
    {
        $when = match ($this->frequency) {
            'weekdays' => 'T2–T6',
            'weekly' => 'Hằng tuần vào '.implode(', ', array_map(
                fn ($d) => config('mam.weekday_labels.'.$d, $d), $this->weekdaysList()
            )),
            'monthly' => 'Hằng tháng vào ngày '.($this->month_day ?: 1),
            'interval' => 'Cách '.max(1, (int) $this->interval_days).' ngày',
            default => 'Mỗi ngày',
        };

        return $when.' lúc '.implode(', ', $this->timesList());
    }
}
