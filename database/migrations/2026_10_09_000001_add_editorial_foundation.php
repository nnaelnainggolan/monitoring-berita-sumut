<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('leader');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['kind', 'name']);
        });
        Schema::table('news_items', function (Blueprint $table) {
            foreach (['category', 'region', 'agency'] as $kind) {
                $table->foreignId($kind.'_id')->nullable()->constrained('taxonomies')->nullOnDelete();
            }
            $table->string('verification_status')->default('pending')->index();
            $table->unsignedInteger('review_version')->default(0);
            $table->json('ai_payload')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
        });
        Schema::create('news_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->json('before');
            $table->json('after');
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_reviews');
        Schema::table('news_items', function (Blueprint $table) {
            foreach (['category_id', 'region_id', 'agency_id', 'verified_by'] as $column) {
                $table->dropConstrainedForeignId($column);
            }
            $table->dropIndex(['verification_status']);
            $table->dropColumn(['verification_status', 'review_version', 'ai_payload', 'verified_at']);
        });
        Schema::dropIfExists('taxonomies');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'is_active']));
    }
};
