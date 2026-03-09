<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable()->after('assigned_to');
            $table->timestamp('due_at')->nullable()->after('started_at');
            $table->timestamp('completed_at')->nullable()->after('due_at');
            $table->text('processing_notes')->nullable()->after('completed_at');
        });

        $orderItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('tests', 'tests.id', '=', 'order_items.test_id')
            ->select('order_items.id', 'orders.created_at as order_created_at', 'tests.turnaround_hours')
            ->get();

        foreach ($orderItems as $orderItem) {
            $dueAt = Carbon::parse($orderItem->order_created_at)
                ->addHours((int) $orderItem->turnaround_hours);

            DB::table('order_items')
                ->where('id', $orderItem->id)
                ->update(['due_at' => $dueAt]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['started_at', 'due_at', 'completed_at', 'processing_notes']);
        });
    }
};