<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::dropIfExists('item_option_issue_comments');
    Schema::dropIfExists('item_option_issues');
    Schema::dropIfExists('item_options');
    Schema::create('item_issues', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_id')->constrained()->onDelete('cascade');
      $table->text('value')->nullable();
      $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
      $table->foreignId('reported_by')->constrained('users')->onDelete('set null');
      $table->date('resolution_date')->nullable();
      $table->timestamps();
    });

    Schema::create('item_issue_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_issue_id')->constrained('item_issues')->onDelete('cascade');
      $table->foreignId('user_id')->constrained()->onDelete('set null');
      $table->text('comment');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('item_issue_comments');
    Schema::dropIfExists('item_issues');
    Schema::create('item_options', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_id')->constrained()->onDelete('cascade');
      $table->string('name');
      $table->string('value')->nullable();
      $table->timestamps();
    });

    Schema::create('item_option_issues', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_option_id')->constrained()->onDelete('cascade');
      $table->string('title');
      $table->text('description')->nullable();
      $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
      $table->date('resolution_date')->nullable();
      $table->timestamps();
    });

    Schema::create('item_option_issue_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_option_issue_id')->constrained('item_option_issues')->onDelete('cascade');
      $table->foreignId('user_id')->constrained()->onDelete('set null');
      $table->text('comment');
      $table->timestamps();
    });
  }
};
