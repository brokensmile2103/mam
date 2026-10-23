<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Center;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * CLOUD — khu vực Super Admin, quản lý TOÀN BỘ nhóm trẻ đã đăng ký.
 * Không thuộc phạm vi 1 nhóm trẻ cụ thể nên KHÔNG dùng session current_center_id.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $centers = Center::withCount(['staff', 'children', 'entries'])
            ->with('owner')
            // Mốc hoạt động gần nhất — 1 phép tổng hợp cho cả trang, chỉ lấy đúng 1 mốc thời gian.
            ->withMax('entries as last_event_at', 'occurred_at')
            ->when($request->query('q'), function ($q, $search) {
                $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($q3) => $q3->where('phone', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Center::count(),
            'active' => Center::where('is_active', true)->count(),
            'inactive' => Center::where('is_active', false)->count(),
        ];

        return view('admin.dashboard', compact('centers', 'stats'));
    }

    /**
     * Chi tiết 1 nhóm trẻ — CHỈ để đánh giá nhóm trẻ còn hoạt động hay không (hỗ trợ vận hành).
     * CỐ TÌNH chỉ trả về SỐ LƯỢNG, tuyệt đối KHÔNG load nội dung nhật ký, giá, doanh thu,
     * học phí — dữ liệu của trẻ em và phụ huynh không thuộc phạm vi Super Admin
     * (nguyên tắc data minimization).
     */
    public function show(Center $center): View
    {
        $center->load('owner');

        $lastEventAt = $center->entries()->max('occurred_at');
        $firstEventAt = $center->entries()->min('occurred_at');

        return view('admin.centers.show', [
            'center' => $center,
            'stats' => [
                'staff_count' => $center->staff()->count(),
                'classroom_count' => $center->classrooms()->count(),
                'active_children' => $center->children()->where('status', 'active')->count(),
                'left_children' => $center->children()->where('status', 'left')->count(),
                'event_count' => $center->entries()->count(),
                'item_count' => $center->items()->count(),
                'invoice_count' => $center->invoices()->count(),
                'routine_count' => $center->taskTemplates()->where('is_active', true)->count(),
                'attendance_7d' => $center->attendances()->where('date', '>=', now()->subDays(7)->toDateString())->count(),
                'events_7d' => $center->entries()->where('occurred_at', '>=', now()->subDays(7))->count(),
                'events_30d' => $center->entries()->where('occurred_at', '>=', now()->subDays(30))->count(),
                'last_event_at' => $lastEventAt ? Carbon::parse($lastEventAt) : null,
                'first_event_at' => $firstEventAt ? Carbon::parse($firstEventAt) : null,
            ],
        ]);
    }

    /** Khoá/mở lại 1 nhóm trẻ — nhóm trẻ bị khoá thì chủ nhóm/thành viên không đăng nhập được nữa. */
    public function toggleActive(Center $center): RedirectResponse
    {
        $center->forceFill(['is_active' => ! $center->is_active])->save();

        return back()->with('status', 'Đã '.($center->is_active ? 'mở lại' : 'tạm khoá').' nhóm trẻ "'.$center->name.'".');
    }
}
