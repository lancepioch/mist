<?php

namespace App\Http\Livewire;

use App\Models\Row;
use App\Models\Steam;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ListGames extends Component  implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected function getTableQuery(): Builder
    {
        return Row::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->hidden(),
            Tables\Columns\ImageColumn::make('steam.banner')->width(184)->height(69),
            Tables\Columns\TextColumn::make('name')->wrap()->sortable()->searchable()
                ->url(fn (...$params) => "https://steamcommunity.com/app/{$params[2]->appid}"),
            Tables\Columns\TextColumn::make('acquired_at')->date()
                ->sortable(query: function (Builder $query, string $direction): Builder {
                return $query
                    ->orderBy('acquired_at', $direction);
            }),
            Tables\Columns\IconColumn::make('available')->boolean(),
            Tables\Columns\TextColumn::make('source')->wrap()->toggleable(true, true),
            Tables\Columns\TextColumn::make('price')->toggleable(true, true),
            Tables\Columns\TextColumn::make('sent_at')->hidden(),
            Tables\Columns\SelectColumn::make('appid')->options(function (...$params) {
                $row = $params[2]; /** @var Row $row */
                $options = $row->steam ? [$row->steam->appid => $row->steam->name] : [];
                $closest = Steam::search($row->name)->take(5)->get();
                $options += $closest->mapWithKeys(fn (Steam $steam) => [$steam->appid => $steam->name])->all();

                return $options;
            })->toggleable(true, true),
            Tables\Columns\TextColumn::make('steam.name')->wrap()->hidden(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('available')->default()
                ->query(fn (Builder $query): Builder => $query->where('used', false)),
            Tables\Filters\Filter::make('unavailable')
                ->query(fn (Builder $query): Builder => $query->where('used', true)),
            Tables\Filters\Filter::make('associated')
                ->query(fn (Builder $query): Builder => $query->whereNot('appid', '', 0)),
            Tables\Filters\Filter::make('unassociated')
                ->query(fn (Builder $query): Builder => $query->whereNull('appid')->orWhere('appid', '')),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'name';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }

    public function render()
    {
        return view('livewire.list-games');
    }
}
