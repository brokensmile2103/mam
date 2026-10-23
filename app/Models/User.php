<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * 'role' và 'is_active' CỐ TÌNH không nằm trong $fillable — đây là 2 trường
     * quyết định quyền hạn (owner/staff/platform_admin, khoá/mở), không bao giờ
     * được set qua mass-assignment. Nơi nào cần set (đăng ký, thêm thành viên,
     * cài đặt) phải dùng forceCreate()/forceFill() với giá trị CỐ ĐỊNH trong code.
     */
    protected $fillable = ['name', 'phone', 'email', 'password', 'avatar_url', 'google_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Có quyền chủ không. Super Admin cũng tính là chủ (có nhóm trẻ riêng, dùng được
     * mọi tính năng như tài khoản thường) — chỉ khác là có thêm khu vực /sieu-quan-tri.
     */
    public function isOwner(): bool
    {
        return in_array($this->role, ['owner', 'platform_admin'], true);
    }

    /** Super Admin của cả nền tảng (vừa là chủ, vừa quản trị được mọi nhóm trẻ). */
    public function isPlatformAdmin(): bool
    {
        return $this->role === 'platform_admin';
    }

    public function ownedCenters()
    {
        return $this->hasMany(Center::class, 'owner_id');
    }

    public function centers()
    {
        return $this->belongsToMany(Center::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }
}
