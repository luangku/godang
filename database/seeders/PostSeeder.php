<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        $categories = Category::all();
        $tags = Tag::all();

        $featured = Post::updateOrCreate(
            ['slug' => 'cara-mengatasi-error-class-not-found-di-laravel'],
            [
                'user_id' => $user->id,
                'category_id' => $categories->where('slug', 'laravel')->first()?->id,
                'title' => 'Cara Mengatasi Error "Class Not Found" di Laravel',
                'excerpt' => 'Solusi lengkap untuk error "Class Not Found" yang sering terjadi saat development Laravel.',
                'content' => $this->sampleContent(),
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'meta_title' => 'Fix Error Class Not Found Laravel — Solusi Lengkap',
                'meta_description' => 'Panduan mengatasi error Class Not Found di Laravel: autoload, namespace, dan cache.',
            ]
        );
        $featured->tags()->sync(
            $tags->whereIn('slug', ['troubleshooting', 'eloquent'])->pluck('id')->all()
        );

        $samplePosts = [
            ['title' => 'Optimasi Query Eloquent untuk Performa Lebih Baik', 'slug' => 'optimasi-query-eloquent', 'category' => 'laravel', 'tags' => ['eloquent', 'performance', 'mysql']],
            ['title' => 'Setup Nginx sebagai Reverse Proxy untuk Laravel', 'slug' => 'setup-nginx-reverse-proxy-laravel', 'category' => 'devops', 'tags' => ['nginx', 'deployment']],
            ['title' => 'Cara Deploy Laravel ke Shared Hosting', 'slug' => 'deploy-laravel-shared-hosting', 'category' => 'devops', 'tags' => ['deployment', 'troubleshooting']],
            ['title' => 'Mengatasi Error CORS di Laravel + React', 'slug' => 'mengatasi-error-cors-laravel-react', 'category' => 'javascript', 'tags' => ['react', 'api', 'troubleshooting']],
            ['title' => 'Docker Compose untuk Development Laravel', 'slug' => 'docker-compose-development-laravel', 'category' => 'devops', 'tags' => ['docker', 'deployment']],
            ['title' => 'Git Workflow untuk Tim Kecil', 'slug' => 'git-workflow-tim-kecil', 'category' => 'devops', 'tags' => ['git']],
            ['title' => 'Tailwind CSS v4: Migrasi dari v3', 'slug' => 'tailwind-css-v4-migrasi', 'category' => 'javascript', 'tags' => ['tailwind-css', 'tutorial']],
            ['title' => 'Troubleshooting Permission Denied di Linux Server', 'slug' => 'troubleshooting-permission-denied-linux', 'category' => 'linux', 'tags' => ['troubleshooting', 'deployment']],
        ];

        foreach ($samplePosts as $index => $data) {
            $post = Post::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $user->id,
                    'category_id' => $categories->where('slug', $data['category'])->first()?->id,
                    'title' => $data['title'],
                    'excerpt' => fake()->paragraph(2),
                    'content' => '<h2>'.fake()->sentence().'</h2><p>'.fake()->paragraphs(3, true).'</p><h2>'.fake()->sentence().'</h2><p>'.fake()->paragraphs(2, true).'</p><pre><code>'.fake()->text(150).'</code></pre><p>'.fake()->paragraphs(2, true).'</p>',
                    'status' => $index < 6 ? 'published' : 'draft',
                    'published_at' => $index < 6 ? now()->subDays(rand(1, 60)) : null,
                    'meta_description' => fake()->sentence(12),
                    'views_count' => rand(50, 3000),
                ]
            );

            $post->tags()->sync(
                $tags->whereIn('slug', $data['tags'])->pluck('id')->all()
            );
        }
    }

    private function sampleContent(): string
    {
        return <<<'HTML'
<h2>Masalah</h2>
<p>Saat menjalankan aplikasi Laravel, kamu mungkin menemui error seperti ini:</p>
<pre><code>Class "App\Models\SomeModel" not found</code></pre>
<p>Error ini bisa terjadi karena beberapa alasan. Mari kita bahas satu per satu.</p>

<h2>Penyebab & Solusi</h2>
<h3>1. Namespace Salah</h3>
<p>Pastikan namespace di file model sesuai dengan lokasi file. Jika model ada di <code>app/Models/</code>, maka namespace-nya harus <code>App\Models</code>.</p>

<h3>2. Autoload Belum Di-regenerate</h3>
<p>Jalankan perintah berikut untuk me-regenerate autoload:</p>
<pre><code>composer dump-autoload</code></pre>

<h3>3. Cache Konfigurasi</h3>
<p>Laravel meng-cache konfigurasi. Bersihkan cache:</p>
<pre><code>php artisan config:clear
php artisan cache:clear</code></pre>

<h2>Kesimpulan</h2>
<p>Error "Class Not Found" biasanya disebabkan oleh masalah namespace atau autoload. Pastikan untuk selalu menjalankan <code>composer dump-autoload</code> setelah menambahkan class baru.</p>
HTML;
    }
}
