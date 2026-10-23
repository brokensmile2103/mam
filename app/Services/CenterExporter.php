<?php

namespace App\Services;

use App\Models\Center;

/** Xuất toàn bộ dữ liệu của 1 nhóm trẻ ra mảng (JSON) — "dữ liệu của bạn thuộc về bạn". */
class CenterExporter
{
    public function export(Center $center): array
    {
        $take = fn ($relation) => $center->{$relation}()->get()->toArray();

        return [
            'app' => 'Mầm',
            'exported_at' => now()->toIso8601String(),
            'center' => $center->only(['id', 'name', 'address', 'phone', 'tuition_mode', 'tuition_default', 'daily_fee_default', 'meal_rate_default', 'created_at']),
            'classrooms' => $take('classrooms'),
            'children' => $take('children'),
            'attendances' => $take('attendances'),
            'diary_entries' => $take('entries'),
            'menus' => $take('menus'),
            'inventory_items' => $center->items()->withTrashed()->get()->toArray(),
            'stock_movements' => \App\Models\StockMovement::query()->where('center_id', $center->id)->get()->toArray(),
            'invoices' => $take('invoices'),
            'payments' => $take('payments'),
            'expenses' => $take('expenses'),
            'task_templates' => $take('taskTemplates'),
            'tasks' => $take('tasks'),
        ];
    }
}
