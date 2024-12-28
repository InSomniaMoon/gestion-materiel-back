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
    // create table groups
    Schema::create('groups', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->text('description')->nullable();
    });

    // add foreign key to users
    Schema::table('users', function (Blueprint $table) {
      $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
    });

    // add foreign key to items
    Schema::table('items', function (Blueprint $table) {
      $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
        //
    Schema::table('users', function (Blueprint $table) {
      $table->dropForeign(['group_id']);
      $table->dropColumn('group_id');
    });

    Schema::table('items', function (Blueprint $table) {
      $table->dropForeign(['group_id']);
      $table->dropColumn('group_id');
    });

    Schema::dropIfExists('groups');
  }
};
