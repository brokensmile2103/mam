<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Center;
use App\Models\Child;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use App\Services\FormGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Các lỗi logic đã sửa — giữ test để không tái phát. */
class RegressionTest extends TestCase
{
    use RefreshDatabase;

    private bool $createdLock = false;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00');

        if (! file_exists(storage_path('installed.lock'))) {
            file_put_contents(storage_path('installed.lock'), 'test');
            $this->createdLock = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdLock) {
            @unlink(storage_path('installed.lock'));
        }

        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array{0: User, 1: Center} */
    private function owner(): array
    {
        $owner = User::forceCreate(['name' => 'Cô Hoa', 'phone' => '0900000001', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $center = Center::forceCreate(['owner_id' => $owner->id, 'name' => 'Nhóm trẻ Hoa Mai', 'is_active' => true, 'tuition_mode' => 'daily', 'daily_fee_default' => 100000]);
        $center->staff()->attach($owner->id);

        return [$owner, $center];
    }

    private function as(User $user, Center $center): self
    {
        return $this->actingAs($user)->withSession(['current_center_id' => $center->id]);
    }

    private function child(Center $center, string $name, string $enrolled = '2026-10-01'): Child
    {
        $c = new Child(['full_name' => $name, 'birth_date' => '2025-03-01', 'enrolled_on' => $enrolled]);
        $c->center_id = $center->id;
        $c->status = 'active';
        $c->save();

        return $c;
    }

    public function test_child_who_left_mid_month_can_still_get_earlier_days_marked_and_billed(): void
    {
        [$owner, $center] = $this->owner();
        $na = $this->child($center, 'Nguyễn Na');

        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => '2026-10-05', 'status' => 'co_mat'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('children.leave', $na->id), ['left_on' => '2026-10-15'])->assertSessionHasNoErrors();

        // Quên điểm danh ngày 14 → vẫn thấy bé trong danh sách ngày 14 và điểm danh bù được.
        $this->as($owner, $center)->get(route('attendance.index', ['ngay' => '2026-10-14']))->assertOk()->assertSee('Bé Na', false);
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => '2026-10-14', 'status' => 'co_mat'])->assertSessionHasNoErrors();

        // Sau ngày nghỉ: không còn trong danh sách, không điểm danh được.
        $this->as($owner, $center)->get(route('attendance.index', ['ngay' => '2026-10-16']))->assertOk()->assertDontSee('id="be-'.$na->id.'"', false);
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => '2026-10-16', 'status' => 'co_mat'])->assertSessionHasErrors('date');
        $this->as($owner, $center)->post(route('attendance.mark-all'), ['date' => '2026-10-16'])->assertSessionHasNoErrors();
        $this->assertSame(2, Attendance::query()->where('child_id', $na->id)->count());

        // Phiếu tháng vẫn tính đủ 2 ngày cho bé đã nghỉ.
        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => '2026-10'])->assertSessionHasNoErrors();
        $inv = Invoice::query()->where('child_id', $na->id)->firstOrFail();
        $this->assertSame(2, $inv->tuition_days);
        $this->assertEqualsWithDelta(200000, (float) $inv->total_amount, 0.01);
    }

    public function test_attendance_before_enrollment_is_rejected_and_visible_as_error(): void
    {
        [$owner, $center] = $this->owner();
        $bin = $this->child($center, 'Trần Bin', '2026-10-12');

        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $bin->id, 'date' => '2026-10-10', 'status' => 'co_mat'])
            ->assertSessionHasErrors('date');
        $this->assertSame(0, Attendance::query()->count());

        // Trang điểm danh hiển thị lỗi (trước đây lỗi bị nuốt mất).
        $this->as($owner, $center)->from(route('attendance.index'))->followingRedirects()
            ->post(route('attendance.mark'), ['child_id' => $bin->id, 'date' => '2026-10-10', 'status' => 'co_mat'])
            ->assertOk()->assertSee('nhập học từ 12/10/2026', false);
    }

    public function test_check_out_cannot_be_before_check_in_and_attendance_can_be_undone(): void
    {
        [$owner, $center] = $this->owner();
        $na = $this->child($center, 'Nguyễn Na');

        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => '2026-10-20', 'status' => 'co_mat'])->assertSessionHasNoErrors();
        $row = Attendance::query()->firstOrFail();
        $this->assertSame('10:00', $row->checkInLabel());

        $this->as($owner, $center)->post(route('attendance.check-out', $row->id), ['check_out' => '08:00'])->assertSessionHasErrors('check_out');
        $this->assertNull($row->fresh()->check_out);
        $this->as($owner, $center)->post(route('attendance.check-out', $row->id), ['check_out' => '16:30'])->assertSessionHasNoErrors();

        $html = $this->as($owner, $center)->get(route('attendance.index'))->assertOk()->getContent();
        $this->assertStringContainsString(route('attendance.destroy', $row->id), $html);

        $this->as($owner, $center)->delete(route('attendance.destroy', $row->id))->assertSessionHasNoErrors();
        $this->assertSame(0, Attendance::query()->count());
    }

    public function test_marking_twice_keeps_single_row(): void
    {
        [$owner, $center] = $this->owner();
        $na = $this->child($center, 'Nguyễn Na');

        foreach (['co_mat', 'vang_phep', 'co_mat'] as $status) {
            $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => '2026-10-19', 'status' => $status])->assertSessionHasNoErrors();
        }
        $this->assertSame(1, Attendance::query()->count());
        $this->assertSame('co_mat', Attendance::query()->value('status'));
    }

    public function test_leave_date_validation_and_pending_child_tasks_removed(): void
    {
        [$owner, $center] = $this->owner();
        $na = $this->child($center, 'Nguyễn Na', '2026-10-05');

        $this->as($owner, $center)->post(route('children.leave', $na->id), ['left_on' => '2026-10-01'])->assertSessionHasErrors('left_on');
        $this->assertTrue($na->fresh()->isActive());

        $this->as($owner, $center)->post(route('tasks.store'), ['title' => 'Uống thuốc', 'type' => 'thuoc', 'target' => 'child', 'child_id' => $na->id, 'due_date' => '2026-10-22', 'due_time' => '09:00'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('children.leave', $na->id))->assertSessionHasNoErrors();
        $this->assertSame('2026-10-20', $na->fresh()->left_on->toDateString());
        $this->assertSame(0, Task::query()->count());
    }

    public function test_phone_numbers_are_normalized_and_login_is_tolerant(): void
    {
        $this->assertSame('0912345678', normalize_phone(' 0912 345.678 '));
        $this->assertSame('0912345678', normalize_phone('+84 912 345 678'));
        $this->assertSame('0912345678', normalize_phone('(091) 234-5678'));

        $guard = FormGuard::generate('register');
        Carbon::setTestNow(now()->addSeconds(5));
        $this->post(route('register.store'), [
            'center_name' => 'Nhóm trẻ Sen', 'name' => 'Cô Sen', 'phone' => '0912 345 678', 'password' => 'password1', 'password_confirmation' => 'password1', 'terms' => '1',
            'form_ts' => $guard['timestamp'], 'form_sig' => $guard['signature'], 'hp_key' => $guard['honeypot_field'],
        ])->assertRedirect(route('today'));
        $this->assertSame('0912345678', User::query()->where('name', 'Cô Sen')->value('phone'));
        auth()->logout();

        // Đăng nhập gõ kiểu khác vẫn vào được.
        $guard = FormGuard::generate('login');
        Carbon::setTestNow(now()->addSeconds(5));
        $this->post(route('login.attempt'), ['phone' => '0912.345.678', 'password' => 'password1', 'form_ts' => $guard['timestamp'], 'form_sig' => $guard['signature'], 'hp_key' => $guard['honeypot_field']])
            ->assertRedirect(route('today'));
        auth()->logout();

        // Tài khoản cũ lưu kèm dấu cách vẫn đăng nhập được như trước.
        $old = User::forceCreate(['name' => 'Cũ', 'phone' => '0933 111 222', 'password' => Hash::make('password1'), 'role' => 'owner', 'is_active' => true]);
        Center::forceCreate(['owner_id' => $old->id, 'name' => 'Cũ', 'is_active' => true]);
        $guard = FormGuard::generate('login');
        Carbon::setTestNow(now()->addSeconds(5));
        $this->post(route('login.attempt'), ['phone' => '0933 111 222', 'password' => 'password1', 'form_ts' => $guard['timestamp'], 'form_sig' => $guard['signature'], 'hp_key' => $guard['honeypot_field']])
            ->assertRedirect(route('today'));
        auth()->logout();

        // Không tạo được tài khoản trùng chỉ vì khác dấu cách; số rác bị chặn.
        [$owner, $center] = $this->owner();
        $this->as($owner, $center)->post(route('staff.store'), ['name' => 'Cô Lan', 'phone' => '0912-345-678', 'password' => 'password1'])->assertSessionHasErrors('phone');
        $this->as($owner, $center)->post(route('staff.store'), ['name' => 'Cô Lan', 'phone' => 'abc', 'password' => 'password1'])->assertSessionHasErrors('phone');
        $this->as($owner, $center)->post(route('staff.store'), ['name' => 'Cô Lan', 'phone' => '0977 000 111', 'password' => 'password1'])->assertSessionHasNoErrors();
        $this->assertTrue(User::query()->where('phone', '0977000111')->exists());
    }

    public function test_paying_too_much_from_invoice_list_shows_error(): void
    {
        [$owner, $center] = $this->owner();
        $na = $this->child($center, 'Nguyễn Na');
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => '2026-10-19', 'status' => 'co_mat']);
        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => '2026-10']);
        $inv = Invoice::query()->firstOrFail();

        $this->as($owner, $center)->from(route('invoices.index'))
            ->post(route('invoices.pay', $inv->id), ['amount' => '999999', 'paid_on' => '2026-10-20', 'method' => 'tien_mat'])
            ->assertSessionHasErrors('amount');

        $this->as($owner, $center)->from(route('invoices.index'))->followingRedirects()
            ->post(route('invoices.pay', $inv->id), ['amount' => '999999', 'paid_on' => '2026-10-20', 'method' => 'tien_mat'])
            ->assertOk()->assertSee('lớn hơn số còn phải thu', false);
    }
}
