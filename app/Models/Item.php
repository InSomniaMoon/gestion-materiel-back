<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
  use HasFactory;
  //     -- un item est un objet, une salle, une tente empreintable.
  // CREATE TABLE IF NOT EXISTS `items` (
  //   `id` BIGINT(11) NOT NULL AUTO_INCREMENT,
  //   `name` varchar(255) NOT NULL,
  //   `description` text NOT NULL,
  //   `category` varchar(255) NOT NULL,
  //   `usable` BOOLEAN NOT NULL DEFAULT true,
  //   PRIMARY KEY (`id`)
  // ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

  protected $fillable = [
    'name',
    'description',
    'category',
    'usable',
    'date_of_buy',
    'group_id',
    'category_id',
    'image',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  public function options()
  {
    return $this->hasMany(ItemOption::class, 'item_id');
  }

  public function optionIssues()
  {
    return $this->hasManyThrough(ItemOptionIssue::class, ItemOption::class);
  }

  public function events()
  {
    // Many-to-many with events via pivot table 'event_subscriptions'
    return $this->belongsToMany(Event::class, 'event_subscriptions', 'item_id', 'event_id')
      ->withTimestamps();
  }

  protected static function newFactory()
  {
    return ItemFactory::new();
  }

  public function group()
  {
    return $this->belongsTo(Group::class);
  }

  public function category()
  {
    return $this->belongsTo(ItemCategory::class);
  }

  public static $validation = [
    'name' => 'required|max:255',
    'description' => 'max:255',
    'category_id' => 'required|exists:item_categories,id',
    'usable' => 'boolean',
    'date_of_buy' => 'date|nullable', // date format: YYYY-MM-DD
  ];
}
