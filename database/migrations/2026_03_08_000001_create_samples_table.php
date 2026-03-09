<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->string('accession_number')->unique();
            $table->string('sample_type')->nullable();
            $table->string('container')->nullable();
            $table->enum('status', ['pending', 'collected', 'received', 'rejected'])->default('pending');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_recollection')->default(false);
            $table->timestamp('label_printed_at')->nullable();
            $table->timestamps();

            $table->unique('order_item_id');
            $table->index(['lab_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};