<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            // Queue connection name from config/queue.php, e.g. database, rabbitmq.
            $table->string('queue_connection', 32)->nullable()->after('status');
        });

        // Every import before this column was queued in the database.
        DB::table('imports')->update(['queue_connection' => 'database']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn('queue_connection');
        });
    }
};
