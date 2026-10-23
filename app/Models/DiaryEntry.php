<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 1 dòng trong sổ nhật ký của bé (ăn, ngủ, vệ sinh, thuốc, nhiệt độ, cân đo, sự cố...). */
class DiaryEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'temperature' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:1',
            'item_quantity' => 'decimal:3',
            'cost' => 'decimal:2',
            'amount_ml' => 'integer',
            'sleep_minutes' => 'integer',
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

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id')->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function meta(): array
    {
        return config('mam.entry_types.'.$this->type, config('mam.entry_types.khac'));
    }

    public function isFever(): bool
    {
        return $this->temperature !== null && (float) $this->temperature >= (float) config('mam.fever_threshold', 37.5);
    }

    public function isAlert(): bool
    {
        return $this->type === 'su_co' || $this->isFever() || ($this->type === 'an' && $this->level === 'khong') || ($this->type === 've_sinh' && $this->level === 'long');
    }

    /** Mô tả ngắn gọn 1 dòng (dùng cho timeline, báo cáo ngày, trang phụ huynh). */
    public function summary(bool $withItem = true): string
    {
        $parts = [];

        switch ($this->type) {
            case 'an':
                if ($this->meal) {
                    $parts[] = config('mam.meals.'.$this->meal, $this->meal);
                }
                if ($this->level) {
                    $parts[] = mb_strtolower(config('mam.eat_levels.'.$this->level.'.label', $this->level));
                }
                break;
            case 'sua':
                if ($this->amount_ml) {
                    $parts[] = $this->amount_ml.' ml';
                }
                break;
            case 'ngu':
                if ($this->sleep_from && $this->sleep_to) {
                    $parts[] = substr($this->sleep_from, 0, 5).'–'.substr($this->sleep_to, 0, 5);
                }
                if ($this->sleep_minutes) {
                    $parts[] = duration_label($this->sleep_minutes);
                }
                break;
            case 've_sinh':
                if ($this->level) {
                    $parts[] = config('mam.toilet_kinds.'.$this->level, $this->level);
                }
                break;
            case 'suc_khoe':
                if ($this->temperature !== null) {
                    $parts[] = quantity($this->temperature, 1).'°C'.($this->isFever() ? ' (sốt)' : '');
                }
                break;
            case 'can_do':
                if ($this->weight_kg !== null) {
                    $parts[] = quantity($this->weight_kg, 2).' kg';
                }
                if ($this->height_cm !== null) {
                    $parts[] = quantity($this->height_cm, 1).' cm';
                }
                break;
        }

        if ($withItem && $this->item_id && $this->item) {
            $parts[] = quantity($this->item_quantity).' '.$this->item->unit.' '.$this->item->name;
        }

        return implode(' · ', $parts);
    }
}
