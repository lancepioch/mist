<?php

namespace App\Models;

use ArrayAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Revolution\Google\Sheets\Facades\Sheets;

class Row extends Model
{
    use \Sushi\Sushi;

    public const CACHE_KEY = 'steam-games-collection';

    protected $hidden = [
        'key',
        'sent_to',
    ];

    public function available(): Attribute
    {
        return Attribute::make(get: fn () => $this->used === null ? null : ! $this->used);
    }

    public function steam(): BelongsTo
    {
        return $this->belongsTo(Steam::class, 'appid', 'appid', 'steam');
    }

    public function getRows()
    {
        return self::getRowsFromCache();
    }

    public static function getRowsFromCache(): array
    {
        return cache()->remember(self::CACHE_KEY, now()->addMinute(), self::retrieveGames(...));
    }

    public static function setCacheRow($id, $key, $value)
    {
        $rows = self::getRowsFromCache();
        $index = collect($rows)->search(fn ($item, $k) => $item['id'] == $id);

        $rows[$index][$key] = $value;

        return cache()->put(self::CACHE_KEY, $rows, now()->addMinute());
    }

    public function save(array $options = [])
    {
        $changes = $this->getDirty();
        if ($newAppId = $changes['appid'] ?? null) {
            $token = [
                'access_token' => '',
                'refresh_token' => config('mist.tokens.refresh'),
            ];

            $rowN = $this->id + 1;
            Sheets::setAccessToken($token)
                ->spreadsheet(config('mist.sheet'))
                ->range("J$rowN")
                ->sheet('Steam')
                ->update([[$newAppId]]);

            self::setCacheRow($this->id, 'appid', $newAppId);
        }

        return parent::save($options);
    }

    public static function retrieveGames(): array
    {
        $token = [
            'access_token' => '',
            'refresh_token' => config('mist.tokens.refresh'),
        ];

        $rows = Sheets::setAccessToken($token)
            ->spreadsheet(config('mist.sheet'))
            ->sheet('Steam')
            ->get();

        $header = $rows->pull(0);
        $rows = Sheets::collection(header: $header, rows: $rows);

        /** @var Collection $games */
        $games = $rows
            ->filter(fn ($row) => ! empty($row['name']))
            ->map(self::convertUsedColumnToBool(...))
            ->map(self::convertColumnsToDatetime(...))
            ->map(self::convertAllFalseyValuesToNull(...));

        $bestCount = 0;
        $bestIndex = $games->reduce(function ($carry, $item, $key) use (&$bestCount) {
            if (($count = $item->filter()->count()) > $bestCount) {
                $bestCount = $count;

                return $key;
            }

            return $carry;
        }, 0);

        $games = $games->values();

        // Swap first row with most filled out row
        [$games[0], $games[$bestIndex]] = [$games[$bestIndex], $games[0]];

        return $games->toArray();
    }

    public static function convertUsedColumnToBool(ArrayAccess $array): ArrayAccess
    {
        $array['used'] = match ($array['used']) {
            'TRUE', 'true', true => true,
            'FALSE', 'false', false => false,
            default => null,
        };

        return $array;
    }

    public static function convertColumnsToDatetime(ArrayAccess $array): ArrayAccess
    {
        if ($array['acquired_at']) {
            $array['acquired_at'] = Carbon::createFromFormat('n/j/Y', $array['acquired_at'])->setTime(0, 0, 0);
        }

        if ($array['sent_at']) {
            $array['sent_at'] = Carbon::createFromFormat('n/j/Y', $array['sent_at'])->setTime(0, 0, 0);
        }

        return $array;
    }

    public static function convertAllFalseyValuesToNull(ArrayAccess $array): ArrayAccess
    {
        foreach ($array as $key => $value) {
            if (is_bool($value)) {
                continue;
            }

            if (empty($array[$key])) {
                $array[$key] = null;
            }
        }

        return $array;
    }

    public static function appidOptions(...$params)
    {
        $row = $params[2]; /** @var Row $row */
        $options = $row->steam ? [$row->steam->appid => $row->steam->name] : [];
        $closest = Steam::search($row->name)->take(5)->get();
        $options += $closest->mapWithKeys(fn (Steam $steam) => [$steam->appid => $steam->name])->all();

        return $options;
    }

    protected function newRelatedInstance($class)
    {
        return tap(new $class, function ($instance) use ($class) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->getConnectionResolver()->getDefaultConnection());
                parent::newRelatedInstance($class);
            }
        });
    }
}
