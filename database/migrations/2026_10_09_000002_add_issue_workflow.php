<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->json('notification_preferences')->nullable());
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary');
            $table->string('status')->default('new')->index();
            $table->string('priority')->default('normal')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            foreach (['category', 'region', 'agency'] as $kind) {
                $table->foreignId($kind.'_id')->nullable()->constrained('taxonomies')->nullOnDelete();
            }
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });
        Schema::table('news_items', fn (Blueprint $table) => $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete());
        Schema::create('issue_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->text('note');
            $table->json('changes')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('issue_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained()->cascadeOnDelete();
            $table->date('reminder_date');
            $table->unique(['issue_id', 'reminder_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_reminders');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('issue_activities');
        Schema::table('news_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('issue_id'));
        Schema::dropIfExists('issues');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notification_preferences'));
    }
};
