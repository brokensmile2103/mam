<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Quản lý nhiều nhóm trẻ (nếu chủ nhóm có nhiều cơ sở) và chuyển qua lại. */
class CenterController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $current = $this->currentCenter($request, true);

        $centers = $request->user()->ownedCenters()
            ->withCount(['children as active_children_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount('classrooms')
            ->orderBy('id')->get();

        return view('centers.index', ['center' => $current, 'centers' => $centers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $center = new \App\Models\Center($data);
        $center->owner_id = $user->id;
        $center->is_active = true;
        $center->save();
        $center->staff()->attach($user->id);

        $request->session()->put('current_center_id', $center->id);

        return redirect()->route('today')->with('status', 'Đã tạo nhóm trẻ "'.$center->name.'" và chuyển sang nhóm trẻ này.');
    }

    public function update(Request $request, int $center): RedirectResponse
    {
        $center = $request->user()->ownedCenters()->findOrFail($center);

        $center->update($request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
        ]));

        return back()->with('status', 'Đã cập nhật nhóm trẻ.');
    }

    public function switch(Request $request, int $center): RedirectResponse
    {
        $center = $request->user()->ownedCenters()->where('is_active', true)->findOrFail($center);
        $request->session()->put('current_center_id', $center->id);

        return redirect()->route('today')->with('status', 'Đang xem nhóm trẻ "'.$center->name.'".');
    }
}
