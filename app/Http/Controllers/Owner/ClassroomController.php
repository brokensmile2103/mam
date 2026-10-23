<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lớp / nhóm theo độ tuổi. */
class ClassroomController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request, true);

        $classrooms = $center->classrooms()->withCount('activeChildren')->orderBy('sort_order')->orderBy('name')->get();
        $existing = $classrooms->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        return view('classrooms.index', [
            'center' => $center,
            'classrooms' => $classrooms,
            'unassigned' => $center->children()->active()->whereNull('classroom_id')->count(),
            'presets' => collect(config('mam.classroom_presets'))->reject(fn ($p) => in_array(mb_strtolower($p['name']), $existing, true))->values(),
        ]);
    }

    public function quickAdd(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $data = $request->validate(['presets' => 'required|array|min:1', 'presets.*' => 'integer'], ['presets.required' => 'Hãy chọn ít nhất 1 nhóm.']);

        $added = 0;
        foreach (config('mam.classroom_presets') as $i => $preset) {
            if (in_array($i, array_map('intval', $data['presets']), true) && ! $center->classrooms()->where('name', $preset['name'])->exists()) {
                $center->classrooms()->create($preset + ['sort_order' => $center->classrooms()->count()]);
                $added++;
            }
        }

        return back()->with('status', $added ? "Đã thêm {$added} nhóm lớp." : 'Các nhóm đã chọn đều đã có.');
    }

    public function store(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $data = $this->validated($request);

        $center->classrooms()->create($data + ['sort_order' => $center->classrooms()->count()]);

        return back()->with('status', 'Đã thêm lớp "'.$data['name'].'".');
    }

    public function update(Request $request, int $classroom): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $classroom = $center->classrooms()->findOrFail($classroom);
        $classroom->update($this->validated($request));

        return back()->with('status', 'Đã cập nhật lớp.');
    }

    public function destroy(Request $request, int $classroom): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $classroom = $center->classrooms()->findOrFail($classroom);
        $classroom->delete(); // các bé trong lớp tự về "chưa xếp lớp" (nullOnDelete)

        return back()->with('status', 'Đã xoá lớp "'.$classroom->name.'". Các bé trong lớp chuyển sang "chưa xếp lớp".');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:80',
            'age_label' => 'nullable|string|max:40',
            'capacity' => 'nullable|integer|min:0|max:500',
            'note' => 'nullable|string|max:255',
        ]);
    }
}
