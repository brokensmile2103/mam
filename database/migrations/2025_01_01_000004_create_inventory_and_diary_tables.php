<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('category', 20)->default('thuc_pham');
            $table->string('unit', 20)->default('cái');
            $table->decimal('current_stock', 14, 3)->default(0);
            $table->decimal('avg_cost', 14, 2)->default(0);       // giá vốn bình quân gia quyền / đơn vị
            $table->decimal('low_stock_threshold', 14, 3)->default(0);
            $table->string('note')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['center_id', 'category']);
        });

        // Sổ nhật ký của trẻ: ăn, ngủ, vệ sinh, thuốc, nhiệt độ, cân đo, hoạt động, sự cố...
        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->string('type', 20);
            $table->dateTime('occurred_at');
            $table->string('meal', 10)->nullable();                // an: sang | trua | xe | phu
            $table->string('level', 12)->nullable();               // an: het | gan_het | it | khong — ve_sinh: tieu | dai | thay_ta | long
            $table->unsignedSmallInteger('amount_ml')->nullable(); // sua
            $table->time('sleep_from')->nullable();                // ngu
            $table->time('sleep_to')->nullable();
            $table->unsignedSmallInteger('sleep_minutes')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();      // suc_khoe (°C)
            $table->decimal('weight_kg', 6, 2)->nullable();        // can_do
            $table->decimal('height_cm', 5, 1)->nullable();        // can_do
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('item_quantity', 14, 3)->nullable();
            $table->decimal('cost', 16, 2)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['child_id', 'occurred_at']);
            $table->index(['center_id', 'occurred_at']);
            $table->index(['center_id', 'type']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type', 10);                           // in | out | adjust
            $table->decimal('quantity', 14, 3);                   // có dấu: in +, out −, adjust ±
            $table->decimal('unit_cost', 14, 2)->default(0);
            $table->decimal('total_cost', 16, 2)->default(0);     // luôn dương
            $table->unsignedBigInteger('child_id')->nullable();
            $table->unsignedBigInteger('entry_id')->nullable();
            $table->string('supplier', 120)->nullable();
            $table->string('note')->nullable();
            $table->dateTime('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['item_id', 'occurred_at']);
            $table->index('entry_id');
            $table->index(['center_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('inventory_items');
    }
};
