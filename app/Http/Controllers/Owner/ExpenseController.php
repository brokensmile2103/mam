<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Chi phí: thuê nhà, lương cô, đi chợ, điện nước, đồ dùng... */
class ExpenseController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request, true);
        $month = parse_month($request->query('thang'));

        $base = $center->expenses()->whereBetween('occurred_on', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()]);

        return view('expenses.index', [
            'center' => $center,
            'month' => $month,
            'expenses' => (clone $base)->latest('occurred_on')->latest('id')->get(),
            'total' => (float) (clone $base)->sum('amount'),
            'byCategory' => (clone $base)->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(config('mam.expense_categories')))],
            'amount' => 'required|numeric|gt:0|max:100000000000',
            'occurred_on' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:255',
        ]);

        $center->expenses()->create($data + ['created_by' => $request->user()->id]);

        return redirect()->to($this->safeReturn($request, route('expenses.index')))->with('status', 'Đã ghi nhận chi phí '.money($data['amount']).'đ.');
    }

    public function destroy(Request $request, int $expense): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $center->expenses()->findOrFail($expense)->delete();

        return back()->with('status', 'Đã xoá khoản chi.');
    }
}
