<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOptionIssue extends Model
{

    protected $fillable = [
        'item_option_id',
        'date_declaration',
        'date_resolution',
        'status'
    ];

    protected $hidden = [
        // 'created_at',
        'updated_at'
    ];

    public function comments()
    {
        return $this->hasMany(ItemOptionIssueComment::class);
    }

    public function itemOption()
    {
        return $this->belongsTo(ItemOption::class);
    }
}
