<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->enum('status', ['draft', 'verified', 'released'])->default('draft')->after('remarks');
            $table->foreignId('released_by')->nullable()->after('verified_by')->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable()->after('verified_at');
            $table->timestamp('critical_alerted_at')->nullable()->after('released_at');
        });

        DB::table('results')
            ->where('is_verified', true)
            ->update(['status' => 'verified']);
    }

    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('released_by');
            $table->dropColumn(['status', 'released_at', 'critical_alerted_at']);
        });
    }
};