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
  ];

  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
  ];

  public function eventSubscriptions()
  {
    return $this->belongsToMany(Item::class, EventSubscription::class, 'event_id', 'item_id');
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
