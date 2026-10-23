<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Điểm danh hằng ngày — cô giáo và chủ nhóm đều dùng được. */
class AttendanceController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request);
        $date = parse_date($request->query('ngay'), Carbon::today());
        if ($date->isFuture()) {
            $date = Carbon::today();
        }
        $classroomId = (int) $request->query('lop') ?: null;

        // Bé có tên trong lớp vào ngày đó — gồm cả bé đã nghỉ SAU ngày đó (để điểm danh bù những ngày bé còn đi học).
        $children = $center->children()->onRollAt($date)->with('classroom')
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderBy('full_name')->get();

        $rows = $center->attendances()->whereDate('date', $date)->whereIn('child_id', $children->pluck('id'))->get()->keyBy('child_id');

        return view('attendance.index', [
            'center' => $center,
            'date' => $date,
            'classroomId' => $classroomId,
            'classrooms' => $center->classrooms()->orderBy('sort_order')->orderBy('name')->get(),
            'children' => $children,
            'rows' => $rows,
            'stats' => [
                'total' => $children->count(),
                'present' => $rows->where('status', 'co_mat')->count(),
                'absent' => $rows->where('status', '!=', 'co_mat')->count(),
                'unmarked' => $children->count() - $rows->count(),
                'out' => $rows->whereNotNull('check_out')->count(),
            ],
        ]);
    }

    public function mark(Request $request, AttendanceService $service): RedirectResponse
    {
        $center = $this->currentCenter($request);

        $data = $request->validate([
            'child_id' => ['required', Rule::exists('children', 'id')->where('center_id', $center->id)],
            'date' => 'required|date|before_or_equal:today',
            'status' => ['required', Rule::in(array_keys(config('mam.attendance_statuses')))],
            'note' => 'nullable|string|max:255',
        ]);

        $child = $center->children()->findOrFail($data['child_id']);
        $row = $service->mark($center, $child, Carbon::parse($data['date']), $data['status'], $request->user(), $data['note'] ?? null);

        return redirect()->to(strtok(url()->previous(), '#').'#be-'.$child->id)->with('status', $child->displayName().': '.$row->meta()['label'].'.');
    }

    public function markAll(Request $request, AttendanceService $service): RedirectResponse
    {
        $center = $this->currentCenter($request);

        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'classroom_id' => ['nullable', Rule::exists('classrooms', 'id')->where('center_id', $center->id)],
        ]);

        $date = Carbon::parse($data['date']);
        $children = $center->children()->onRollAt($date)
            ->when($data['classroom_id'] ?? null, fn ($q, $id) => $q->where('classroom_id', $id))->get();

        $count = $service->markAllPresent($center, $children, $date, $request->user());

        return back()->with('status', $count ? "Đã điểm danh có mặt cho {$count} bé." : 'Các bé đều đã được điểm danh.');
    }

    public function checkOut(Request $request, AttendanceService $service, int $attendance): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $row = $center->attendances()->with('child')->findOrFail($attendance);

        $data = $request->validate([
            'check_out' => 'nullable|date_format:H:i',
            'picked_up_by' => 'nullable|string|max:100',
        ]);

        $service->checkOut($row, $data['check_out'] ?? null, $data['picked_up_by'] ?? null);

        return redirect()->to(strtok(url()->previous(), '#').'#be-'.$row->child_id)->with('status', $row->child->displayName().' đã về lúc '.$row->checkOutLabel().'.');
    }

    /** Sửa giờ đến/về (bấm nhầm, ghi bù). */
    public function updateTimes(Request $request, int $attendance): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $row = $center->attendances()->findOrFail($attendance);
        abort_unless($row->isPresent(), 422);

        $data = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after_or_equal:check_in',
            'picked_up_by' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:255',
        ], ['check_out.after_or_equal' => 'Giờ về phải sau giờ đến.']);

        $row->fill([
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'picked_up_by' => $data['picked_up_by'] ?? null,
            'note' => $data['note'] ?? null,
        ])->save();

        return back()->with('status', 'Đã lưu giờ đến/về.');
    }

    public function destroy(Request $request, int $attendance): RedirectResponse
    {
        $center = $this->currentCenter($request);
        $row = $center->attendances()->with('child')->findOrFail($attendance);
        $row->delete();

        return redirect()->to(strtok(url()->previous(), '#').'#be-'.$row->child_id)
            ->with('status', ($row->child?->displayName() ?? 'Bé').': đã bỏ điểm danh (về "chưa điểm danh").');
    }
}
