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
        // one user is in one group, one group has many users
        return $this->hasMany(User::class);
    }

    public function items()
    {
        // one group has many items
        return $this->hasMany(Item::class);
    }
}
