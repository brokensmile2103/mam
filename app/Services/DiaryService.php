<?php

namespace App\Services;

use App\Models\Center;
use App\Models\DiaryEntry;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ghi "Sổ nhật ký" cho 1 hoặc nhiều bé cùng lúc (VD: cả lớp ăn trưa, cả lớp ngủ trưa).
 * Là NƠI DUY NHẤT tạo/xoá DiaryEntry để tồn kho (sữa, tã...) luôn khớp với nhật ký.
 */
class DiaryService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * $fields: meal, level, amount_ml, sleep_from, sleep_to, temperature, weight_kg, height_cm (tuỳ loại).
     * $itemQtyEach: số lượng vật tư dùng cho MỖI bé (VD: 1 tã/bé, 180 ml sữa/bé).
     *
     * @param  Collection<int, \App\Models\Child>  $children
     * @return array{entries: Collection<int, DiaryEntry>, warnings: list<string>}
     */
    public function log(
        Center $center,
        ?User $user,
        Collection $children,
        string $type,
        Carbon $when,
        array $fields = [],
        ?InventoryItem $item = null,
        ?float $itemQtyEach = null,
        ?string $note = null,
    ): array {
        $meta = config('mam.entry_types.'.$type);
        abort_unless($meta, 422, 'Loại ghi nhận không hợp lệ.');

        $useItem = $meta['item'] && $item && $item->center_id === $center->id && $itemQtyEach && $itemQtyEach > 0;
        $data = $this->normalizeFields($type, $fields);
        $entries = collect();
        $warnings = [];

        DB::transaction(function () use ($center, $user, $children, $type, $when, $data, $item, $itemQtyEach, $note, $useItem, &$entries, &$warnings) {
            foreach ($children->values() as $child) {
                $entry = DiaryEntry::create($data + [
                    'center_id' => $center->id,
                    'child_id' => $child->id,
                    'type' => $type,
                    'occurred_at' => $when,
                    'item_id' => $useItem ? $item->id : null,
                    'item_quantity' => $useItem ? $itemQtyEach : null,
                    'note' => $note,
                    'created_by' => $user?->id,
                ]);

                if ($useItem) {
                    [$cost, $stockAfter] = $this->inventory->consume($item, $itemQtyEach, $when, $user, $child->id, $entry->id);
                    $entry->update(['cost' => $cost]);

                    if ($stockAfter < 0) {
                        $warnings[$item->id] = 'Kho "'.$item->name.'" đang âm — nhớ ghi nhập kho để số liệu chính xác.';
                    }
                }

                $entries->push($entry);
            }
        });

        return ['entries' => $entries, 'warnings' => array_values($warnings)];
    }

    /** Xoá 1 ghi nhận (hoàn lại tồn kho nếu có dùng vật tư). */
    public function delete(DiaryEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $this->inventory->restoreForEntry($entry);
            $entry->delete();
        });
    }

    /** Chỉ giữ các trường đúng với loại ghi nhận, tính sẵn số phút ngủ. */
    public function normalizeFields(string $type, array $f): array
    {
        $out = [
            'meal' => null, 'level' => null, 'amount_ml' => null, 'sleep_from' => null, 'sleep_to' => null,
            'sleep_minutes' => null, 'temperature' => null, 'weight_kg' => null, 'height_cm' => null,
        ];

        switch ($type) {
            case 'an':
                $out['meal'] = $f['meal'] ?? null;
                $out['level'] = $f['level'] ?? null;
                break;
            case 'sua':
                $out['amount_ml'] = isset($f['amount_ml']) && $f['amount_ml'] !== '' ? (int) $f['amount_ml'] : null;
                break;
            case 'ngu':
                $out['sleep_from'] = $f['sleep_from'] ?? null;
                $out['sleep_to'] = $f['sleep_to'] ?? null;
                $out['sleep_minutes'] = self::sleepMinutes($out['sleep_from'], $out['sleep_to']);
                break;
            case 've_sinh':
                $out['level'] = $f['toilet'] ?? $f['level'] ?? null;
                break;
            case 'suc_khoe':
                $out['temperature'] = isset($f['temperature']) && $f['temperature'] !== '' ? round((float) $f['temperature'], 1) : null;
                break;
            case 'can_do':
                $out['weight_kg'] = isset($f['weight_kg']) && $f['weight_kg'] !== '' ? round((float) $f['weight_kg'], 2) : null;
                $out['height_cm'] = isset($f['height_cm']) && $f['height_cm'] !== '' ? round((float) $f['height_cm'], 1) : null;
                break;
        }

        return $out;
    }

    /** Số phút ngủ từ giờ bắt đầu → giờ dậy (qua nửa đêm vẫn đúng). */
    public static function sleepMinutes(?string $from, ?string $to): ?int
    {
        if (! $from || ! $to || ! preg_match('/^\d{2}:\d{2}/', $from) || ! preg_match('/^\d{2}:\d{2}/', $to)) {
            return null;
        }

        [$fh, $fm] = array_map('intval', explode(':', substr($from, 0, 5)));
        [$th, $tm] = array_map('intval', explode(':', substr($to, 0, 5)));
        $minutes = ($th * 60 + $tm) - ($fh * 60 + $fm);

        return $minutes < 0 ? $minutes + 1440 : $minutes;
    }
}
