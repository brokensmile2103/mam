<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Center;
use App\Models\Child;
use App\Models\InventoryItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(private DiaryService $diary) {}

    /**
     * Hoàn thành 1 công việc. Nếu là việc chăm sóc gắn với bé/lớp (cho uống thuốc, cho ăn, đo nhiệt...)
     * thì tự ghi vào sổ nhật ký của các bé ĐANG CÓ MẶT (bỏ qua bé đã điểm danh vắng hôm nay),
     * kèm trừ kho nếu có vật tư. Trả về các cảnh báo (VD: kho âm).
     *
     * @return list<string>
     */
    public function complete(Task $task, ?User $user, ?float $itemQty = null, array $fields = [], ?string $note = null): array
    {
        $warnings = [];

        DB::transaction(function () use ($task, $user, $itemQty, $fields, $note, &$warnings) {
            // Khoá dòng + đọc lại trạng thái: bấm "Xong" 2 lần không được ghi nhật ký / trừ kho 2 lần.
            $locked = Task::query()->whereKey($task->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isPending()) {
                return;
            }

            $center = Center::query()->find($locked->center_id);
            $children = $this->targetChildren($locked);

            // "Cho ngủ" bấm Xong = bắt đầu ngủ lúc này.
            if ($locked->type === 'ngu' && empty($fields['sleep_from'])) {
                $fields['sleep_from'] = now()->format('H:i');
            }

            if ($center && $children->isNotEmpty() && config('mam.entry_types.'.$locked->type) && self::hasRequiredValues($locked->type, $fields)) {
                $item = $locked->item_id ? InventoryItem::query()->where('center_id', $locked->center_id)->find($locked->item_id) : null;
                $qty = $itemQty ?? ($locked->item_quantity !== null ? (float) $locked->item_quantity : null);

                $result = $this->diary->log(
                    $center,
                    $user,
                    $children,
                    $locked->type,
                    now(),
                    $fields,
                    $item,
                    $qty,
                    trim($locked->title.($note ? ' — '.$note : '')),
                );

                $warnings = $result['warnings'];
            }

            $locked->forceFill([
                'status' => 'done',
                'completed_at' => now(),
                'completed_by' => $user?->id,
                'note' => $note ?: $locked->note,
            ])->save();

            $task->setRawAttributes($locked->getAttributes(), true);
        });

        return $warnings;
    }

    /**
     * Loại cần số liệu riêng từng bé (nhiệt độ, cân đo, mức ăn): chỉ ghi nhật ký khi có số liệu —
     * không tạo dòng nhật ký rỗng. Việc vẫn được đánh dấu hoàn thành.
     */
    public static function hasRequiredValues(string $type, array $fields): bool
    {
        return match ($type) {
            'suc_khoe' => filled($fields['temperature'] ?? null),
            'can_do' => filled($fields['weight_kg'] ?? null) || filled($fields['height_cm'] ?? null),
            'an' => filled($fields['level'] ?? null),
            default => true,
        };
    }

    public function skip(Task $task, ?User $user): void
    {
        if ($task->isPending()) {
            $task->forceFill(['status' => 'skipped', 'completed_at' => now(), 'completed_by' => $user?->id])->save();
        }
    }

    /** Mở lại việc đã BỎ QUA (việc đã hoàn thành đã ghi nhật ký nên không mở lại được — hãy xoá nhật ký). */
    public function reopenSkipped(Task $task): void
    {
        if ($task->status === 'skipped') {
            $task->forceFill(['status' => 'pending', 'completed_at' => null, 'completed_by' => null])->save();
        }
    }

    /** Các bé đang học thuộc phạm vi công việc, trừ các bé đã điểm danh VẮNG hôm nay. @return Collection<int, Child> */
    public function targetChildren(Task $task): Collection
    {
        $query = Child::query()->where('center_id', $task->center_id)->active();

        $children = match ($task->target) {
            'child' => $query->where('id', $task->child_id)->get(),
            'classroom' => $query->where('classroom_id', $task->classroom_id)->get(),
            'all' => $query->get(),
            default => collect(),
        };

        if ($children->isEmpty()) {
            return $children;
        }

        $absent = Attendance::query()->whereIn('child_id', $children->pluck('id'))
            ->whereDate('date', Carbon::today())->where('status', '!=', 'co_mat')->pluck('child_id')->all();

        return $children->reject(fn (Child $c) => in_array($c->id, $absent, true))->values();
    }
}
