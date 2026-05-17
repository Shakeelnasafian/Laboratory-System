<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained('labs')->cascadeOnDelete();
            $table->string('patient_id')->nullable(); // auto-generated
            $table->string('name');
            $table->string('cnic', 15)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->date('dob')->nullable();
            $table->integer('age')->nullable();
            $table->string('age_unit')->default('years'); // years, months, days
            $table->string('address')->nullable();
            $table->string('referred_by')->nullable(); // doctor name
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
