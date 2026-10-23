<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\TaskTemplate;
use App\Services\TaskScheduler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Công việc định kỳ (routine): cho bé uống thuốc theo giờ, khử khuẩn đồ chơi mỗi tuần, cân đo hằng tháng... */
class RoutineController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request, true);

        return view('routines.index', [
            'center' => $center,
            'templates' => $center->taskTemplates()->with(['child', 'classroom', 'item'])->orderByDesc('is_active')->orderBy('title')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $center = $this->currentCenter($request, true);

        return view('routines.form', $this->formData($center, new TaskTemplate([
            'type' => array_key_exists((string) $request->query('type'), config('mam.task_types')) ? $request->query('type') : 'thuoc',
            'target' => 'all',
            'frequency' => 'daily',
            'times' => '09:00',
            'starts_on' => Carbon::today(),
        ])));
    }

    public function store(Request $request, TaskScheduler $scheduler): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $template = new TaskTemplate($this->payload($request, $center));
        $template->center_id = $center->id;
        $template->created_by = $request->user()->id;
        $template->is_active = true;
        $template->save();

        $scheduler->regenerate($template);

        return redirect()->route('routines.index')->with('status', 'Đã tạo lịch "'.$template->title.'" — công việc sẽ tự xuất hiện ở mục Hôm nay.');
    }

    public function edit(Request $request, int $template): View
    {
        $center = $this->currentCenter($request, true);

        return view('routines.form', $this->formData($center, $center->taskTemplates()->findOrFail($template)));
    }

    public function update(Request $request, TaskScheduler $scheduler, int $template): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $template = $center->taskTemplates()->findOrFail($template);

        $template->fill($this->payload($request, $center))->save();
        $scheduler->regenerate($template);

        return redirect()->route('routines.index')->with('status', 'Đã cập nhật lịch "'.$template->title.'".');
    }

    public function toggle(Request $request, TaskScheduler $scheduler, int $template): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $template = $center->taskTemplates()->findOrFail($template);

        $template->forceFill(['is_active' => ! $template->is_active])->save();
        $scheduler->regenerate($template);

        return back()->with('status', $template->is_active ? 'Đã bật lại lịch.' : 'Đã tạm dừng lịch — các việc chưa làm sắp tới đã được gỡ.');
    }

    public function destroy(Request $request, TaskScheduler $scheduler, int $template): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $template = $center->taskTemplates()->findOrFail($template);

        $scheduler->clearFuturePending($template);
        $template->delete(); // các task đã làm giữ lại làm lịch sử (template_id về null)

        return back()->with('status', 'Đã xoá lịch "'.$template->title.'".');
    }

    private function formData(Center $center, TaskTemplate $template): array
    {
        return [
            'center' => $center,
            'template' => $template,
            'children' => $center->children()->active()->orderBy('full_name')->get(),
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
            'items' => $center->items()->orderBy('name')->get(),
        ];
    }

    private function payload(Request $request, Center $center): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(config('mam.task_types')))],
            'target' => 'required|in:child,classroom,all,none',
            'child_id' => ['nullable', 'required_if:target,child', Rule::exists('children', 'id')->where('center_id', $center->id)],
            'classroom_id' => ['nullable', 'required_if:target,classroom', Rule::exists('classrooms', 'id')->where('center_id', $center->id)],
            'item_id' => ['nullable', Rule::exists('inventory_items', 'id')->where('center_id', $center->id)->whereNull('deleted_at')],
            'item_quantity' => 'nullable|numeric|min:0|max:100000000',
            'frequency' => ['required', Rule::in(array_keys(config('mam.frequencies')))],
            'weekdays' => 'nullable|required_if:frequency,weekly|array',
            'weekdays.*' => 'integer|between:1,7',
            'month_day' => 'nullable|required_if:frequency,monthly|integer|between:1,31',
            'interval_days' => 'nullable|required_if:frequency,interval|integer|between:1,365',
            'times' => 'required|array|min:1|max:8',
            'times.*' => 'required|date_format:H:i',
            'starts_on' => 'required|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'note' => 'nullable|string|max:255',
        ], [
            'weekdays.required_if' => 'Hãy chọn ít nhất 1 ngày trong tuần.',
            'times.required' => 'Hãy nhập ít nhất 1 giờ.',
        ]);

        $times = collect($data['times'])->unique()->sort()->values()->implode(',');

        return [
            'title' => $data['title'],
            'type' => $data['type'],
            'target' => $data['type'] === 'don_dep' ? 'none' : $data['target'],
            'child_id' => $data['type'] !== 'don_dep' && $data['target'] === 'child' ? $data['child_id'] : null,
            'classroom_id' => $data['type'] !== 'don_dep' && $data['target'] === 'classroom' ? $data['classroom_id'] : null,
            'item_id' => $data['item_id'] ?? null,
            'item_quantity' => ($data['item_id'] ?? null) ? ($data['item_quantity'] ?? null) : null,
            'frequency' => $data['frequency'],
            'weekdays' => $data['frequency'] === 'weekly' ? collect($data['weekdays'] ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->implode(',') : null,
            'month_day' => $data['frequency'] === 'monthly' ? (int) $data['month_day'] : null,
            'interval_days' => $data['frequency'] === 'interval' ? (int) $data['interval_days'] : null,
            'times' => $times,
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }
}
