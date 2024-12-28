<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
        //
    Group::create([
      'name' => 'Groupe 1',
      'description' => 'Groupe 1',
    ]);

    Group::create([
      'name' => 'Groupe 2',
      'description' => 'Groupe 2',
    ]);
  }
}
