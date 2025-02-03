<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    //
  protected $table = 'refresh_tokens';

  protected $fillable = ['token', 'user_id', 'expires_at'];

  protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
