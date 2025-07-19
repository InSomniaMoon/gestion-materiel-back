<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CompositeKey\HasCompositeKey;

class EventSubscription extends Model
{
  use HasCompositeKey;

  protected $primaryKey = ['item_id', 'event_id'];

  protected $fillable = [
    'item_id',
    'event_id',
  ];

  // casts
  protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
  ];

  public function item()
  {
    return $this->belongsTo(Item::class);
  }

  public function event()
  {
    return $this->belongsTo(Event::class);
  }
}
