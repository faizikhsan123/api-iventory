<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    protected $fillable = [
        'part_number',
        'file',
        'category',
        'name',
        'brand',
        'type',
        'size',
        'unit',
        'current_stock',
        'status',
        'min_stock',
        'description',

    ];
}
