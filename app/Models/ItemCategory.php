<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
  protected $table = 'item_categories';

  protected $fillable = [
    'name',
    'group_id',
  ];

  public $timestamps = false;

  public function items()
  {
    return $this->hasMany(Item::class, 'category_id');
  }

  public function group()
  {
    return $this->belongsTo(Group::class);
  }
}
