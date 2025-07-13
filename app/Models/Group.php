<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
  protected $table = 'groups';

  protected $fillable = ['name', 'description'];

  public $timestamps = false;

  public function users()
  {
    return $this->belongsToMany(User::class, UserGroup::class, 'group_id', 'user_id')
      ->using(UserGroup::class)
      ->withPivot('role');
  }

  public function userGroups()
  {
    return $this->hasMany(UserGroup::class, 'group_id');
  }

  public function items()
  {
    // one group has many items
    return $this->hasMany(Item::class);
  }
}
