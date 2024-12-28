<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class UserGroup extends Model
{
  use HasCompositeKey;

  // composite primary key
  protected $primaryKey = ['group_id', 'user_id'];

  protected $table = 'user_group';

  protected $fillable = ['user_id', 'group_id', 'role'];

  public function user()
  {
    // one user is in one group, one group has many users
    return $this->belongsTo(User::class);
  }

  public function group()
  {
    // one group has many users
    return $this->belongsTo(Group::class);
  }
}
