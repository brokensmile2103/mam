<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Trang dành cho phụ huynh — mở bằng link bí mật (40 ký tự ngẫu nhiên) do chủ nhóm bật ở hồ sơ bé.
 * Chỉ ĐỌC: nhật ký 7 ngày gần nhất, điểm danh, cân đo, thực đơn tuần, học phí còn nợ.
 * Không hiện thông tin của bé khác, không hiện ghi chú nội bộ (health_note/note của hồ sơ).
 */
class ParentController extends Controller
{
    public function show(string $token): Response
    {
        $child = Child::query()->with(['center', 'classroom'])->where('parent_token', $token)->where('status', 'active')->first();

        abort_unless($child && $child->center?->is_active, 404);

        $center = $child->center;
        $today = Carbon::today();
        $from = $today->copy()->subDays(6);

        $entries = $child->entries()->with('item')
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $today->copy()->endOfDay()])
            ->orderBy('occurred_at')->get()
            ->filter(fn ($e) => $e->meta()['share'] ?? true)
            ->groupBy(fn ($e) => $e->occurred_at->toDateString());

        $attendance = $child->attendances()->whereBetween('date', [$from->toDateString(), $today->toDateString()])->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $days = collect(range(0, 6))->map(fn ($i) => $today->copy()->subDays($i))
            ->filter(fn ($d) => $entries->has($d->toDateString()) || $attendance->has($d->toDateString()) || $d->isToday())
            ->map(fn ($d) => [
                'date' => $d,
                'attendance' => $attendance->get($d->toDateString()),
                'entries' => $entries->get($d->toDateString(), collect()),
            ])->values();

        $monday = $today->copy()->startOfWeek(Carbon::MONDAY);
        $menus = $center->menus()->whereBetween('date', [$monday->toDateString(), $monday->copy()->addDays(6)->toDateString()])->get()
            ->groupBy(fn ($m) => $m->date->toDateString())->map(fn ($g) => $g->pluck('dishes', 'meal'));

        $invoices = $child->invoices()->whereColumn('paid_amount', '<', 'total_amount')->orderBy('month')->get();

        return response()
            ->view('parent.show', [
                'child' => $child,
                'center' => $center,
                'days' => $days,
                'growth' => $child->entries()->where('type', 'can_do')->orderByDesc('occurred_at')->limit(6)->get(),
                'monday' => $monday,
                'menus' => $menus,
                'invoices' => $invoices,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'private, no-store');
    }
}
