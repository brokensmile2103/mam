<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Concerns\ResolvesCurrentCenter;
use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Kho thức ăn / thuốc / vaccine / vật tư. Xem: mọi thành viên; nhập/sửa: chủ nhóm. */
class InventoryController extends Controller
{
    use ResolvesCurrentCenter;

    public function index(Request $request): View
    {
        $center = $this->currentCenter($request);

        $category = array_key_exists((string) $request->query('category'), config('mam.item_categories')) ? $request->query('category') : null;

        $items = $center->items()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('category')->orderBy('name')->get();

        // Mức dùng TB/ngày trong 14 ngày gần nhất → ước tính "đủ dùng bao nhiêu ngày".
        $usage = \App\Models\StockMovement::query()
            ->where('center_id', $center->id)->where('type', 'out')
            ->where('occurred_at', '>=', now()->subDays(14))
            ->selectRaw('item_id, SUM(-quantity) as used')->groupBy('item_id')->pluck('used', 'item_id')
            ->map(fn ($used) => (float) $used / 14);

        return view('inventory.index', [
            'center' => $center,
            'items' => $items,
            'category' => $category,
            'usage' => $usage,
            'trashed' => $request->user()->isOwner() ? $center->items()->onlyTrashed()->orderBy('name')->get() : collect(),
            'isOwner' => $request->user()->isOwner(),
        ]);
    }

    public function history(Request $request, int $item): View
    {
        $center = $this->currentCenter($request);
        $item = $center->items()->withTrashed()->findOrFail($item);

        $movements = $item->movements()->with('creator')->latest('occurred_at')->latest('id')->paginate(30);

        return view('inventory.history', [
            'center' => $center,
            'item' => $item,
            'movements' => $movements,
            'isOwner' => $request->user()->isOwner(),
        ]);
    }

    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $center = $this->currentCenter($request, true);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => ['required', Rule::in(array_keys(config('mam.item_categories')))],
            'unit' => 'required|string|max:20',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'initial_quantity' => 'nullable|numeric|min:0',
            'initial_cost' => 'nullable|numeric|min:0',
        ]);

        $item = $center->items()->create([
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'],
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 0,
        ]);

        if (! empty($data['initial_quantity'])) {
            $inventory->stockIn($item, (float) $data['initial_quantity'], (float) ($data['initial_cost'] ?? 0), null, 'Tồn kho ban đầu', now(), $request->user());
        }

        return back()->with('status', 'Đã thêm "'.$item->name.'" vào kho.');
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $item = $center->items()->findOrFail($item);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => ['required', Rule::in(array_keys(config('mam.item_categories')))],
            'unit' => 'required|string|max:20',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $item->update([
            'name' => $data['name'],
            'category' => $data['category'],
            'unit' => $data['unit'],
            'low_stock_threshold' => $data['low_stock_threshold'] ?? 0,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Đã cập nhật "'.$item->name.'".');
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $item = $center->items()->findOrFail($item);
        $item->delete();

        return back()->with('status', 'Đã xoá "'.$item->name.'" (có thể khôi phục lại).');
    }

    public function restore(Request $request, int $item): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $item = $center->items()->onlyTrashed()->findOrFail($item);
        $item->restore();

        return back()->with('status', 'Đã khôi phục "'.$item->name.'".');
    }

    public function stockIn(Request $request, InventoryService $inventory, int $item): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $item = $center->items()->findOrFail($item);

        $data = $request->validate([
            'quantity' => 'required|numeric|gt:0|max:100000000',
            'total_cost' => 'required|numeric|min:0|max:100000000000',
            'supplier' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255',
            'occurred_on' => 'nullable|date|before_or_equal:today',
        ]);

        $when = ! empty($data['occurred_on']) ? Carbon::parse($data['occurred_on'])->setTime(now()->hour, now()->minute) : now();

        $inventory->stockIn($item, (float) $data['quantity'], (float) $data['total_cost'], $data['supplier'] ?? null, $data['note'] ?? null, $when, $request->user());

        return back()->with('status', 'Đã nhập '.quantity($data['quantity']).' '.$item->unit.' '.$item->name.'.');
    }

    public function adjust(Request $request, InventoryService $inventory, int $item): RedirectResponse
    {
        $center = $this->currentCenter($request, true);
        $item = $center->items()->findOrFail($item);

        $data = $request->validate([
            'actual' => 'required|numeric|min:0|max:100000000',
            'note' => 'nullable|string|max:255',
        ]);

        $movement = $inventory->adjustTo($item, (float) $data['actual'], $data['note'] ?? null, $request->user());

        return back()->with('status', $movement ? 'Đã kiểm kê "'.$item->name.'": tồn thực tế '.quantity($data['actual']).' '.$item->unit.'.' : 'Số liệu khớp, không cần điều chỉnh.');
    }
}
