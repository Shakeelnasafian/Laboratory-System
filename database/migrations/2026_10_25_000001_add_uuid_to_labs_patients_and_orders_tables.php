<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Public uuids for records that appear in URLs (see App\Traits\HasPublicUuid).
 * Existing rows are backfilled before the column becomes required.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    private const TABLES = ['labs', 'patients', 'orders'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            if (! Schema::hasColumn($tableName, 'uuid')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->uuid('uuid')->nullable()->after('id');
                });
            }

            DB::table($tableName)
                ->whereNull('uuid')
                ->select('id')
                ->chunkById(500, function ($rows) use ($tableName) {
                    foreach ($rows as $row) {
                        DB::table($tableName)->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
                    }
                });

            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('uuid')->nullable(false)->change();
                $table->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            });
        }
    }
};
