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
    Schema::table('events', function (Blueprint $table) {
      // make the user_id column nullable and change the on delete behavior
      $table->unsignedBigInteger('user_id')->nullable()->default(null)->change();
      $table->dropForeign(['user_id']);
      $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
    });

    Schema::table('structures', function (Blueprint $table) {
      // make the user_id column nullable and change the on delete behavior
      $table->string('color')->default('#6f74a6')->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('events', function (Blueprint $table) {
      // revert the user_id column to not nullable and change the on delete behavior back
      $table->unsignedBigInteger('user_id')->nullable(false)->default(0)->change();
      $table->dropForeign(['user_id']);
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
  }
};
