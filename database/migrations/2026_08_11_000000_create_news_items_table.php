<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('link')->unique();
            $table->string('source')->nullable();       // RSS Pemprov Sumut / RSS Gubernur Sumut
            $table->timestamp('published_at')->nullable();
            $table->enum('sentiment', ['positif', 'netral', 'negatif'])->default('netral');
            $table->boolean('is_urgent')->default(false); // hasil node Filter Negatif/Mendesak
            $table->text('analysis')->nullable();          // alasan/ringkasan dari Analisis Berita AI
            $table->boolean('archived')->default(false);   // ditandai oleh node Arsipkan Berita
            $table->timestamps();

            $table->index(['sentiment', 'is_urgent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
