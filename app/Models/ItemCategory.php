<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
  protected $table = 'item_categories';

  protected $fillable = [
    'name',
  ];

  public $timestamps = false;

  public function items()
  {
    return $this->hasMany(Item::class, 'category_id');
  }
}
