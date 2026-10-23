<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Child;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Học phí THEO NGÀY (gửi ngày): học phí = số ngày có mặt × đơn giá/ngày; gửi tháng & gửi ngày dùng song song;
 * phiếu sửa tay không bị "Cập nhật phiếu" ghi đè; tính lại 1 phiếu; phiếu báo + trang phụ huynh hiện đúng.
 */
class DailyTuitionTest extends TestCase
{
    use RefreshDatabase;

    private bool $createdLock = false;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-20 10:00:00'); // Thứ ba

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

    private function as(User $user, Center $center): self
    {
        return $this->actingAs($user)->withSession(['current_center_id' => $center->id]);
    }

    private function present(User $owner, Center $center, Child $child, array $days, string $status = 'co_mat'): void
    {
        foreach ($days as $d) {
            $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $child->id, 'date' => '2026-10-'.str_pad((string) $d, 2, '0', STR_PAD_LEFT), 'status' => $status])
                ->assertSessionHasNoErrors();
        }
    }

    public function test_daily_and_monthly_tuition_side_by_side(): void
    {
        $owner = User::forceCreate(['name' => 'Cô Trân', 'phone' => '0900000001', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $center = Center::forceCreate(['owner_id' => $owner->id, 'name' => 'Nhóm Trẻ Huyền Trân', 'is_active' => true]);
        $center->staff()->attach($owner->id);

        // Cài đặt: mặc định gửi ngày 150k/ngày, tiền ăn 30k/ngày, gửi tháng 2,5tr.
        $this->as($owner, $center)->put(route('settings.billing'), [
            'tuition_mode' => 'daily', 'tuition_default' => '2500000', 'daily_fee_default' => '150000', 'meal_rate_default' => '30000',
        ])->assertSessionHasNoErrors();
        $center->refresh();
        $this->assertSame('daily', $center->tuitionMode());
        $this->assertEqualsWithDelta(150000, (float) $center->daily_fee_default, 0.01);
        $this->as($owner, $center)->put(route('settings.billing'), ['tuition_mode' => 'weekly'])->assertSessionHasErrors('tuition_mode');

        $kid = function (array $data) use ($owner, $center): Child {
            $this->as($owner, $center)->post(route('children.store'), $data + [
                'birth_date' => '2025-01-15', 'enrolled_on' => '2026-09-01', 'tuition_mode' => '',
            ])->assertSessionHasNoErrors();

            return Child::query()->latest('id')->firstOrFail();
        };

        $an = $kid(['full_name' => 'Nguyễn Bình An']);                                                          // theo nhóm → gửi ngày
        $bo = $kid(['full_name' => 'Trần Gia Bảo', 'tuition_mode' => 'monthly', 'tuition_fee' => '2000000']);  // gửi tháng riêng
        $ca = $kid(['full_name' => 'Lê Minh Cường']);                                                           // gửi ngày, cả tháng không đi
        $du = $kid(['full_name' => 'Phạm Dương', 'daily_fee' => '120000', 'meal_rate' => '0']);                 // gửi ngày giá riêng, đã gồm ăn

        $this->assertNull($an->tuition_mode);
        $this->assertTrue($an->isDailyTuition());
        $this->assertFalse($bo->isDailyTuition());

        $this->present($owner, $center, $an, [1, 2, 5]);
        $this->present($owner, $center, $an, [6], 'vang_phep'); // nghỉ phép không tính tiền
        $this->present($owner, $center, $bo, [1, 2]);
        $this->present($owner, $center, $du, [7, 8]);

        // Trang học phí: chỉ 3 bé "cần phiếu" (Cường gửi ngày chưa đi ngày nào).
        $this->as($owner, $center)->get(route('invoices.index', ['thang' => '2026-10']))->assertOk()->assertSee('3 bé chưa có phiếu');

        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => '2026-10'])->assertSessionHasNoErrors();
        $this->assertSame(3, Invoice::query()->count());
        $this->assertNull(Invoice::query()->where('child_id', $ca->id)->first());

        $anInv = Invoice::query()->where('child_id', $an->id)->firstOrFail();
        $this->assertTrue($anInv->isDaily());
        $this->assertSame(3, $anInv->tuition_days);
        $this->assertEqualsWithDelta(450000, (float) $anInv->tuition_amount, 0.01);
        $this->assertEqualsWithDelta(450000 + 90000, (float) $anInv->total_amount, 0.01);

        $boInv = Invoice::query()->where('child_id', $bo->id)->firstOrFail();
        $this->assertFalse($boInv->isDaily());
        $this->assertSame(0, $boInv->tuition_days);
        $this->assertEqualsWithDelta(2000000 + 60000, (float) $boInv->total_amount, 0.01);

        $duInv = Invoice::query()->where('child_id', $du->id)->firstOrFail();
        $this->assertEqualsWithDelta(240000, (float) $duInv->total_amount, 0.01);

        // Đi thêm 1 ngày → bấm "Cập nhật phiếu" là phiếu gửi ngày tự tăng.
        $this->present($owner, $center, $an, [9]);
        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => '2026-10'])->assertSessionHasNoErrors();
        $this->assertSame(4, $anInv->fresh()->tuition_days);
        $this->assertEqualsWithDelta(4 * 180000, (float) $anInv->fresh()->total_amount, 0.01);

        // Sửa tay (bớt 1 ngày học vì bé về sớm) → phiếu được đánh dấu, "Cập nhật phiếu" KHÔNG ghi đè.
        $this->as($owner, $center)->put(route('invoices.update', $anInv->id), [
            'tuition_mode' => 'daily', 'tuition_days' => 3, 'tuition_rate' => '150000', 'meal_days' => 4, 'meal_rate' => '30000',
        ])->assertSessionHasNoErrors();
        $anInv->refresh();
        $this->assertNotNull($anInv->edited_at);
        $this->assertEqualsWithDelta(450000 + 120000, (float) $anInv->total_amount, 0.01);

        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => '2026-10'])->assertSessionHasNoErrors()
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Giữ nguyên 1 phiếu'));
        $this->assertSame(3, $anInv->fresh()->tuition_days);

        // "Tính lại theo điểm danh" → về đúng điểm danh, bỏ đánh dấu sửa tay.
        $this->as($owner, $center)->post(route('invoices.recalculate', $anInv->id))->assertSessionHasNoErrors();
        $anInv->refresh();
        $this->assertSame(4, $anInv->tuition_days);
        $this->assertNull($anInv->edited_at);

        // Lưu form mà không đổi gì → không bị coi là sửa tay.
        $this->as($owner, $center)->put(route('invoices.update', $anInv->id), [
            'tuition_mode' => 'daily', 'tuition_days' => 4, 'tuition_rate' => '150000', 'meal_days' => 4, 'meal_rate' => '30000',
        ])->assertSessionHasNoErrors();
        $this->assertNull($anInv->fresh()->edited_at);

        // Phiếu báo Zalo + trang chi tiết hiện cách tính theo ngày.
        $html = html_entity_decode($this->as($owner, $center)->get(route('invoices.show', $anInv->id))->assertOk()->getContent());
        $this->assertStringContainsString('Học phí: 4 ngày × 150.000đ = 600.000đ', $html);
        $this->assertStringContainsString('Học phí (4 ngày × 150.000đ)', $html);

        // Đổi phiếu gửi tháng của Bảo sang gửi ngày ngay trên phiếu.
        $this->as($owner, $center)->put(route('invoices.update', $boInv->id), [
            'tuition_mode' => 'daily', 'tuition_days' => 2, 'tuition_rate' => '100000', 'meal_days' => 2, 'meal_rate' => '30000',
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(260000, (float) $boInv->fresh()->total_amount, 0.01);
        $this->as($owner, $center)->put(route('invoices.update', $boInv->id), [
            'tuition_mode' => 'daily', 'meal_days' => 2, 'meal_rate' => '30000',
        ])->assertSessionHasErrors(['tuition_days', 'tuition_rate']);

        // Phiếu cũ không gửi tuition_mode (form cũ) vẫn sửa được theo cách tính hiện tại của phiếu.
        $this->as($owner, $center)->put(route('invoices.update', $duInv->id), [
            'tuition_days' => 1, 'tuition_rate' => '120000', 'meal_days' => 1, 'meal_rate' => '0',
        ])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(120000, (float) $duInv->fresh()->total_amount, 0.01);

        // Đã thu tiền → không cho giảm phiếu xuống dưới số đã thu, cũng không cho "tính lại" làm vậy.
        $this->as($owner, $center)->post(route('invoices.pay', $anInv->id), ['amount' => '600000', 'paid_on' => '2026-10-20', 'method' => 'tien_mat'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->put(route('invoices.update', $anInv->id), [
            'tuition_mode' => 'daily', 'tuition_days' => 1, 'tuition_rate' => '150000', 'meal_days' => 1, 'meal_rate' => '30000',
        ])->assertSessionHasErrors('total');
        Child::query()->whereKey($an->id)->update(['daily_fee' => 10000]);
        $this->as($owner, $center)->post(route('invoices.recalculate', $anInv->id))->assertSessionHasErrors('total');
        $this->assertEqualsWithDelta(720000, (float) $anInv->fresh()->total_amount, 0.01);

        // Trang phụ huynh hiện dòng học phí theo ngày.
        $this->as($owner, $center)->post(route('children.share', $an->id));
        auth()->logout();
        $page = html_entity_decode($this->get(route('parent.show', $an->fresh()->parent_token))->assertOk()->getContent());
        $this->assertStringContainsString('Học phí (4 ngày × 150.000đ)', $page);

        // Hồ sơ bé: chọn lại "theo nhóm" thì xoá cách tính riêng.
        $this->as($owner, $center)->put(route('children.update', $bo->id), [
            'full_name' => 'Trần Gia Bảo', 'birth_date' => '2025-01-15', 'enrolled_on' => '2026-09-01', 'tuition_mode' => '', 'tuition_fee' => '',
        ])->assertSessionHasNoErrors();
        $this->assertNull($bo->fresh()->tuition_mode);
        $this->assertTrue($bo->fresh()->isDailyTuition());
        $this->as($owner, $center)->get(route('children.show', $bo->id))->assertOk()->assertSee('gửi ngày', false);
        $this->as($owner, $center)->get(route('children.edit', $bo->id))->assertOk();
    }

    public function test_old_invoices_stay_monthly_after_upgrade(): void
    {
        $owner = User::forceCreate(['name' => 'A', 'phone' => '0900000009', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $center = Center::forceCreate(['owner_id' => $owner->id, 'name' => 'N', 'is_active' => true, 'tuition_default' => 2500000]);
        $child = new Child(['full_name' => 'Bé Cũ', 'birth_date' => '2025-01-01', 'enrolled_on' => '2026-01-01']);
        $child->center_id = $center->id;
        $child->status = 'active';
        $child->save();

        // Phiếu tạo trước khi có cột mới: chỉ có các trường cũ.
        $inv = Invoice::query()->create(['center_id' => $center->id, 'child_id' => $child->id, 'month' => '2026-09', 'tuition_amount' => 2500000, 'meal_days' => 10, 'meal_rate' => 30000, 'meal_amount' => 300000, 'total_amount' => 2800000])->fresh();

        $this->assertSame('monthly', $inv->tuition_mode);
        $this->assertFalse($inv->isDaily());
        $inv->recompute();
        $this->assertEqualsWithDelta(2800000, (float) $inv->total_amount, 0.01);
        $this->assertSame('monthly', $center->fresh()->tuitionMode());
        $this->assertFalse($child->fresh()->isDailyTuition());
    }
}
