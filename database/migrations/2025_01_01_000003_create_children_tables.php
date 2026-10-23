<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lớp / nhóm theo độ tuổi.
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('age_label', 40)->nullable();          // VD: "13–24 tháng"
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('center_id');
        });

        // Hồ sơ trẻ.
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('full_name', 100);
            $table->string('nickname', 40)->nullable();            // tên ở nhà
            $table->string('gender', 6)->nullable();               // nam | nu
            $table->date('birth_date');
            $table->date('enrolled_on');
            $table->date('left_on')->nullable();
            $table->string('status', 10)->default('active');       // active | left
            $table->string('parent_name', 100)->nullable();
            $table->string('parent_relation', 20)->nullable();     // Mẹ, Bố, Ông, Bà...
            $table->string('parent_phone', 20)->nullable();
            $table->string('parent2_name', 100)->nullable();
            $table->string('parent2_phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('allergies', 500)->nullable();          // dị ứng thức ăn/thuốc
            $table->text('health_note')->nullable();               // bệnh nền, lưu ý sức khoẻ
            $table->string('pickup_note', 255)->nullable();        // người được phép đón
            $table->decimal('tuition_fee', 14, 2)->nullable();     // null = theo mức mặc định của nhóm
            $table->decimal('meal_rate', 14, 2)->nullable();       // null = theo mức mặc định của nhóm
            $table->string('parent_token', 64)->nullable()->unique(); // link xem dành cho phụ huynh (null = tắt)
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['center_id', 'status']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 12);                          // co_mat | vang_phep | vang
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('picked_up_by', 100)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['child_id', 'date']);
            $table->index(['center_id', 'date']);
        });

        // Thực đơn theo ngày & bữa.
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meal', 10);                            // sang | trua | xe | phu
            $table->string('dishes', 500);
            $table->timestamps();

            $table->unique(['center_id', 'date', 'meal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('children');
        Schema::dropIfExists('classrooms');
    }
};
