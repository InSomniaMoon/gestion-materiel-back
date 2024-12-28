<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOptionIssue extends Model
{
  protected $fillable = [
    'item_option_id',
    'date_resolution',
    'status',
    'value',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  protected $casts = [
    'date_resolution' => 'date',
  ];

  // rename created_at to date_declaration
  public function getCreatedAtAttribute($value)
  {
    return $this->date_declaration;
  }

  public function comments()
  {
    return $this->hasMany(ItemOptionIssueComment::class);
  }

  public function itemOption()
  {
    return $this->belongsTo(ItemOption::class);
  }
}
