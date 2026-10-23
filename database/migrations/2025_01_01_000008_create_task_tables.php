<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('type', 20)->default('khac');
            $table->string('target', 10)->default('none');        // child | classroom | all | none
            $table->foreignId('child_id')->nullable()->constrained('children')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('item_quantity', 14, 3)->nullable();
            $table->string('frequency', 10)->default('daily');    // daily | weekdays | weekly | monthly | interval
            $table->unsignedSmallInteger('interval_days')->nullable();
            $table->string('weekdays', 20)->nullable();           // "1,3,5" (ISO: 1=T2 ... 7=CN)
            $table->unsignedTinyInteger('month_day')->nullable();
            $table->string('times', 60)->default('08:00');        // "08:00,14:00"
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->date('last_generated_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['center_id', 'is_active']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('task_templates')->nullOnDelete();
            $table->string('title', 120);
            $table->string('type', 20)->default('khac');
            $table->string('target', 10)->default('none');
            $table->foreignId('child_id')->nullable()->constrained('children')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->decimal('item_quantity', 14, 3)->nullable();
            $table->dateTime('due_at');
            $table->string('status', 10)->default('pending');     // pending | done | skipped
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['center_id', 'status', 'due_at']);
            $table->unique(['template_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_templates');
    }
};
