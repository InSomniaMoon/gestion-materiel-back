<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Thiagoprz\CompositeKey\HasCompositeKey;

class UserUnit extends Pivot
{
  use HasCompositeKey;

  protected $primaryKey = ['user_id', 'unit_id'];

  protected $table = 'user_unit';

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
