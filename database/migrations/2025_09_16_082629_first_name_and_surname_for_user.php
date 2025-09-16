<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('firstname')->nullable()->after('firstname');
      $table->string('lastname')->nullable()->after('firstname');
      // update existing users to have first part of name as firstname and last part as lastname
    });

    User::all()->each(function (User $user) {
      $nameParts = explode(' ', $user->name, 2);
      $user->firstname = $nameParts[0];
      $user->lastname = $nameParts[1] ?? null;
      $user->save();
    });

    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('name');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('name')->nullable()->after('id');
    });

    // update existing users to have name as concatenation of firstname and lastname
    User::all()->each(function (User $user) {
      $user->name = trim(
        $user->firstname.' '.$user->lastname
      );
      $user->save();
    });

    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['firstname', 'lastname']);
    });
  }
};
