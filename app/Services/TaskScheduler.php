<?php

namespace App\Services;

use App\Models\Center;
use App\Models\TaskTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sinh công việc (Task) từ lịch định kỳ (TaskTemplate). Idempotent: unique
 * (template_id, due_at) + insertOrIgnore nên chạy lại bao nhiêu lần cũng không trùng.
 * Chỉ sinh từ HÔM NAY trở đi — không sinh bù những ngày đã qua.
 */
class TaskScheduler
{
    /**
     * Các thời điểm xảy ra của 1 lịch trong khoảng [from, to] (theo ngày). Hàm thuần, dễ test.
     *
     * @return list<Carbon>
     */
    public function occurrences(TaskTemplate $template, Carbon $from, Carbon $to): array
    {
        $out = [];
        $start = $template->starts_on->copy()->startOfDay();
        $day = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        if ($day->lt($start)) {
            $day = $start->copy();
        }

        $times = array_map(fn ($t) => array_map('intval', explode(':', $t) + [0, 0]), $template->timesList());

        for (; $day->lte($end); $day->addDay()) {
            if ($template->ends_on && $day->gt($template->ends_on->copy()->startOfDay())) {
                break;
            }

            if (! $this->matchesDay($template, $start, $day)) {
                continue;
            }

            foreach ($times as [$h, $m]) {
                $out[] = $day->copy()->setTime($h, $m);
            }
        }

        return $out;
    }

    public function matchesDay(TaskTemplate $template, Carbon $start, Carbon $day): bool
    {
        switch ($template->frequency) {
            case 'weekdays':
                return $day->dayOfWeekIso <= 5;

            case 'weekly':
                $weekdays = $template->weekdaysList() ?: [$start->dayOfWeekIso];

                return in_array($day->dayOfWeekIso, $weekdays, true);

            case 'monthly':
                $target = $template->month_day ?: $start->day;

                return $day->day === min($target, $day->daysInMonth);

            case 'interval':
                $interval = max(1, (int) $template->interval_days);

                return ((int) round($start->diffInDays($day, false))) % $interval === 0;

            default:
                return true;
        }
    }

    /** Sinh task cho các lịch đang bật của 1 nhóm trẻ tới hết (hôm nay + horizon) ngày. Trả về số task mới. */
    public function generateForCenter(Center $center): int
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays((int) config('mam.task_horizon_days', 2));

        $templates = $center->taskTemplates()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('last_generated_on')->orWhere('last_generated_on', '<', $until->toDateString()))
            ->get();

        $created = 0;
        foreach ($templates as $template) {
            $created += $this->generateForTemplate($template, $today, $until);
        }

        return $created;
    }

    public function generateForTemplate(TaskTemplate $template, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $from ??= Carbon::today();
        $to ??= $from->copy()->addDays((int) config('mam.task_horizon_days', 2));

        $now = now();
        $rows = [];

        // Lịch gắn 1 bé đã nghỉ học: không sinh việc mới (tránh việc "ma" không gắn được bé nào).
        $occurrences = $template->target === 'child' && ! $template->child()->where('status', 'active')->exists()
            ? []
            : $this->occurrences($template, $from, $to);

        foreach ($occurrences as $due) {
            $rows[] = [
                'center_id' => $template->center_id,
                'template_id' => $template->id,
                'title' => $template->title,
                'type' => $template->type,
                'target' => $template->target,
                'child_id' => $template->child_id,
                'classroom_id' => $template->classroom_id,
                'item_id' => $template->item_id,
                'item_quantity' => $template->item_quantity,
                'due_at' => $due->toDateTimeString(),
                'status' => 'pending',
                'note' => $template->note,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $created = $rows ? DB::table('tasks')->insertOrIgnore($rows) : 0;

        $template->forceFill(['last_generated_on' => $to->toDateString()])->save();

        return (int) $created;
    }

    /** Sau khi sửa/bật lại 1 lịch: xoá các task CHƯA LÀM trong tương lai rồi sinh lại theo lịch mới. */
    public function regenerate(TaskTemplate $template): void
    {
        $this->clearFuturePending($template);
        $template->forceFill(['last_generated_on' => null])->save();

        if ($template->is_active) {
            $this->generateForTemplate($template);
        }
    }

    public function clearFuturePending(TaskTemplate $template): void
    {
        $template->tasks()
            ->where('status', 'pending')
            ->where('due_at', '>=', Carbon::today()->startOfDay())
            ->delete();
    }
}
