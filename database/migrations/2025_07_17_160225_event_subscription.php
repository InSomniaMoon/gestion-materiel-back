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
    // rename item_subscriptions to event_subscriptions
    Schema::drop('item_subscriptions');

    Schema::create('events', function (Blueprint $table) {
      $table->id();
      $table->dateTime('start_date');
      $table->dateTime('end_date');
      $table->string('name');
      $table->string('comment', 500)->nullable();
      $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
      $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
      $table->timestamps();
    });

    Schema::create('event_subscriptions', function (Blueprint $table) {
      $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
      $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
      $table->primary(['item_id', 'event_id']);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::create('item_subscriptions', function (Blueprint $table) {
    });

    Schema::drop('event_subscriptions');
    Schema::drop('events');
  }
};
