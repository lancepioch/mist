<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Revolution\Google\Sheets\Facades\Sheets;

class Row extends Model
{
    use \Sushi\Sushi;

    protected $schema = [
        'id' => 'integer',
        'appid' => 'integer',
        'sent_at' => 'date',
        'acquired_at' => 'date',
        'used' => 'boolean',
    ];

    public function steam(): BelongsTo
    {
        return $this->belongsTo(Steam::class, 'appid', 'appid', 'steam');
    }

    public function getRows()
    {
        return cache()->remember('steam-games-collection', now()->addMinute(), self::retrieveGames(...))->toArray();
    }

    public static function retrieveGames(): Collection
    {
        $token = [
            'access_token' => config('mist.tokens.access'),
            'refresh_token' => config('mist.tokens.refresh'),
        ];

        $rows = Sheets::setAccessToken($token)
            ->spreadsheet(config('mist.sheet'))
            ->sheet('Steam')->get();

        $header = $rows->pull(0);
        $rows = Sheets::collection(header: $header, rows: $rows);

        $rows->map(function ($row) {
            $row['used'] = match ($row['used']) {
                null, '' => null,
                'TRUE', 'true', true => true,
                'FALSE', 'false', false => false,
                default => null,
            };

            return $row;
        });

        return $rows->values();
    }

    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) use ($class) {
            if (!$instance->getConnectionName()) {
                $instance->setConnection($this->getConnectionResolver()->getDefaultConnection());
                parent::newRelatedInstance($class);
            }
        });
    }
}