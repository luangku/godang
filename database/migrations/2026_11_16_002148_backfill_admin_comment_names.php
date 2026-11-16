<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Copy the current admin name onto the comments written by that admin.
     */
    public function up(): void
    {
        $admins = DB::table('users')
            ->where('is_admin', true)
            ->whereNotNull('email')
            ->get(['name', 'email']);

        foreach ($admins as $admin) {
            DB::table('comments')
                ->where('is_author', true)
                ->whereRaw('LOWER(email) = ?', [Str::lower($admin->email)])
                ->update(['name' => $admin->name]);
        }
    }

    /**
     * Previous display names came from the author_name setting and cannot be restored reliably.
     */
    public function down(): void
    {
        //
    }
};
