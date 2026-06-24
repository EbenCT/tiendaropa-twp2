<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $table = 'page_visit';

    protected $fillable = ['page_url', 'page_name', 'visit_count', 'last_visited_at'];

    protected $casts = [
        'last_visited_at' => 'datetime',
        'visit_count'     => 'integer',
    ];
}
