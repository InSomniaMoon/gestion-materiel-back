<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
  protected $table = 'events';

  protected $fillable = [
    'name',
    'start_date',
    'end_date',
    'unit_id',
    'user_id',
    'comment',
  ];

  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
  ];

  public function eventSubscriptions()
  {
    // Many-to-many with items via pivot table 'event_subscriptions'
    return $this->belongsToMany(Item::class, 'event_subscriptions', 'event_id', 'item_id')
      ->withTimestamps();
  }

  public function organizer()
  {
    return $this->belongsTo(User::class);
  }

  public function unit()
  {
    return $this->belongsTo(Unit::class);
  }
}
