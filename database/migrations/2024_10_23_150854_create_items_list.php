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
    Schema::create('items', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->text('description');
      $table->string('category');
      $table->boolean('usable')->default(true);
      $table->timestamps();
    });

    Schema::create('item_options', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_id')->references('id')->on('items')->constrained()->onDelete('cascade');
      $table->string('name');
      $table->text('description');
      $table->boolean('usable')->default(true);
      $table->timestamps();
    });

    Schema::create('item_option_issues', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_option_id')->references('id')->on('item_options')->constrained()->onDelete('cascade');
      $table->string('value');
      $table->timestamps();
    });

    Schema::create('item_option_issue_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_option_issue_id')->references('id')->on('item_option_issues')->constrained()->onDelete('cascade');
      $table->text('comment');
      $table->foreignId('user_id')->references('id')->on('users')->constrained()->onDelete('cascade');
      $table->timestamps();
    });

    // ItemSubscription
    Schema::create('item_subscriptions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('item_id')->references('id')->on('items')->constrained()->onDelete('cascade');
      $table->foreignId('user_id')->references('id')->on('users')->constrained()->onDelete('cascade');
      $table->string('status');
      $table->string('name');
      $table->timestamp('start_date');
      $table->timestamp('end_date');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('items');
    Schema::dropIfExists('item_options');
    Schema::dropIfExists('item_option_issues');
    Schema::dropIfExists('item_option_issue_comments');
    Schema::dropIfExists('item_subscriptions');
  }
};
