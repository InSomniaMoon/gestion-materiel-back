<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserUnit extends Model
{
  protected $table = 'unit_users';

  protected $fillable = ['user_id', 'unit_id'];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function unit()
  {
    return $this->belongsTo(Unit::class);
  }
}
