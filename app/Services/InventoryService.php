<?php

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Kho: nhập / xuất / kiểm kê, giá vốn bình quân gia quyền theo từng vật tư.
 * Mọi thay đổi tồn kho đều đi qua đây (khoá dòng để tránh ghi đè khi 2 người cùng thao tác).
 */
class InventoryService
{
    public function stockIn(InventoryItem $item, float $quantity, float $totalCost, ?string $supplier, ?string $note, Carbon $when, ?User $user): StockMovement
    {
        return DB::transaction(function () use ($item, $quantity, $totalCost, $supplier, $note, $when, $user) {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            $currentStock = (float) $locked->current_stock;
            $positiveStock = max(0.0, $currentStock);
            $unitCost = $quantity > 0 ? $totalCost / $quantity : 0.0;

            // Giá vốn BQ chỉ tính trên phần tồn dương (tồn âm do ghi thiếu nhập không kéo giá).
            $locked->avg_cost = round((($positiveStock * (float) $locked->avg_cost) + $totalCost) / ($positiveStock + $quantity), 2);
            $locked->current_stock = round($currentStock + $quantity, 3);
            $locked->save();

            return StockMovement::create([
                'center_id' => $locked->center_id,
                'item_id' => $locked->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => round($unitCost, 2),
                'total_cost' => round($totalCost, 2),
                'supplier' => $supplier,
                'note' => $note,
                'occurred_at' => $when,
                'created_by' => $user?->id,
            ]);
        });
    }

    /**
     * Xuất kho cho 1 bé (gọi từ DiaryService). Trả về [chi phí, tồn sau khi xuất].
     * Cho phép tồn âm (người dùng quên ghi nhập) — nơi gọi tự cảnh báo.
     *
     * @return array{0: float, 1: float}
     */
    public function consume(InventoryItem $item, float $quantity, Carbon $when, ?User $user, ?int $childId, ?int $entryId, ?string $note = null): array
    {
        $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

        $cost = round($quantity * (float) $locked->avg_cost, 2);
        $locked->current_stock = round((float) $locked->current_stock - $quantity, 3);
        $locked->save();

        StockMovement::create([
            'center_id' => $locked->center_id,
            'item_id' => $locked->id,
            'type' => 'out',
            'quantity' => -$quantity,
            'unit_cost' => $locked->avg_cost,
            'total_cost' => $cost,
            'child_id' => $childId,
            'entry_id' => $entryId,
            'note' => $note,
            'occurred_at' => $when,
            'created_by' => $user?->id,
        ]);

        return [$cost, (float) $locked->current_stock];
    }

    /** Kiểm kê: đặt tồn kho = số đếm thực tế, ghi lại phần chênh. Không đổi giá vốn BQ. */
    public function adjustTo(InventoryItem $item, float $actual, ?string $note, ?User $user): ?StockMovement
    {
        return DB::transaction(function () use ($item, $actual, $note, $user) {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            $delta = round($actual - (float) $locked->current_stock, 3);
            if ($delta == 0.0) {
                return null;
            }

            $locked->current_stock = round($actual, 3);
            $locked->save();

            return StockMovement::create([
                'center_id' => $locked->center_id,
                'item_id' => $locked->id,
                'type' => 'adjust',
                'quantity' => $delta,
                'unit_cost' => $locked->avg_cost,
                'total_cost' => round(abs($delta) * (float) $locked->avg_cost, 2),
                'note' => $note ?: 'Kiểm kê điều chỉnh',
                'occurred_at' => now(),
                'created_by' => $user?->id,
            ]);
        });
    }

    /** Hoàn lại tồn kho khi XOÁ 1 ghi nhận nhật ký có tiêu hao vật tư. */
    public function restoreForEntry(DiaryEntry $entry): void
    {
        $movements = StockMovement::query()->where('entry_id', $entry->id)->where('type', 'out')->get();

        foreach ($movements as $movement) {
            $item = InventoryItem::withTrashed()->whereKey($movement->item_id)->lockForUpdate()->first();

            if ($item) {
                $item->current_stock = round((float) $item->current_stock + abs((float) $movement->quantity), 3);
                $item->save();
            }

            $movement->delete();
        }
    }
}
