<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Matching\SchemeValidator;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_group', function (Blueprint $table) {
            $table->timestamps();
            $table->string('role')->default('member');
            $table->unique(['group_id', 'user_id']);
            // set composite keys
            $table->primary(['group_id', 'user_id']);
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            // remove the group_id column
            $table->dropColumn('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_group');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
