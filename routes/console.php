<?php

use App\Models\Group;
use App\Models\UserGroup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('create_admin', function () {
  $this->info('Creating admin user');
  // ask for  'name', 'email', 'password', 'role', 'phone',

  $name = $this->ask('Enter name', 'Pierre Leroyer');
  $email = $this->ask('Enter email', 'pierre.leroyer69@gmail.com');
  do {
    $password = $this->secret('Enter password');
    $confirmPassword = $this->secret('Confirm password');
    if ($password !== $confirmPassword) {
      $this->error('Passwords do not match');
    }
  } while ($password !== $confirmPassword);
  $phone = $this->ask('Enter phone');
  // choose between the groups that are available
  $groups = Group::all()->pluck('name', 'id')->toArray();
  // choose between the groups that are available
  $group = $this->choice('Choose a group', $groups);

  $group = array_search($group, $groups);

  $user = new App\Models\User();
  $user->name = $name;
  $user->email = $email;
  $user->password = Illuminate\Support\Facades\Hash::make($password);
  $user->role = 'user';
  $user->phone = $phone;

  $this->info('Creation...');
  // begin transaction
  DB::beginTransaction();

  $user->save();
  UserGroup::create([
    'user_id' => $user->id,
    'group_id' => $group,
    'role' => 'admin',
  ]);
  DB::commit();
  $this->info('Admin user created');
})->purpose('Create an admin user');
