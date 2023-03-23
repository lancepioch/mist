<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Steam extends Model
{
    use HasFactory, Searchable;

    protected $primaryKey = 'appid';

    protected $fillable = ['appid', 'name'];

    public function searchableAs()
    {
        return 'steamapps_index';
    }

    public function getScoutKeyName()
    {
        return 'appid';
    }

    public function rows()
    {
        return $this->hasMany(Row::class, 'appid');
    }
}
