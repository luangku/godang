<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The media_categories table is created here as well because media
     * references it through a foreign key.
     */
    public function up(): void
    {
        Schema::create('media_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('type', 10)->default('image'); // image, file
            $table->string('original_name')->nullable();
            $table->string('thumb_path')->nullable();
            $table->string('folder')->default('uploads');
            $table->foreignId('media_category_id')
                ->nullable()
                ->constrained('media_categories')
                ->nullOnDelete();
            $table->string('alt_text')->nullable();
            $table->string('mime', 50)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('folder');
            $table->index(['type', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_categories');
    }
};
