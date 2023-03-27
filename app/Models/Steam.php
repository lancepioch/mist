<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Steam extends Model
{
    use HasFactory, Searchable;

    protected $primaryKey = 'appid';

    protected $fillable = ['appid', 'name'];

    public function banner(): Attribute
    {
        return Attribute::make(
            get: fn () => "https://cdn.cloudflare.steamstatic.com/steam/apps/$this->appid/capsule_184x69.jpg",
        );
    }

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
