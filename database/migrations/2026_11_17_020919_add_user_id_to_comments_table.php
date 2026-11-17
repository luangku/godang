<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('post_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $admins = DB::table('users')
            ->where('is_admin', true)
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            DB::table('comments')
                ->where('is_author', true)
                ->whereRaw('LOWER(email) = ?', [Str::lower($admin->email)])
                ->update(['user_id' => $admin->id]);
        }

        $primary = DB::table('users')
            ->where('is_admin', true)
            ->where('is_primary', true)
            ->first(['id', 'avatar']);

        if ($primary !== null) {
            $avatar = DB::table('settings')->where('key', 'author_avatar')->value('value');
            $avatarAlt = DB::table('settings')->where('key', 'author_avatar_alt')->value('value');

            if (blank($primary->avatar) && filled($avatar)) {
                DB::table('users')->where('id', $primary->id)->update(['avatar' => $avatar]);
            }

            if (Schema::hasColumn('users', 'avatar_alt') && filled($avatarAlt)) {
                $currentAlt = DB::table('users')->where('id', $primary->id)->value('avatar_alt');

                if (blank($currentAlt)) {
                    DB::table('users')->where('id', $primary->id)->update(['avatar_alt' => $avatarAlt]);
                }
            }
        }

        DB::table('settings')->whereIn('key', ['author_avatar', 'author_avatar_alt'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $primary = DB::table('users')
            ->where('is_admin', true)
            ->where('is_primary', true)
            ->first(['avatar']);

        if ($primary !== null && filled($primary->avatar)) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'author_avatar'],
                ['value' => $primary->avatar]
            );
        }

        if (Schema::hasColumn('users', 'avatar_alt')) {
            $primaryAlt = DB::table('users')
                ->where('is_admin', true)
                ->where('is_primary', true)
                ->value('avatar_alt');

            if (filled($primaryAlt)) {
                DB::table('settings')->updateOrInsert(
                    ['key' => 'author_avatar_alt'],
                    ['value' => $primaryAlt]
                );
            }
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
