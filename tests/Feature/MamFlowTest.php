<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Child;
use App\Models\DiaryEntry;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Luồng nghiệp vụ chính của Mầm + "quét" mọi trang GET (chủ nhóm, cô giáo, Super Admin) để bắt lỗi 500.
 * Chạy với SQLite (mặc định) hoặc MySQL: DB_CONNECTION=mysql DB_DATABASE=mam_test ... vendor/bin/phpunit
 */
class MamFlowTest extends TestCase
{
    use RefreshDatabase;

    private bool $createdLock = false;

    protected function setUp(): void
    {
        parent::setUp();

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
        $center = Center::forceCreate(['owner_id' => $owner->id, 'name' => 'Nhóm trẻ Hoa Mai', 'is_active' => true, 'tuition_default' => 2500000, 'meal_rate_default' => 35000]);
        $center->staff()->attach($owner->id);

        return [$owner, $center];
    }

    private function as(User $user, Center $center): self
    {
        return $this->actingAs($user)->withSession(['current_center_id' => $center->id]);
    }

    /** 2 lớp, 3 bé, 1 món trong kho. */
    private function seedCenter(User $owner, Center $center): array
    {
        $this->as($owner, $center)->post(route('classrooms.quick-add'), ['presets' => [1, 2]])->assertSessionHasNoErrors();
        [$c1, $c2] = $center->classrooms()->orderBy('id')->get()->all();

        $kid = fn (array $data) => $this->as($owner, $center)->post(route('children.store'), $data + [
            'enrolled_on' => now()->subMonths(2)->toDateString(), 'parent_relation' => 'Mẹ', 'parent_phone' => '0911222333',
        ])->assertSessionHasNoErrors();

        $kid(['full_name' => 'Nguyễn Ngọc An Nhiên', 'nickname' => 'Na', 'gender' => 'nu', 'birth_date' => now()->subMonths(20)->toDateString(), 'classroom_id' => $c1->id, 'allergies' => 'Tôm']);
        $kid(['full_name' => 'Trần Gia Bảo', 'nickname' => 'Bin', 'gender' => 'nam', 'birth_date' => now()->subMonths(30)->toDateString(), 'classroom_id' => $c2->id, 'tuition_fee' => '2000000']);
        $kid(['full_name' => 'Lê Minh Khôi', 'gender' => 'nam', 'birth_date' => now()->subMonths(28)->toDateString(), 'classroom_id' => $c2->id]);

        $this->as($owner, $center)->post(route('inventory.store'), [
            'name' => 'Tã quần size M', 'category' => 'ta_bim', 'unit' => 'cái', 'low_stock_threshold' => 10, 'initial_quantity' => 100, 'initial_cost' => 500000,
        ])->assertSessionHasNoErrors();

        [$na, $bin, $khoi] = $center->children()->orderBy('id')->get()->all();

        return [$c1, $c2, $na, $bin, $khoi, $center->items()->firstOrFail()];
    }

    public function test_attendance_diary_tasks_and_inventory(): void
    {
        [$owner, $center] = $this->owner();
        [$c1, $c2, $na, $bin, $khoi, $ta] = $this->seedCenter($owner, $center);
        $today = now()->toDateString();

        $this->assertSame(['B001', 'B002', 'B003'], $center->children()->orderBy('id')->pluck('code')->all());

        // Điểm danh: Na có mặt, Bin nghỉ phép, còn lại → "tất cả có mặt".
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => $today, 'status' => 'co_mat'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $bin->id, 'date' => $today, 'status' => 'vang_phep', 'note' => 'Bé sốt'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('attendance.mark-all'), ['date' => $today])->assertSessionHasNoErrors();
        $this->assertSame(2, $center->attendances()->where('status', 'co_mat')->count());
        $this->assertNotNull($center->attendances()->where('child_id', $na->id)->value('check_in'));

        // Đổi ý: Bin đến muộn → có mặt (vẫn 1 dòng/ngày).
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $bin->id, 'date' => $today, 'status' => 'co_mat'])->assertSessionHasNoErrors();
        $this->assertSame(1, $center->attendances()->where('child_id', $bin->id)->count());

        $row = $center->attendances()->where('child_id', $na->id)->firstOrFail();
        $this->as($owner, $center)->post(route('attendance.check-out', $row->id), ['check_out' => '16:30', 'picked_up_by' => 'Mẹ'])->assertSessionHasNoErrors();
        $this->assertSame('16:30', $row->fresh()->checkOutLabel());

        // Ghi nhật ký cả lớp: ăn trưa, thay tã (trừ kho 1 cái/bé), ngủ, nhiệt độ (sốt), sự cố.
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'an', 'child_ids' => [$bin->id, $khoi->id], 'meal' => 'trua', 'level' => 'het', 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 've_sinh', 'child_ids' => [$na->id, $bin->id, $khoi->id], 'toilet' => 'thay_ta', 'item_id' => $ta->id, 'item_quantity' => 1, 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'ngu', 'child_ids' => [$na->id], 'sleep_from' => '12:00', 'sleep_to' => '14:15', 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'suc_khoe', 'child_ids' => [$na->id], 'temperature' => '38.2', 'occurred_on' => $today])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'su_co', 'child_ids' => [$khoi->id], 'occurred_on' => $today])->assertSessionHasErrors('note');
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'can_do', 'child_ids' => [$na->id], 'weight_kg' => '11.2', 'height_cm' => '82', 'occurred_on' => $today])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(97, (float) $ta->fresh()->current_stock, 0.001);
        $this->assertSame(135, (int) DiaryEntry::query()->where('type', 'ngu')->value('sleep_minutes'));
        $this->assertTrue(DiaryEntry::query()->where('type', 'suc_khoe')->firstOrFail()->isFever());

        // Báo cáo ngày có đủ thông tin.
        $report = $this->as($owner, $center)->get(route('diary.report'))->assertOk()->getContent();
        $this->assertStringContainsString('Ngủ: 12:00–14:15 · 2 giờ 15 phút', html_entity_decode($report));
        $this->assertStringContainsString('38,2°C (sốt)', html_entity_decode($report));

        // Xoá 1 ghi nhận thay tã → hoàn kho.
        $entry = DiaryEntry::query()->where('type', 've_sinh')->where('child_id', $na->id)->firstOrFail();
        $this->as($owner, $center)->delete(route('diary.destroy', $entry->id))->assertRedirect();
        $this->assertEqualsWithDelta(98, (float) $ta->fresh()->current_stock, 0.001);

        // Lịch "thay tã cả lớp 2" → hoàn thành 1 lần chỉ ghi cho bé có mặt, bấm 2 lần không trừ kho 2 lần.
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $khoi->id, 'date' => $today, 'status' => 'vang'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('routines.store'), [
            'title' => 'Thay tã', 'type' => 've_sinh', 'target' => 'classroom', 'classroom_id' => $c2->id, 'item_id' => $ta->id, 'item_quantity' => 1,
            'frequency' => 'daily', 'times' => ['23:59'], 'starts_on' => $today,
        ])->assertSessionHasNoErrors();
        $task = Task::query()->whereDate('due_at', $today)->firstOrFail();
        $this->as($owner, $center)->post(route('tasks.complete', $task->id))->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('tasks.complete', $task->id))->assertSessionHasNoErrors();
        $this->assertSame('done', $task->fresh()->status);
        $this->assertEqualsWithDelta(97, (float) $ta->fresh()->current_stock, 0.001); // chỉ Bin (Khôi vắng)

        // Việc đo nhiệt cả lớp không có số liệu → không tạo dòng nhật ký rỗng.
        $this->as($owner, $center)->post(route('tasks.store'), ['title' => 'Đo nhiệt', 'type' => 'suc_khoe', 'target' => 'all', 'due_date' => $today, 'due_time' => '10:00'])->assertSessionHasNoErrors();
        $before = DiaryEntry::query()->count();
        $this->as($owner, $center)->post(route('tasks.complete', Task::query()->where('title', 'Đo nhiệt')->value('id')))->assertSessionHasNoErrors();
        $this->assertSame($before, DiaryEntry::query()->count());

        // Bé nghỉ học: không ghi nhật ký được nữa, link phụ huynh bị tắt.
        $this->as($owner, $center)->post(route('children.share', $khoi->id))->assertSessionHasNoErrors();
        $this->assertNotNull($khoi->fresh()->parent_token);
        $this->as($owner, $center)->post(route('children.leave', $khoi->id))->assertSessionHasNoErrors();
        $this->assertNull($khoi->fresh()->parent_token);
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'hoat_dong', 'child_ids' => [$khoi->id], 'note' => 'Vẽ', 'occurred_on' => $today])->assertSessionHasErrors('child_ids');
    }

    public function test_invoices_payments_parent_page_and_report(): void
    {
        [$owner, $center] = $this->owner();
        [$c1, $c2, $na, $bin, $khoi, $ta] = $this->seedCenter($owner, $center);
        $month = now()->startOfMonth();

        // 3 ngày có mặt cho Na, 2 cho Bin trong tháng.
        foreach ([0, 1, 2] as $i) {
            $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => $month->copy()->addDays($i)->min(now())->toDateString(), 'status' => 'co_mat']);
        }
        foreach ([0, 1] as $i) {
            $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $bin->id, 'date' => $month->copy()->addDays($i)->min(now())->toDateString(), 'status' => 'co_mat']);
        }
        $naDays = $center->attendances()->where('child_id', $na->id)->where('status', 'co_mat')->count();

        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => $month->format('Y-m')])->assertSessionHasNoErrors();
        $this->assertSame(3, Invoice::query()->count());

        $naInv = Invoice::query()->where('child_id', $na->id)->firstOrFail();
        $binInv = Invoice::query()->where('child_id', $bin->id)->firstOrFail();
        $this->assertEqualsWithDelta(2500000 + 35000 * $naDays, (float) $naInv->total_amount, 0.01);
        $this->assertEqualsWithDelta(2000000, (float) $binInv->tuition_amount, 0.01); // học phí riêng

        // Thu 1 phần rồi thu đủ; không cho thu quá.
        $this->as($owner, $center)->post(route('invoices.pay', $naInv->id), ['amount' => '1000000', 'paid_on' => now()->toDateString(), 'method' => 'tien_mat'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('invoices.pay', $naInv->id), ['amount' => '99999999', 'paid_on' => now()->toDateString(), 'method' => 'chuyen_khoan'])->assertSessionHasErrors('amount');
        $this->assertSame('partial', $naInv->fresh()->status());

        // Phiếu đã thu không bị "tạo lại" làm thay đổi; phiếu chưa thu được cập nhật số ngày.
        $this->as($owner, $center)->post(route('attendance.mark'), ['child_id' => $bin->id, 'date' => now()->toDateString(), 'status' => 'co_mat']);
        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => $month->format('Y-m')])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta((float) $naInv->total_amount, (float) $naInv->fresh()->total_amount, 0.01);

        // Sửa phiếu: giảm trừ; không được nhỏ hơn số đã thu.
        $this->as($owner, $center)->put(route('invoices.update', $naInv->id), [
            'tuition_amount' => '100000', 'meal_days' => 0, 'meal_rate' => '35000', 'discount_amount' => '0',
        ])->assertSessionHasErrors('total');
        $this->as($owner, $center)->put(route('invoices.update', $naInv->id), [
            'tuition_amount' => '2500000', 'meal_days' => $naDays, 'meal_rate' => '35000', 'other_label' => 'Đồng phục', 'other_amount' => '150000', 'discount_amount' => '50000',
        ])->assertSessionHasNoErrors();
        $naInv->refresh();
        $this->as($owner, $center)->post(route('invoices.pay', $naInv->id), ['amount' => (string) $naInv->balance(), 'paid_on' => now()->toDateString(), 'method' => 'chuyen_khoan'])->assertSessionHasNoErrors();
        $this->assertSame('paid', $naInv->fresh()->status());

        // Không xoá được bé đã có khoản thu.
        $this->as($owner, $center)->delete(route('children.destroy', $na->id))->assertSessionHas('warning');
        $this->assertNotNull(Child::find($na->id));

        // Trang phụ huynh: mở bằng link, thấy nhật ký & học phí còn nợ của đúng bé.
        $center->forceFill(['bank_code' => 'VCB', 'bank_account' => '0123456789', 'bank_holder' => 'NGUYEN THI HOA'])->save();
        $this->as($owner, $center)->post(route('diary.store'), ['type' => 'an', 'child_ids' => [$bin->id], 'meal' => 'sang', 'level' => 'it', 'occurred_on' => now()->toDateString()]);
        $this->as($owner, $center)->post(route('children.share', $bin->id));
        $token = $bin->fresh()->parent_token;
        $this->assertSame(40, strlen($token));

        auth()->logout();
        $page = $this->get(route('parent.show', $token))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->getContent();
        $this->assertStringContainsString('Bé Bin', $page);
        $this->assertStringContainsString('img.vietqr.io/image/VCB-0123456789', $page);
        $this->assertStringNotContainsString('An Nhiên', $page);
        $this->get('/phu-huynh/'.str_repeat('a', 40))->assertNotFound();

        // Báo cáo thu/chi.
        $this->as($owner, $center)->post(route('expenses.store'), ['category' => 'thue_nha', 'amount' => '3000000', 'occurred_on' => now()->toDateString()])->assertSessionHasNoErrors();
        $html = $this->as($owner, $center)->get(route('reports.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Thuê nhà', $html);
        $this->as($owner, $center)->get(route('reports.debts'))->assertOk();
    }

    public function test_every_get_page_renders_for_owner_staff_and_admin(): void
    {
        [$owner, $center] = $this->owner();
        [$c1, $c2, $na, $bin, $khoi, $ta] = $this->seedCenter($owner, $center);
        $today = now()->toDateString();

        $this->as($owner, $center)->post(route('attendance.mark-all'), ['date' => $today]);
        foreach (['an' => ['meal' => 'trua', 'level' => 'it'], 'sua' => ['amount_ml' => 180], 'ngu' => ['sleep_from' => '12:00'], 've_sinh' => ['toilet' => 'long'],
            'thuoc' => ['note' => 'Siro ho'], 'suc_khoe' => ['temperature' => 37.0], 'can_do' => ['weight_kg' => 12], 'hoat_dong' => ['note' => 'Hát'], 'su_co' => ['note' => 'Ngã'], 'khac' => ['note' => 'Vui']] as $type => $f) {
            $this->as($owner, $center)->post(route('diary.store'), ['type' => $type, 'child_ids' => [$na->id, $bin->id], 'occurred_on' => $today] + $f)->assertSessionHasNoErrors();
        }
        $this->as($owner, $center)->put(route('menus.update'), ['week' => $today, 'dishes' => [$today => ['trua' => 'Cháo thịt bí đỏ', 'xe' => 'Sữa chua']]])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('routines.store'), [
            'title' => 'Khử khuẩn đồ chơi', 'type' => 'don_dep', 'target' => 'none', 'frequency' => 'weekdays', 'times' => ['16:00'], 'starts_on' => $today,
        ])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('tasks.store'), ['title' => 'Uống thuốc', 'type' => 'thuoc', 'target' => 'child', 'child_id' => $na->id, 'due_date' => $today, 'due_time' => '09:00'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('invoices.generate'), ['month' => now()->format('Y-m')]);
        $this->as($owner, $center)->post(route('expenses.store'), ['category' => 'di_cho', 'amount' => '250000', 'occurred_on' => $today]);
        $this->as($owner, $center)->post(route('staff.store'), ['name' => 'Cô Lan', 'phone' => '0900000002', 'password' => 'password1'])->assertSessionHasNoErrors();
        $this->as($owner, $center)->post(route('children.share', $na->id));
        $staff = User::query()->where('phone', '0900000002')->firstOrFail();

        $params = [
            'child' => $na->id, 'item' => $ta->id, 'template' => $center->taskTemplates()->value('id'), 'invoice' => Invoice::query()->value('id'),
            'center' => $center->id, 'slug' => 'gioi-thieu', 'user' => $staff->id, 'token' => $na->fresh()->parent_token,
        ];

        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || str_starts_with($uri, 'install') || str_starts_with($uri, 'sieu-quan-tri')
                || str_contains($uri, 'auth/google') || str_starts_with($uri, 'storage') || str_contains($uri, 'dat-lai-mat-khau')
                || array_diff($route->parameterNames(), array_keys($params))) {
                continue;
            }

            $url = url(strtr($uri, collect($params)->mapWithKeys(fn ($v, $k) => ['{'.$k.'}' => $v])->all()));

            foreach (['owner' => $owner, 'staff' => $staff] as $role => $user) {
                $res = $this->as($user, $center)->get($url);
                $this->assertLessThan(500, $res->getStatusCode(), "GET {$url} as {$role} → {$res->getStatusCode()}\n".substr((string) $res->getContent(), 0, 2500));
                $checked++;
            }
        }
        $this->assertGreaterThan(40, $checked);

        // Trang có tham số lọc / ngày khác.
        foreach ([
            route('children.show', ['child' => $na->id, 'ngay' => now()->subDay()->toDateString()]), route('children.show', ['child' => $na->id, 'page' => 2]),
            route('attendance.index', ['ngay' => 'rác', 'lop' => $c1->id]), route('diary.index', ['be' => $na->id, 'loai' => 'an', 'ngay' => $today]),
            route('menus.index', ['tuan' => '2026-01-07']), route('invoices.index', ['thang' => '2026-13', 'loc' => 'unpaid']),
            route('reports.index', ['thang' => 'abc']), route('children.index', ['q' => 'Na', 'status' => 'left']),
        ] as $url) {
            $this->as($owner, $center)->get($url)->assertOk();
        }

        // Cô giáo không vào được trang tiền bạc / quản lý.
        foreach (['invoices.index', 'expenses.index', 'reports.index', 'settings.index', 'staff.index', 'classrooms.index', 'children.create'] as $name) {
            $this->as($staff, $center)->get(route($name))->assertForbidden();
        }
        // Cô giáo chỉ xoá được nhật ký của chính mình.
        $ownerEntry = DiaryEntry::query()->firstOrFail();
        $this->as($staff, $center)->delete(route('diary.destroy', $ownerEntry->id))->assertForbidden();

        // Super Admin.
        $admin = User::forceCreate(['name' => 'Admin', 'phone' => '0911111111', 'password' => Hash::make('password'), 'role' => 'platform_admin', 'is_active' => true]);
        foreach (['admin.dashboard' => [], 'admin.centers.show' => [$center->id], 'admin.users.index' => [], 'admin.pages.edit' => ['gioi-thieu'], 'profile.edit' => []] as $name => $args) {
            $this->actingAs($admin)->get(route($name, $args))->assertOk();
        }
    }

    public function test_tenant_isolation(): void
    {
        [$owner, $center] = $this->owner();
        [$c1, $c2, $na] = $this->seedCenter($owner, $center);

        $other = User::forceCreate(['name' => 'Khác', 'phone' => '0988888888', 'password' => Hash::make('password'), 'role' => 'owner', 'is_active' => true]);
        $otherCenter = Center::forceCreate(['owner_id' => $other->id, 'name' => 'Nhóm khác', 'is_active' => true]);
        $otherCenter->staff()->attach($other->id);

        $this->as($other, $otherCenter)->get(route('children.show', $na->id))->assertNotFound();
        $this->as($other, $otherCenter)->post(route('diary.store'), ['type' => 'khac', 'child_ids' => [$na->id], 'note' => 'x', 'occurred_on' => now()->toDateString()])->assertSessionHasErrors('child_ids.0');
        $this->as($other, $otherCenter)->post(route('attendance.mark'), ['child_id' => $na->id, 'date' => now()->toDateString(), 'status' => 'co_mat'])->assertSessionHasErrors('child_id');
        // Session trỏ sang nhóm trẻ của người khác → tự quay về nhóm của mình.
        $this->as($other, $center)->get(route('children.index'))->assertOk()->assertDontSee('An Nhiên');
    }
}
