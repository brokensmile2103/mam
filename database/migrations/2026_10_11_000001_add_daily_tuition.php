<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Học phí THEO NGÀY (gửi ngày): học phí = số ngày bé có mặt × đơn giá/ngày, bên cạnh kiểu học phí tháng cố định.
 *  - centers.tuition_mode: cách tính mặc định của nhóm (monthly | daily) + đơn giá/ngày mặc định.
 *  - children.tuition_mode: null = theo nhóm; từng bé có thể gửi tháng hoặc gửi ngày riêng + đơn giá riêng.
 *  - invoices: lưu lại cách tính + số ngày học + đơn giá tại thời điểm lập phiếu (phiếu cũ = monthly, không đổi số tiền),
 *    và edited_at = phiếu đã được sửa tay (không bị "Cập nhật phiếu" hàng loạt ghi đè).
 * Chỉ THÊM cột có giá trị mặc định — an toàn với dữ liệu đang chạy thật.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->string('tuition_mode', 10)->default('monthly')->after('is_active');
            $table->decimal('daily_fee_default', 14, 2)->default(0)->after('tuition_default');
        });

        Schema::table('children', function (Blueprint $table) {
            $table->string('tuition_mode', 10)->nullable()->after('pickup_note');   // null = theo mức chung của nhóm
            $table->decimal('daily_fee', 14, 2)->nullable()->after('tuition_fee');   // null = theo mức chung của nhóm
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('tuition_mode', 10)->default('monthly')->after('month');
            $table->unsignedSmallInteger('tuition_days')->default(0)->after('tuition_amount');
            $table->decimal('tuition_rate', 14, 2)->default(0)->after('tuition_days');
            // Chủ nhóm đã sửa tay phiếu → "Cập nhật phiếu" hàng loạt KHÔNG ghi đè nữa (trước đây bị tính lại mất số đã sửa).
            $table->timestamp('edited_at')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tuition_mode', 'tuition_days', 'tuition_rate', 'edited_at']);
        });

        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn(['tuition_mode', 'daily_fee']);
        });

        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn(['tuition_mode', 'daily_fee_default']);
        });
    }
};
