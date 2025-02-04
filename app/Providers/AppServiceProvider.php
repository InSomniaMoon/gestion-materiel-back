<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    config(['app.debug.sql' => env('APP_DEBUG_SQL', true)]);

    ResetPassword::createUrlUsing(function (User $user, string $token) {
      return env('FRONT_URL').'/auth/reset-password?token='.$token;
    });

    if (config('app.debug.sql')) {
      // log all queries
      $this->logQueries();
    }
  }

  public function logQueries(): void
  {
    DB::listen(function ($query) {
      Log::info(
        $query->sql,
        [
          'bindings' => $query->bindings,
          'time' => $query->time,
        ]
      );
    });
  }
}
