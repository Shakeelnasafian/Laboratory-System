<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('test_categories')->nullOnDelete();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('code')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('unit')->nullable();        // mg/dL, g/L, etc.
            $table->string('normal_range')->nullable(); // text like "70-100" or "Negative"
            $table->string('normal_range_male')->nullable();
            $table->string('normal_range_female')->nullable();
            $table->text('description')->nullable();
            $table->string('sample_type')->nullable(); // Blood, Urine, Stool, etc.
            $table->integer('turnaround_hours')->default(24);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
