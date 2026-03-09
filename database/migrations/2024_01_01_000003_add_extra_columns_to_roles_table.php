<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('guard_name');
            $table->unsignedInteger('hierarchy_level')->default(0)->after('description');
            $table->boolean('is_super_admin')->default(false)->after('hierarchy_level');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'hierarchy_level', 'is_super_admin']);
        });
    }
};
