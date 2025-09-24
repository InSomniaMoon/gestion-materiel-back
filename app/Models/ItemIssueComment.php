<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemIssueComment extends Model
{
  protected $fillable = [
    'item_issue_id',
    'comment',
    'user_id',
  ];

  protected $hidden = [
    'updated_at',
  ];

  public function author()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
