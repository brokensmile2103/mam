<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mỗi Center (1 nhóm trẻ / cơ sở) CHÍNH LÀ 1 tenant — mọi bảng nghiệp vụ đều có center_id.
 * Cùng mô hình với Farm bên Chăm và Location bên Rót.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('font_scale', 10)->default('md');
            $table->string('accent_color', 20)->default('pink');
            $table->boolean('is_active')->default(true);
            // Học phí mặc định (đ/tháng) và tiền ăn (đ/ngày có mặt) — từng bé có thể đặt mức riêng.
            $table->decimal('tuition_default', 14, 2)->default(0);
            $table->decimal('meal_rate_default', 14, 2)->default(0);
            // Thông tin nhận chuyển khoản in trên phiếu học phí (+ mã VietQR nếu có).
            $table->string('bank_code', 20)->nullable();
            $table->string('bank_account', 40)->nullable();
            $table->string('bank_holder', 100)->nullable();
            $table->string('invoice_note', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('center_user', function (Blueprint $table) {
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['center_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('center_user');
        Schema::dropIfExists('centers');
    }
};
