<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOptionIssue extends Model
{
    // CREATE TABLE IF NOT EXISTS `item_option_issues` (
    //     `id` BIGINT NOT NULL AUTO_INCREMENT,
    //     `item_option_id` BIGINT NOT NULL,
    //     `date_declaration` datetime NOT NULL,
    //     `date_resolution` datetime,
    //     `comment` text,
    //     `status` varchar(255) NOT NULL,
    //     PRIMARY KEY (`id`),
    //     FOREIGN KEY (`item_option_id`) REFERENCES `item_options` (`id`) ON DELETE CASCADE
    //   ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

    protected $fillable = [
        'item_option_id',
        'date_declaration',
        'date_resolution',
        'status'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function itemOption()
    {
        return $this->belongsTo(ItemOption::class);
    }
}
