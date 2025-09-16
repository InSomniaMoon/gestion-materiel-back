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
    Schema::rename('groups', 'structures');
    Schema::table('structures', function (Blueprint $table) {
      $table->bigInteger('code_structure')->unique();
      $table->string('nom_structure')->nullable();
      $table->string('color')->default('#ffffff');
      $table->string('type')->default('group');
      $table->bigInteger('parent_code')->nullable();
    });
    Schema::table('structures', function (Blueprint $table) {
      $table->foreign('parent_code')->references('code_structure')->on('structures')->onDelete('set null');
    });
    Schema::create('user_structures', function (Blueprint $table) {
      $table->string('role')->default('user');
      $table->unique(['structure_id', 'user_id']);
      $table->primary(['structure_id', 'user_id']);
      // $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
      $table->foreignId('structure_id')->nullable()->constrained()
        ->onDelete('cascade');

      $table->foreignId('user_id')->constrained()->onDelete('cascade');
    });

    Schema::table('item_categories', function (Blueprint $table) {
      $table->renameColumn('group_id', 'structure_id');
    });

    Schema::table('events', function (Blueprint $table) {
      $table->dropForeign(['unit_id']);
      $table->renameColumn('unit_id', 'structure_id');
      $table->foreign('structure_id')->references('id')->on('structures')->onDelete('cascade');
    });
    Schema::drop('user_group');
    Schema::drop('unit_users');
    Schema::drop('units');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
  }
};
