<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Laravel', 'slug' => 'laravel', 'description' => 'Framework PHP untuk aplikasi web modern.'],
            ['name' => 'PHP', 'slug' => 'php', 'description' => 'Bahasa pemrograman sisi server.'],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'description' => 'Bahasa pemrograman untuk web interaktif.'],
            ['name' => 'DevOps', 'slug' => 'devops', 'description' => 'Deployment, CI/CD, dan infrastruktur.'],
            ['name' => 'Database', 'slug' => 'database', 'description' => 'MySQL, PostgreSQL, dan manajemen data.'],
            ['name' => 'Linux', 'slug' => 'linux', 'description' => 'Administrasi server dan command line.'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                ]
            );
        }
    }
}
