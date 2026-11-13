<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Troubleshooting', 'slug' => 'troubleshooting'],
            ['name' => 'Tutorial', 'slug' => 'tutorial'],
            ['name' => 'Eloquent', 'slug' => 'eloquent'],
            ['name' => 'MySQL', 'slug' => 'mysql'],
            ['name' => 'API', 'slug' => 'api'],
            ['name' => 'Nginx', 'slug' => 'nginx'],
            ['name' => 'Docker', 'slug' => 'docker'],
            ['name' => 'Git', 'slug' => 'git'],
            ['name' => 'React', 'slug' => 'react'],
            ['name' => 'Performa', 'slug' => 'performance'],
            ['name' => 'Keamanan', 'slug' => 'security'],
            ['name' => 'Testing', 'slug' => 'testing'],
            ['name' => 'Deployment', 'slug' => 'deployment'],
            ['name' => 'Vite', 'slug' => 'vite'],
            ['name' => 'Tailwind CSS', 'slug' => 'tailwind-css'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                ['name' => $tag['name']]
            );
        }
    }
}
