<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\TaskScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Màn hình "Hôm nay": sĩ số, việc cần làm, cảnh báo sức khoẻ, dị ứng, sinh nhật, thực đơn, kho, học phí. */
class TodayController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request, TaskScheduler $scheduler): View
    {
        $center = $this->currentCenter($request);
        $isOwner = $request->user()->isOwner();

        // Sinh sẵn công việc định kỳ (idempotent) — không cần cron trên hosting rẻ.
        $scheduler->generateForCenter($center);

        $today = Carbon::today();

        $tasks = $center->tasks()
            ->with(['child', 'classroom', 'item'])
            ->where('status', 'pending')
            ->where('due_at', '>=', $today->copy()->subDays(7))
            ->where('due_at', '<=', $today->copy()->endOfDay())
            ->orderBy('due_at')
            ->get();

        $doneToday = $center->tasks()->where('status', 'done')->whereDate('completed_at', $today)->count();

        $children = $center->children()->onRollAt($today)->with('classroom')->orderBy('full_name')->get();
        $attendance = $center->attendances()->whereDate('date', $today)->get()->keyBy('child_id');
        $presentIds = $attendance->where('status', 'co_mat')->keys();

        $todayEntries = $center->entries()->with(['child', 'item'])
            ->whereBetween('occurred_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->latest('occurred_at')->get();

        // Bé có sự cố / sốt / bỏ ăn / đi lỏng hôm nay — cần báo phụ huynh.
        $alerts = $todayEntries->filter(fn ($e) => $e->isAlert())->values();

        // Sinh nhật trong 7 ngày tới.
        $birthdays = $children->filter(fn ($c) => $c->nextBirthday()->lte($today->copy()->addDays(7)))
            ->sortBy(fn ($c) => $c->nextBirthday()->timestamp)->values();

        $lowStock = $center->items()->where('low_stock_threshold', '>', 0)
            ->whereColumn('current_stock', '<=', 'low_stock_threshold')->orderBy('name')->get();

        $unpaid = $isOwner
            ? $center->invoices()->whereColumn('paid_amount', '<', 'total_amount')
                ->selectRaw('COUNT(*) as n, SUM(total_amount - paid_amount) as due')->first()
            : null;

        return view('today', [
            'center' => $center,
            'overdue' => $tasks->filter(fn ($t) => $t->due_at->lt($today)),
            'todayTasks' => $tasks->filter(fn ($t) => $t->due_at->gte($today)),
            'doneToday' => $doneToday,
            'stats' => [
                'total' => $children->count(),
                'present' => $presentIds->count(),
                'absent' => $attendance->where('status', '!=', 'co_mat')->count(),
                'unmarked' => $children->count() - $attendance->whereIn('child_id', $children->pluck('id'))->count(),
                'not_out' => $attendance->where('status', 'co_mat')->whereNull('check_out')->count(),
            ],
            'allergies' => $children->filter(fn ($c) => $c->hasAllergy() && $presentIds->contains($c->id))->values(),
            'alerts' => $alerts,
            'birthdays' => $birthdays,
            'lowStock' => $lowStock,
            'menu' => $center->menus()->whereDate('date', $today)->get()->keyBy('meal'),
            'recentEntries' => $todayEntries->take(6),
            'needsSetup' => ! $center->children()->exists(),
            'needsClassrooms' => ! $center->classrooms()->exists(),
            'unpaid' => $unpaid,
            'isOwner' => $isOwner,
        ]);
    }
}
