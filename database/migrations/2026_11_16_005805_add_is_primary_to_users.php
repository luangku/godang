<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the primary admin flag and promote the earliest administrator.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false);
        });

        $primaryId = DB::table('users')
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');

        if ($primaryId !== null) {
            DB::table('users')
                ->where('id', $primaryId)
                ->update(['is_primary' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
