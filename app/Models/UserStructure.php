<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Thiagoprz\CompositeKey\HasCompositeKey;

class UserStructure extends Pivot
{
  use HasCompositeKey;

  // composite primary key
  protected $primaryKey = ['structure_id', 'user_id'];

  protected $table = 'user_structures';

  //
  protected $fillable = ['user_id', 'structure_id', 'role'];

  public $timestamps = false;

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function structure()
  {
    return $this->belongsTo(Structure::class);
  }
}
