<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained('results')->cascadeOnDelete();
            $table->foreignId('revised_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('previous_value')->nullable();
            $table->string('previous_unit')->nullable();
            $table->string('previous_normal_range')->nullable();
            $table->boolean('previous_is_abnormal')->default(false);
            $table->enum('previous_flag', ['normal', 'high', 'low', 'critical'])->default('normal');
            $table->text('previous_remarks')->nullable();
            $table->enum('previous_status', ['draft', 'verified', 'released'])->default('draft');
            $table->timestamp('revised_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_revisions');
    }
};