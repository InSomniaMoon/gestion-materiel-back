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
    Schema::create('item_categories', function (Blueprint $table) {
      $table->id();
      $table->string('name')->unique();
    });

    Schema::table('items', function (Blueprint $table) {
      $table->foreignId('category_id')->nullable()->constrained('item_categories')->onDelete('set null');
      $table->dropColumn('category');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('items', function (Blueprint $table) {
      $table->string('category');
      $table->dropForeign(['category_id']);
      $table->dropColumn('category_id');
    });

    Schema::dropIfExists('item_categories');
  }
};
