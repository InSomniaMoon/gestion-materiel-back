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
    Schema::create('units', function (Blueprint $table) {
      $table->id();
      $table->foreignId('group_id')->references('id')->on('groups')->constrained()->onDelete('cascade');
      $table->foreignId('responsible_id')->nullable()->references('id')->on('users')->constrained()->onDelete('set null');
      $table->string('name');
      $table->string('color', length: 7);
      $table->timestamps();
    });

    Schema::create('unit_users', function (Blueprint $table) {
      $table->primary(['unit_id', 'user_id']);
      $table->foreignId('unit_id')->references('id')->on('units')->constrained()->onDelete('cascade');
      $table->foreignId('user_id')->references('id')->on('users')->constrained()->onDelete('cascade');
    });

    // the table subscriptions will have a foreign key to the units table
    Schema::table('item_subscriptions', function (Blueprint $table) {
      $table->foreignId('unit_id')->nullable()->references('id')->on('units')->constrained()->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('item_subscriptions', function (Blueprint $table) {
      $table->dropForeign(['unit_id']);
      $table->dropColumn('unit_id');
    });
    Schema::dropIfExists('unit_users');
    Schema::dropIfExists('units');
  }
};
