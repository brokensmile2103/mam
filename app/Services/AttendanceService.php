<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Center;
use App\Models\Child;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Điểm danh: có mặt / nghỉ có phép / nghỉ không phép + giờ đến, giờ về, người đón. */
class AttendanceService
{
    /** Ghi/đổi trạng thái của 1 bé trong 1 ngày (1 bé chỉ có 1 dòng điểm danh mỗi ngày). */
    public function mark(Center $center, Child $child, Carbon $date, string $status, ?User $user, ?string $note = null): Attendance
    {
        abort_unless(array_key_exists($status, config('mam.attendance_statuses')), 422);
        abort_unless((int) $child->center_id === (int) $center->id, 404);

        if (! $child->isOnRollAt($date)) {
            throw ValidationException::withMessages(['date' => $date->lt($child->enrolled_on)
                ? $child->displayName().' nhập học từ '.$child->enrolled_on->format('d/m/Y').' — không điểm danh được ngày trước đó (sửa ngày nhập học ở hồ sơ bé nếu cần).'
                : $child->displayName().' đã nghỉ học từ '.$child->left_on?->format('d/m/Y').'.']);
        }

        try {
            return $this->saveMark($center, $child, $date, $status, $user, $note);
        } catch (UniqueConstraintViolationException) {
            // 2 request cùng lúc (bấm 2 lần khi mạng chậm) cùng tạo dòng cho 1 bé/1 ngày → dòng kia đã có, cập nhật lại nó.
            return $this->saveMark($center, $child, $date, $status, $user, $note);
        }
    }

    private function saveMark(Center $center, Child $child, Carbon $date, string $status, ?User $user, ?string $note): Attendance
    {
        $row = Attendance::query()->where('child_id', $child->id)->whereDate('date', $date->toDateString())->first()
            ?? new Attendance(['child_id' => $child->id, 'date' => $date->toDateString()]);
        $row->center_id = $center->id;
        $row->status = $status;
        $row->created_by ??= $user?->id;

        if ($status === 'co_mat') {
            // Điểm danh đúng ngày hôm nay → tự ghi giờ đến (nếu chưa có).
            if (! $row->check_in && $date->isToday()) {
                $row->check_in = now()->format('H:i');
            }
        } else {
            $row->check_in = null;
            $row->check_out = null;
            $row->picked_up_by = null;
        }

        if ($note !== null) {
            $row->note = $note ?: null;
        }

        $row->save();

        return $row;
    }

    /** Đánh dấu "có mặt" cho mọi bé đang học CHƯA được điểm danh trong ngày. Trả về số bé được đánh dấu. */
    public function markAllPresent(Center $center, Collection $children, Carbon $date, ?User $user): int
    {
        $done = Attendance::query()->where('center_id', $center->id)->whereDate('date', $date)->pluck('child_id')->map(fn ($id) => (int) $id)->all();
        $count = 0;

        foreach ($children as $child) {
            if (! in_array((int) $child->id, $done, true) && $child->isOnRollAt($date)) {
                $this->mark($center, $child, $date, 'co_mat', $user);
                $count++;
            }
        }

        return $count;
    }

    /** Ghi giờ về + người đón. */
    public function checkOut(Attendance $row, ?string $time, ?string $pickedUpBy): Attendance
    {
        abort_unless($row->isPresent(), 422, 'Bé chưa được điểm danh có mặt.');

        $time = $time ?: now()->format('H:i');

        if ($row->check_in && $time < substr($row->check_in, 0, 5)) {
            throw ValidationException::withMessages(['check_out' => 'Giờ về ('.$time.') phải sau giờ đến ('.substr($row->check_in, 0, 5).').']);
        }

        $row->check_out = $time;
        $row->picked_up_by = $pickedUpBy ?: null;
        $row->save();

        return $row;
    }

    /** Số ngày có mặt của từng bé trong 1 tháng. @return array<int, int> child_id => số ngày */
    public function presentDays(Center $center, Carbon $month): array
    {
        return Attendance::query()->where('center_id', $center->id)->where('status', 'co_mat')
            ->whereBetween('date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->selectRaw('child_id, COUNT(*) as days')->groupBy('child_id')->pluck('days', 'child_id')
            ->map(fn ($d) => (int) $d)->all();
    }
}
