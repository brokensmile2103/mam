<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\DiaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Sổ nhật ký của bé: xem timeline + ghi nhanh cho 1 hoặc nhiều bé + báo cáo ngày gửi phụ huynh. */
class DiaryController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request);
        $types = array_keys(config('mam.entry_types'));
        $date = parse_date($request->query('ngay'));

        $entries = $center->entries()
            ->with(['child', 'item', 'creator'])
            ->when((int) $request->query('be'), fn ($q, $v) => $q->where('child_id', $v))
            ->when((int) $request->query('lop'), fn ($q, $v) => $q->whereHas('child', fn ($c) => $c->where('classroom_id', $v)))
            ->when(in_array($request->query('loai'), $types, true), fn ($q) => $q->where('type', $request->query('loai')))
            ->when($date, fn ($q) => $q->whereBetween('occurred_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]))
            ->latest('occurred_at')->latest('id')
            ->paginate(40)->withQueryString();

        return view('diary.index', [
            'center' => $center,
            'entries' => $entries,
            'date' => $date,
            'children' => $center->children()->orderByDesc('status')->orderBy('full_name')->get(),
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $center = $this->currentCenter($request);
        $type = array_key_exists((string) $request->query('loai'), config('mam.entry_types')) ? $request->query('loai') : 'an';

        $children = $center->children()->active()->with(['classroom', 'attendances' => fn ($a) => $a->whereDate('date', Carbon::today())])
            ->orderBy('full_name')->get();

        $preselect = collect((array) $request->query('be'))->map(fn ($v) => (int) $v)->filter()->values()->all();

        return view('diary.create', [
            'center' => $center,
            'type' => $type,
            'children' => $children,
            'preselect' => $preselect,
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
            'items' => $center->items()->orderBy('category')->orderBy('name')->get(),
            'returnTo' => $this->safeReturn($request, ''),
            'menuToday' => $center->menus()->whereDate('date', Carbon::today())->pluck('dishes', 'meal'),
        ]);
    }

    public function store(Request $request, DiaryService $service): RedirectResponse
    {
        $center = $this->currentCenter($request);

        $type = (string) $request->input('type');
        $meta = config('mam.entry_types.'.$type);
        abort_unless($meta, 422);

        $rules = [
            'type' => 'required',
            'child_ids' => 'required|array|min:1',
            'child_ids.*' => ['integer', Rule::exists('children', 'id')->where('center_id', $center->id)],
            'occurred_on' => 'required|date|before_or_equal:today',
            'occurred_time' => 'nullable|date_format:H:i',
            'note' => [in_array($type, config('mam.note_required_types'), true) ? 'required' : 'nullable', 'string', 'max:1000'],
            'item_id' => ['nullable', Rule::exists('inventory_items', 'id')->where('center_id', $center->id)->whereNull('deleted_at')],
            'item_quantity' => 'nullable|numeric|min:0|max:100000',
        ];

        $rules += match ($type) {
            'an' => ['meal' => ['required', Rule::in(array_keys(config('mam.meals')))], 'level' => ['required', Rule::in(array_keys(config('mam.eat_levels')))]],
            'sua' => ['amount_ml' => 'nullable|integer|min:0|max:3000'],
            'ngu' => ['sleep_from' => 'required|date_format:H:i', 'sleep_to' => 'nullable|date_format:H:i'],
            've_sinh' => ['toilet' => ['required', Rule::in(array_keys(config('mam.toilet_kinds')))]],
            'suc_khoe' => ['temperature' => 'required|numeric|min:34|max:43'],
            'can_do' => ['weight_kg' => 'nullable|required_without:height_cm|numeric|min:1|max:60', 'height_cm' => 'nullable|numeric|min:30|max:150'],
            default => [],
        };

        $data = $request->validate($rules, [
            'child_ids.required' => 'Hãy chọn ít nhất 1 bé.',
            'note.required' => 'Hãy ghi rõ nội dung (VD: thuốc gì, chuyện gì đã xảy ra).',
            'temperature.min' => 'Nhiệt độ không hợp lệ.',
            'temperature.max' => 'Nhiệt độ không hợp lệ.',
            'weight_kg.required_without' => 'Nhập cân nặng hoặc chiều cao.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['child_ids'])));
        $children = $center->children()->active()->whereIn('id', $ids)->get();

        if ($children->count() !== count($ids)) {
            throw ValidationException::withMessages(['child_ids' => 'Có bé đã nghỉ học — hãy bỏ chọn bé đó.']);
        }

        $when = $this->resolveWhen($data['occurred_on'], $data['occurred_time'] ?? null);
        $item = $meta['item'] && ! empty($data['item_id']) ? $center->items()->find($data['item_id']) : null;

        $result = $service->log(
            $center, $request->user(), $children, $type, $when, $data, $item,
            isset($data['item_quantity']) ? (float) $data['item_quantity'] : null,
            $data['note'] ?? null,
        );

        $who = $children->count() === 1 ? $children->first()->displayName() : $children->count().' bé';
        $redirect = redirect()->to($this->safeReturn($request, route('diary.index')))
            ->with('status', 'Đã ghi '.mb_strtolower($meta['label']).' cho '.$who.'.');

        return $result['warnings'] ? $redirect->with('warning', implode(' ', $result['warnings'])) : $redirect;
    }

    public function destroy(Request $request, DiaryService $service, int $entry): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $entry = $center->entries()->findOrFail($entry);
        $user = $request->user();

        // Chủ nhóm xoá được mọi ghi nhận; cô giáo chỉ xoá được ghi nhận CỦA MÌNH trong 12 giờ.
        $allowed = $user->isOwner() || ($entry->created_by === $user->id && $entry->created_at->gt(now()->subHours(12)));
        abort_unless($allowed, 403, 'Bạn chỉ xoá được ghi nhận của mình trong vòng 12 giờ.');

        $service->delete($entry);

        return back()->with('status', 'Đã xoá ghi nhận.');
    }

    /** Báo cáo ngày của cả lớp — mỗi bé 1 đoạn văn bản để cô bấm "Sao chép" gửi Zalo phụ huynh. */
    public function report(Request $request): View
    {
        $center = $this->currentCenter($request);
        $day = parse_date($request->query('ngay'), Carbon::today());
        $classroomId = (int) $request->query('lop') ?: null;

        $children = $center->children()->active()
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderBy('full_name')->get();

        $entries = $center->entries()->with('item')->whereIn('child_id', $children->pluck('id'))
            ->whereBetween('occurred_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->orderBy('occurred_at')->get()->groupBy('child_id');
        $attendance = $center->attendances()->whereDate('date', $day)->get()->keyBy('child_id');

        $reports = $children
            ->filter(fn ($c) => $entries->has($c->id) || $attendance->has($c->id))
            ->map(fn ($c) => [
                'child' => $c,
                'text' => daily_report($c, $day, $entries->get($c->id, collect()), $attendance->get($c->id)),
                'alert' => $entries->get($c->id, collect())->contains(fn ($e) => $e->isAlert()),
            ])->values();

        return view('diary.report', [
            'center' => $center,
            'day' => $day,
            'classroomId' => $classroomId,
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
            'reports' => $reports,
            'missing' => $children->reject(fn ($c) => $entries->has($c->id) || $attendance->has($c->id))->values(),
        ]);
    }

    private function resolveWhen(string $date, ?string $time): Carbon
    {
        $day = Carbon::parse($date);

        if (! $time) {
            return $day->isToday() ? now() : $day->setTime(12, 0);
        }

        $when = $day->setTimeFromTimeString($time);

        return $when->isFuture() ? now() : $when;
    }
}
