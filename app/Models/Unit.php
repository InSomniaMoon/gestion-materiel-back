<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
  //
  protected $table = 'units';

  protected $fillable = ['name', 'abbreviation', 'color', 'group_id', 'responsible_id'];

  public function group()
  {
    return $this->belongsTo(Group::class);
  }

  public function responsible()
  {
    return $this->belongsTo(User::class, 'responsible_id');
  }

  public function chiefs()
  {
    return $this->belongsToMany(User::class, 'unit_users', 'unit_id', 'user_id', );
  }
}
