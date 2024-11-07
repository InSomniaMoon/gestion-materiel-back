<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOptionIssueComment extends Model
{
    //
    protected $fillable = [
        'item_option_issue_id',
        'comment',
        'user_id',
    ];

    protected $hidden = [
        'updated_at'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
