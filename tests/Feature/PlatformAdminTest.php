<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/**
 * Super Admin dùng được như tài khoản thường (có nhóm trẻ riêng, không bị ép về
 * /sieu-quan-tri) và việc đóng/mở đăng ký công khai.
 */
class PlatformAdminTest extends TestCase
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

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'phone' => '0900000003', 'password' => bcrypt('secret123'), 'role' => 'platform_admin', 'is_active' => true]);
    }

    public function test_platform_admin_without_center_gets_one_on_login_and_lands_on_work_area(): void
    {
        $admin = $this->admin();
        $this->assertSame(0, Center::count());

        $this->post('/login', ['phone' => $admin->phone, 'password' => 'secret123'])->assertRedirect(route('today'));

        $tenant = Center::where('owner_id', $admin->id)->first();
        $this->assertNotNull($tenant);
        $this->assertSame($tenant->id, session('current_center_id'));
    }

    public function test_platform_admin_can_use_work_area_owner_pages_and_admin_area_without_redirects(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('today'))->assertOk();
        $this->get(route('settings.index'))->assertOk();
        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_platform_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete('/ho-so', ['delete_password' => 'secret123', 'confirmation' => 'XOA TAI KHOAN'])->assertForbidden();
        $this->assertNotNull($admin->fresh());
    }

    public function test_regular_owner_cannot_open_admin_settings(): void
    {
        $owner = User::forceCreate(['name' => 'Chủ', 'phone' => '0900000001', 'password' => bcrypt('x'), 'role' => 'owner', 'is_active' => true]);
        $this->actingAs($owner)->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs($owner)->put(route('admin.settings.update'), ['registration_open' => '0'])->assertForbidden();
    }

    public function test_admin_can_close_and_reopen_registration(): void
    {
        $admin = $this->admin();
        $this->assertTrue(PlatformSetting::registrationOpen());

        $this->actingAs($admin)->put(route('admin.settings.update'), ['registration_open' => '0'])->assertRedirect();
        $this->assertFalse(PlatformSetting::registrationOpen());

        $this->put(route('admin.settings.update'), ['registration_open' => '1'])->assertRedirect();
        $this->assertTrue(PlatformSetting::registrationOpen());
    }

    public function test_closed_registration_blocks_register_pages_and_unknown_google_accounts(): void
    {
        PlatformSetting::put('registration_open', '0');

        $this->get('/dang-ky')->assertRedirect(route('login'));
        $this->post('/dang-ky', [])->assertRedirect(route('login'));
        $this->assertSame(0, User::count());

        config(['services.google.client_id' => 'x']);
        $google = (new SocialiteUser)->map(['id' => 'g-unknown', 'name' => 'Lạ', 'email' => 'la@example.com', 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertSame(0, User::count());
        $this->assertNull(session('google_pending'));
    }

    public function test_closed_registration_still_lets_existing_google_account_sign_in(): void
    {
        PlatformSetting::put('registration_open', '0');
        $admin = $this->admin();
        $admin->forceFill(['google_id' => 'g-admin'])->save();

        config(['services.google.client_id' => 'x']);
        $google = (new SocialiteUser)->map(['id' => 'g-admin', 'name' => 'Admin', 'email' => null, 'avatar' => null]);
        Socialite::shouldReceive('driver->user')->andReturn($google);

        $this->get('/auth/google/callback')->assertRedirect(route('today'));
        $this->assertAuthenticatedAs($admin);
    }
}
