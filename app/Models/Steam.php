<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Steam extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['app_id', 'name'];

    public function searchableAs()
    {
        return 'steamapps_index';
    }

    public function getScoutKeyName()
    {
        return 'appid';
    }
}
