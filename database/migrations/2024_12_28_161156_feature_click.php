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
    // create features table
    Schema::create('features', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->timestamps();
    });
    // create feature_clicks table
    Schema::create('feature_clicks', function (Blueprint $table) {
      $table->id();
      $table->foreignId('feature_id');
      $table->foreignId('user_id');
      $table->integer('clicks')->default(0);
      $table->unique(['feature_id', 'user_id']);
      $table->index(['feature_id', 'user_id']);

      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
        //
    Schema::dropIfExists('feature_clicks');
    Schema::dropIfExists('features');
  }
};
