<?php

use App\Models\Group;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Artisan::command("create_admin", function () {
    $this->info("Creating admin user");
    // ask for  'name', 'email', 'password', 'role', 'phone',

    $name = $this->ask("Enter name");
    $email = $this->ask("Enter email");
    do {
        $password = $this->secret("Enter password");
        $confirmPassword = $this->secret("Confirm password");
        if ($password !== $confirmPassword) {
            $this->error("Passwords do not match");
        }
    } while ($password !== $confirmPassword);
    $phone = $this->ask("Enter phone");
    // choose between the groups that are available
    $groups = Group::all()->pluck('name', 'id')->toArray();
    Log::info($groups);
    // choose between the groups that are available
    $group = $this->choice("Choose a group", $groups, 0);


    $user = new \App\Models\User();
    $user->name = $name;
    $user->email = $email;
    $user->password = \Illuminate\Support\Facades\Hash::make($password);
    $user->role = "admin";
    $user->phone = $phone;
    $user->group_id = array_search($group, $groups);;

    $this->info("Creation...");

    $user->save();
    $this->info("Admin user created");
})->purpose('Create an admin user');
