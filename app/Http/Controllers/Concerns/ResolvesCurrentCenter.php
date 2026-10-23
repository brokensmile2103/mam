<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Center;
use Illuminate\Http\Request;

/**
 * Dùng chung cho MỌI controller thao tác trên "nhóm trẻ đang chọn" (session current_center_id).
 * KHÔNG BAO GIỜ tin center_id từ client — luôn lấy từ session rồi đối chiếu quyền với DB
 * ở mọi request. Mọi truy vấn theo id (trẻ, kho, nhật ký...) đều phải đi qua
 * $center->relation()->findOrFail($id) để không đọc chéo dữ liệu giữa các cơ sở.
 */
trait ResolvesCurrentCenter
{
    protected function currentCenter(Request $request, bool $onlyOwner = false): Center
    {
        $user = $request->user();
        $id = $request->session()->get('current_center_id');
        $center = $id ? Center::find($id) : null;

        abort_if($onlyOwner && ! $user->isOwner(), 403, 'Chỉ chủ nhóm mới được vào mục này.');

        if (! $center || ! $center->is_active || ! $this->canAccessCenter($center, $user->id, $user->isOwner(), $onlyOwner)) {
            // Tự chữa: chọn lại nhóm trẻ đầu tiên còn hoạt động mà người dùng được phép vào
            // (VD: nhóm trẻ đang chọn vừa bị khoá nhưng chủ nhóm còn nhóm trẻ khác).
            // Super Admin của bản cài cũ chưa có nhóm trẻ riêng → tạo tự động.
            app(\App\Services\CenterProvisioner::class)->ensureFor($user);

            $center = $user->isOwner()
                ? $user->ownedCenters()->where('is_active', true)->orderBy('id')->first()
                : $user->centers()->where('centers.is_active', true)->orderBy('centers.id')->first();

            abort_unless($center, 403, 'Nhóm trẻ đang tạm khoá hoặc bạn chưa thuộc nhóm trẻ nào đang hoạt động. Vui lòng liên hệ hỗ trợ.');

            $request->session()->put('current_center_id', $center->id);
        }

        return $center;
    }

    private function canAccessCenter(Center $center, int $userId, bool $isOwner, bool $onlyOwner): bool
    {
        if ($isOwner) {
            return $center->owner_id === $userId;
        }

        return ! $onlyOwner && $center->staff()->where('users.id', $userId)->exists();
    }

    /** Chỉ cho quay lại URL nội bộ (chống open-redirect qua trường return_to). */
    protected function safeReturn(Request $request, string $default): string
    {
        $to = (string) $request->input('return_to', '');

        return ($to !== '' && str_starts_with($to, '/') && ! str_starts_with($to, '//')) ? $to : $default;
    }
}
