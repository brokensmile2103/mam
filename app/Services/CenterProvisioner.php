<?php

namespace App\Services;

use App\Models\Center;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tạo nhóm trẻ đầu tiên cho 1 chủ — dùng cho trình cài đặt và việc cấp
 * nhóm trẻ riêng cho Super Admin (Super Admin cũng dùng được mọi tính năng như
 * chủ nhóm trẻ). Bản đã cài từ trước (Super Admin chưa có nhóm trẻ nào) sẽ được
 * tạo tự động ở lần đăng nhập đầu tiên.
 */
class CenterProvisioner
{
    public function createFor(User $owner, string $name): Center
    {
        $center = Center::forceCreate([
            'owner_id' => $owner->id,
            'name' => $name,
            'is_active' => true,
        ]);

        $center->staff()->attach($owner->id);

        return $center;
    }

    /** Chỉ tạo khi Super Admin hoàn toàn chưa sở hữu nhóm trẻ nào (nhóm trẻ đang bị khoá vẫn tính là có). */
    public function ensureFor(User $user): ?Center
    {
        if (! $user->isPlatformAdmin()) {
            return null;
        }

        return $user->ownedCenters()->orderBy('id')->first()
            ?? DB::transaction(fn () => $this->createFor($user, 'Nhóm trẻ của '.$user->name));
    }
}
