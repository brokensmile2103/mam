<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Child;
use App\Services\DiaryService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Hồ sơ trẻ. Xem: mọi thành viên. Thêm/sửa/nghỉ học/xoá: chỉ chủ nhóm (route middleware). */
class ChildController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request);

        $status = $request->query('status') === 'left' ? 'left' : 'active';
        $classroomId = (int) $request->query('lop') ?: null;
        $q = trim((string) $request->query('q'));

        $children = $center->children()
            ->with(['classroom', 'attendances' => fn ($a) => $a->whereDate('date', Carbon::today())])
            ->where('status', $status)
            ->when($classroomId, fn ($query) => $query->where('classroom_id', $classroomId))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('full_name', 'like', '%'.$q.'%')
                ->orWhere('nickname', 'like', '%'.$q.'%')->orWhere('parent_name', 'like', '%'.$q.'%')->orWhere('parent_phone', 'like', '%'.$q.'%')))
            ->orderBy('full_name')
            ->get();

        return view('children.index', [
            'center' => $center,
            'children' => $children,
            'status' => $status,
            'classroomId' => $classroomId,
            'q' => $q,
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
            'counts' => [
                'active' => $center->children()->where('status', 'active')->count(),
                'left' => $center->children()->where('status', 'left')->count(),
            ],
            'isOwner' => $request->user()->isOwner(),
        ]);
    }

    public function show(Request $request, int $child): View
    {
        $center = $this->currentCenter($request);
        $child = $center->children()->with('classroom')->findOrFail($child);
        $isOwner = $request->user()->isOwner();
        $day = parse_date($request->query('ngay'), Carbon::today());

        $entries = $child->entries()->with(['item', 'creator'])->latest('occurred_at')->latest('id')->paginate(30);

        $dayEntries = $child->entries()->with('item')
            ->whereBetween('occurred_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->orderBy('occurred_at')->get();

        $growth = $child->entries()->where('type', 'can_do')
            ->where(fn ($q) => $q->whereNotNull('weight_kg')->orWhereNotNull('height_cm'))
            ->orderBy('occurred_at')->get(['occurred_at', 'weight_kg', 'height_cm']);

        $monthStart = Carbon::today()->startOfMonth();
        $monthAttendance = $child->attendances()->whereBetween('date', [$monthStart->toDateString(), Carbon::today()->toDateString()])
            ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('children.show', [
            'center' => $center,
            'child' => $child,
            'isOwner' => $isOwner,
            'day' => $day,
            'entries' => $entries,
            'dayEntries' => $dayEntries,
            'dayAttendance' => $child->attendances()->whereDate('date', $day)->first(),
            'dailyReport' => daily_report($child, $day, $dayEntries, $child->attendances()->whereDate('date', $day)->first()),
            'growth' => $growth,
            'monthAttendance' => $monthAttendance,
            'invoices' => $isOwner ? $child->invoices()->with('payments')->orderByDesc('month')->limit(12)->get() : collect(),
            'classrooms' => $isOwner ? $center->classrooms()->orderBy('sort_order')->orderBy('name')->get() : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $center = $this->currentCenter($request, true);

        return view('children.form', $this->formData($center, new Child([
            'classroom_id' => (int) $request->query('lop') ?: null,
            'enrolled_on' => Carbon::today(),
            'parent_relation' => 'Mẹ',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $data = $this->validated($request, $center);

        $child = new Child($data);
        $child->center_id = $center->id;
        $child->status = 'active';
        $child->code = $this->nextCode($center);
        $child->save();

        return redirect()->route('children.show', $child->id)->with('status', 'Đã thêm hồ sơ '.$child->displayName().'.');
    }

    public function edit(Request $request, int $child): View
    {
        $center = $this->currentCenter($request, true);

        return view('children.form', $this->formData($center, $center->children()->findOrFail($child)));
    }

    public function update(Request $request, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);

        $child->fill($this->validated($request, $center))->save();

        return redirect()->route('children.show', $child->id)->with('status', 'Đã cập nhật hồ sơ.');
    }

    /** Chuyển lớp nhanh. */
    public function move(Request $request, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);

        $data = $request->validate([
            'classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where('center_id', $center->id)],
        ]);

        $child->update(['classroom_id' => $data['classroom_id'] ?? null]);

        return back()->with('status', 'Đã chuyển lớp.');
    }

    /** Cho bé nghỉ học (giữ lại toàn bộ hồ sơ, nhật ký, học phí). */
    public function leave(Request $request, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);

        $data = $request->validate(
            ['left_on' => 'nullable|date|after_or_equal:'.$child->enrolled_on->toDateString()],
            ['left_on.after_or_equal' => 'Ngày nghỉ học không được trước ngày nhập học ('.$child->enrolled_on->format('d/m/Y').').'],
        );
        $leftOn = $data['left_on'] ?? Carbon::today()->toDateString();

        // Không còn đi học thì tắt link phụ huynh để thông tin không còn hiện công khai.
        $child->forceFill(['status' => 'left', 'left_on' => $leftOn, 'parent_token' => null])->save();

        // Gỡ các việc riêng của bé (uống thuốc...) chưa làm sau ngày nghỉ — tránh việc "ma" ở mục Hôm nay.
        $center->tasks()->where('child_id', $child->id)->where('status', 'pending')
            ->where('due_at', '>', Carbon::parse($leftOn)->endOfDay())->delete();

        return back()->with('status', $child->displayName().' đã chuyển sang danh sách nghỉ học.');
    }

    public function rejoin(Request $request, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);

        $child->forceFill(['status' => 'active', 'left_on' => null])->save();

        return back()->with('status', $child->displayName().' đã đi học lại.');
    }

    /** Bật / đổi link xem dành cho phụ huynh. */
    public function shareLink(Request $request, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);
        abort_unless($child->isActive(), 422, 'Bé đã nghỉ học.');

        $child->forceFill(['parent_token' => Str::random(40)])->save();

        return back()->with('status', 'Đã tạo link mới cho phụ huynh. Link cũ (nếu có) không còn dùng được.');
    }

    public function unshareLink(Request $request, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);

        $child->forceFill(['parent_token' => null])->save();

        return back()->with('status', 'Đã tắt link phụ huynh.');
    }

    /** Xoá hẳn hồ sơ — chỉ cho phép khi bé CHƯA có khoản thu học phí nào (tránh mất sổ sách tiền). */
    public function destroy(Request $request, InventoryService $inventory, int $child): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $child = $center->children()->findOrFail($child);

        if ($center->payments()->whereIn('invoice_id', $child->invoices()->select('id'))->exists()) {
            return back()->with('warning', 'Không thể xoá vì '.$child->displayName().' đã có khoản thu học phí. Hãy dùng "Nghỉ học" để lưu trữ hồ sơ.');
        }

        $name = $child->displayName();

        DB::transaction(function () use ($child, $inventory) {
            // Hoàn lại vật tư (sữa, tã...) đã trừ kho theo nhật ký của bé trước khi xoá.
            foreach ($child->entries()->whereNotNull('item_id')->get() as $entry) {
                $inventory->restoreForEntry($entry);
            }

            $child->delete();
        });

        return redirect()->route('children.index')->with('status', 'Đã xoá hồ sơ '.$name.'.');
    }

    // ---------------------------------------------------------------------

    private function formData(Center $center, Child $child): array
    {
        return [
            'center' => $center,
            'child' => $child,
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, Center $center): array
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:100',
            'nickname' => 'nullable|string|max:40',
            'gender' => 'nullable|in:nam,nu',
            'birth_date' => 'required|date|before_or_equal:today|after:'.Carbon::today()->subYears(7)->toDateString(),
            'enrolled_on' => 'required|date',
            'classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where('center_id', $center->id)],
            'parent_name' => 'nullable|string|max:100',
            'parent_relation' => 'nullable|string|max:20',
            'parent_phone' => 'nullable|string|max:20',
            'parent2_name' => 'nullable|string|max:100',
            'parent2_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'allergies' => 'nullable|string|max:500',
            'health_note' => 'nullable|string|max:2000',
            'pickup_note' => 'nullable|string|max:255',
            'tuition_mode' => ['nullable', Rule::in(array_keys(config('mam.tuition_modes')))],
            'tuition_fee' => 'nullable|numeric|min:0|max:1000000000',
            'daily_fee' => 'nullable|numeric|min:0|max:100000000',
            'meal_rate' => 'nullable|numeric|min:0|max:100000000',
            'note' => 'nullable|string|max:2000',
        ], [
            'birth_date.after' => 'Ngày sinh không hợp lệ (nhóm trẻ nhận trẻ dưới 6 tuổi).',
        ]);

        // Ô tiền để trống = theo mức mặc định của nhóm (lưu null, KHÔNG lưu 0).
        foreach (['tuition_fee', 'daily_fee', 'meal_rate'] as $k) {
            $data[$k] = isset($data[$k]) && $data[$k] !== '' && $data[$k] !== null ? (float) $data[$k] : null;
        }
        $data['tuition_mode'] = ($data['tuition_mode'] ?? null) ?: null; // '' = theo cách tính chung của nhóm

        return $data;
    }

    private function nextCode(Center $center): string
    {
        $max = $center->children()->pluck('code')
            ->map(fn ($c) => preg_match('/^B(\d+)$/', (string) $c, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;

        return 'B'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
