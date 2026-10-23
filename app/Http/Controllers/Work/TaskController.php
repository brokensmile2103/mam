<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\TaskScheduler;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request, TaskScheduler $scheduler): View
    {
        $center = $this->currentCenter($request);
        $scheduler->generateForCenter($center);

        $tab = in_array($request->query('tab'), ['today', 'upcoming', 'done'], true) ? $request->query('tab') : 'today';
        $today = Carbon::today();

        $query = $center->tasks()->with(['child', 'classroom', 'item', 'completer']);

        $tasks = match ($tab) {
            'upcoming' => $query->where('status', 'pending')->where('due_at', '>', $today->copy()->endOfDay())->orderBy('due_at')->limit(100)->get(),
            'done' => $query->whereIn('status', ['done', 'skipped'])->orderByDesc('completed_at')->limit(60)->get(),
            default => $query->where('status', 'pending')->where('due_at', '>=', $today->copy()->subDays(7))->where('due_at', '<=', $today->copy()->endOfDay())->orderBy('due_at')->get(),
        };

        return view('tasks.index', [
            'center' => $center,
            'tab' => $tab,
            'tasks' => $tasks,
            'children' => $center->children()->active()->orderBy('full_name')->get(),
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
            'items' => $center->items()->orderBy('name')->get(),
        ]);
    }

    /** Thêm 1 việc làm 1 lần (không lặp). Việc lặp lại tạo ở "Lịch định kỳ". */
    public function store(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(config('mam.task_types')))],
            'target' => 'required|in:child,classroom,all,none',
            'child_id' => ['nullable', 'required_if:target,child', Rule::exists('children', 'id')->where('center_id', $center->id)],
            'classroom_id' => ['nullable', 'required_if:target,classroom', Rule::exists('classrooms', 'id')->where('center_id', $center->id)],
            'item_id' => ['nullable', Rule::exists('inventory_items', 'id')->where('center_id', $center->id)->whereNull('deleted_at')],
            'item_quantity' => 'nullable|numeric|min:0',
            'due_date' => 'required|date',
            'due_time' => 'required|date_format:H:i',
        ]);

        $center->tasks()->create([
            'title' => $data['title'],
            'type' => $data['type'],
            'target' => $data['type'] === 'don_dep' ? 'none' : $data['target'],
            'child_id' => $data['type'] !== 'don_dep' && $data['target'] === 'child' ? $data['child_id'] : null,
            'classroom_id' => $data['type'] !== 'don_dep' && $data['target'] === 'classroom' ? $data['classroom_id'] : null,
            'item_id' => $data['item_id'] ?? null,
            'item_quantity' => $data['item_quantity'] ?? null,
            'due_at' => Carbon::parse($data['due_date'].' '.$data['due_time']),
            'status' => 'pending',
        ]);

        return back()->with('status', 'Đã thêm công việc.');
    }

    public function complete(Request $request, TaskService $service, int $task): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $task = $center->tasks()->findOrFail($task);

        $data = $request->validate([
            'item_quantity' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|min:34|max:43',
            'weight_kg' => 'nullable|numeric|min:1|max:60',
            'height_cm' => 'nullable|numeric|min:30|max:150',
            'amount_ml' => 'nullable|integer|min:0|max:3000',
            'meal' => ['nullable', Rule::in(array_keys(config('mam.meals')))],
            'level' => ['nullable', Rule::in(array_keys(config('mam.eat_levels')))],
            'note' => 'nullable|string|max:200',
        ]);

        $warnings = $service->complete(
            $task,
            $request->user(),
            isset($data['item_quantity']) && $data['item_quantity'] !== '' ? (float) $data['item_quantity'] : null,
            $data,
            $data['note'] ?? null,
        );

        $redirect = back()->with('status', 'Đã hoàn thành: '.$task->title);

        return $warnings ? $redirect->with('warning', implode(' ', $warnings)) : $redirect;
    }

    public function skip(Request $request, TaskService $service, int $task): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $service->skip($center->tasks()->findOrFail($task), $request->user());

        return back()->with('status', 'Đã bỏ qua công việc.');
    }

    public function reopen(Request $request, TaskService $service, int $task): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $service->reopenSkipped($center->tasks()->findOrFail($task));

        return back()->with('status', 'Đã mở lại công việc.');
    }

    public function destroy(Request $request, int $task): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $task = $center->tasks()->where('status', 'pending')->findOrFail($task);
        $task->delete();

        return back()->with('status', 'Đã xoá công việc.');
    }

    public function skipOverdue(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request);

        $count = $center->tasks()->where('status', 'pending')->where('due_at', '<', Carbon::today())
            ->update(['status' => 'skipped', 'completed_at' => now(), 'completed_by' => $request->user()->id]);

        return back()->with('status', "Đã bỏ qua {$count} công việc quá hạn.");
    }
}
