<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Thực đơn theo tuần (T2–T7) — cô giáo và chủ nhóm đều sửa được; phụ huynh xem qua trang phụ huynh. */
class MenuController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request);
        $monday = parse_date($request->query('tuan'), Carbon::today())->startOfWeek(Carbon::MONDAY);
        $days = collect(range(0, 5))->map(fn ($i) => $monday->copy()->addDays($i));

        $menus = $center->menus()->whereBetween('date', [$monday->toDateString(), $monday->copy()->addDays(6)->toDateString()])->get()
            ->groupBy(fn ($m) => $m->date->toDateString())->map(fn ($g) => $g->pluck('dishes', 'meal'));

        return view('menus.index', [
            'center' => $center,
            'monday' => $monday,
            'days' => $days,
            'menus' => $menus,
            'hasPrevious' => $center->menus()->whereBetween('date', [$monday->copy()->subWeek()->toDateString(), $monday->copy()->subDay()->toDateString()])->exists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request);

        $data = $request->validate([
            'week' => 'required|date',
            'dishes' => 'nullable|array',
            'dishes.*' => 'nullable|array',
            'dishes.*.*' => 'nullable|string|max:500',
        ]);

        $monday = Carbon::parse($data['week'])->startOfWeek(Carbon::MONDAY);
        $allowedDays = collect(range(0, 6))->map(fn ($i) => $monday->copy()->addDays($i)->toDateString())->all();
        $meals = array_keys(config('mam.meals'));

        DB::transaction(function () use ($center, $data, $allowedDays, $meals) {
            foreach ($data['dishes'] ?? [] as $date => $byMeal) {
                if (! in_array($date, $allowedDays, true)) {
                    continue;
                }

                foreach ((array) $byMeal as $meal => $dishes) {
                    if (! in_array($meal, $meals, true)) {
                        continue;
                    }

                    $dishes = trim((string) $dishes);
                    $existing = $center->menus()->whereDate('date', $date)->where('meal', $meal)->first();

                    if ($dishes === '') {
                        $existing?->delete();
                    } elseif ($existing) {
                        $existing->update(['dishes' => $dishes]);
                    } else {
                        $center->menus()->create(['date' => $date, 'meal' => $meal, 'dishes' => $dishes]);
                    }
                }
            }
        });

        return redirect()->route('menus.index', ['tuan' => $monday->toDateString()])->with('status', 'Đã lưu thực đơn tuần.');
    }

    /** Chép thực đơn tuần trước sang tuần đang xem (chỉ điền vào ô còn trống). */
    public function copyPrevious(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $data = $request->validate(['week' => 'required|date']);
        $monday = Carbon::parse($data['week'])->startOfWeek(Carbon::MONDAY);

        $previous = $center->menus()->whereBetween('date', [$monday->copy()->subWeek()->toDateString(), $monday->copy()->subDay()->toDateString()])->get();
        $copied = 0;

        foreach ($previous as $menu) {
            $date = $menu->date->copy()->addWeek()->toDateString();
            if (! $center->menus()->whereDate('date', $date)->where('meal', $menu->meal)->exists()) {
                $center->menus()->create(['date' => $date, 'meal' => $menu->meal, 'dishes' => $menu->dishes]);
                $copied++;
            }
        }

        return redirect()->route('menus.index', ['tuan' => $monday->toDateString()])
            ->with('status', $copied ? "Đã chép {$copied} món từ tuần trước." : 'Không có ô trống nào để chép.');
    }
}
