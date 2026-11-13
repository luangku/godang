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
        User::updateOrCreate(
            ['email' => 'admin@godang.com'],
            [
                'name' => 'Admin',
                'bio' => 'Developer & Penulis Teknis',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        $this->call([
            CategorySeeder::class,
            TagSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
