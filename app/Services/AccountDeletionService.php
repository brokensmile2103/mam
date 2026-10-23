<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CLOUD — chủ nhóm TỰ XOÁ tài khoản: xoá toàn bộ nhóm trẻ sở hữu (cascade ở tầng
 * database lo hết dữ liệu con: lớp, hồ sơ trẻ, điểm danh, nhật ký, kho, học phí, chi phí, lịch định kỳ...),
 * rồi các thành viên "mồ côi" (không còn thuộc nhóm trẻ nào khác), cuối cùng là chính chủ nhóm.
 * Các cột created_by đều nullOnDelete nên xoá user không bao giờ vướng khoá ngoại.
 */
class AccountDeletionService
{
    public function delete(User $owner): void
    {
        DB::transaction(function () use ($owner) {
            $candidateStaffIds = collect();

            foreach ($owner->ownedCenters()->get() as $center) {
                $candidateStaffIds = $candidateStaffIds->merge(
                    $center->staff()->where('role', 'staff')->pluck('users.id')
                );

                $center->delete();
            }

            foreach ($candidateStaffIds->unique() as $staffId) {
                $staff = User::find($staffId);

                if ($staff && ! $staff->centers()->exists()) {
                    $staff->delete();
                }
            }

            $owner->delete();
        });
    }
}
