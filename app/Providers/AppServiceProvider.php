<?php

namespace App\Providers;

use App\Models\Center;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Số liệu cho menu điều hướng của layouts.app: tính 1 LẦN tại đây (layout hiện diện ở mọi trang).
        // Gói trong try/catch: badge lỗi âm thầm về 0 KHÔNG được làm sập cả trang.
        View::composer('layouts.app', function ($view) {
            $view->with([
                'currentCenter' => null,
                'taskBadgeCount' => 0,
                'lowStockBadgeCount' => 0,
                'unmarkedBadgeCount' => 0,
            ]);

            $user = auth()->user();
            $centerId = session('current_center_id');

            if (! $user || ! $centerId) {
                return;
            }

            try {
                $center = Center::find($centerId);

                if (! $center) {
                    return;
                }

                $allowed = $user->isOwner()
                    ? $center->owner_id === $user->id
                    : $center->staff()->where('users.id', $user->id)->exists();

                if (! $allowed) {
                    return;
                }

                $view->with([
                    'currentCenter' => $center,
                    // Việc quá hạn + việc hôm nay chưa làm
                    // Cùng khung 7 ngày với danh sách ở Hôm nay/Công việc — badge không đếm việc quá cũ mà danh sách không hiện.
                    'taskBadgeCount' => $center->tasks()->where('status', 'pending')
                        ->where('due_at', '>=', now()->startOfDay()->subDays(7))
                        ->where('due_at', '<=', now()->endOfDay())->count(),
                    'lowStockBadgeCount' => $center->items()->where('low_stock_threshold', '>', 0)->whereColumn('current_stock', '<=', 'low_stock_threshold')->count(),
                    // Bé đang học chưa được điểm danh hôm nay (chỉ nhắc vào ngày thường T2–T6).
                    'unmarkedBadgeCount' => now()->isWeekday()
                        ? $center->children()->onRollAt(now()->toDateString())
                            ->whereDoesntHave('attendances', fn ($q) => $q->whereDate('date', now()->toDateString()))->count()
                        : 0,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
