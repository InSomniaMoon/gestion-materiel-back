<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
  protected $table = 'groups';

  protected $fillable = ['name', 'description', 'image'];

  public $timestamps = false;

  public function users()
  {
    return $this->belongsToMany(User::class, UserStructure::class, 'structure_id', 'user_id')
      ->using(UserStructure::class)
      ->withPivot('role');
  }

  public function userStructures()
  {
    return $this->hasMany(UserStructure::class, 'structure_id');
  }

  public function items()
  {
    // one group has many items
    return $this->hasMany(Item::class);
  }
}
