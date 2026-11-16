<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        $hasPrimaryAdmin = User::query()
            ->where('is_admin', true)
            ->where('is_primary', true)
            ->exists();

        $admin = User::firstOrNew(['email' => 'admin@godang.com']);
        $admin->fill([
            'name' => 'Admin',
            'bio' => 'Developer & Penulis Teknis',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);
        $admin->is_primary = $hasPrimaryAdmin ? (bool) $admin->is_primary : true;
        $admin->save();

        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
