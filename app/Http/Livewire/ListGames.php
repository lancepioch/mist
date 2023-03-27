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
            // Tables\Columns\ImageColumn::make('author.avatar')->size(40)->circular(),
            // Tables\Columns\TextColumn::make('author.name'),
            /*Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'danger' => 'draft',
                    'warning' => 'reviewing',
                    'success' => 'published',
                ]), // */

            Tables\Columns\TextColumn::make('id'),
            Tables\Columns\TextColumn::make('name'),
            // Tables\Columns\TextColumn::make('key'),
            Tables\Columns\TextColumn::make('acquired_at'),
            Tables\Columns\IconColumn::make('used')->boolean()
                ->trueColor('primary')
                ->falseColor('warning'),
            Tables\Columns\TextColumn::make('source'),
            Tables\Columns\TextColumn::make('price'),
            // Tables\Columns\TextColumn::make('sent_to'),
            // Tables\Columns\TextColumn::make('sent_at'),
            Tables\Columns\TextInputColumn::make('appid'),
            Tables\Columns\TextInputColumn::make('appid'),
            Tables\Columns\SelectColumn::make('appid')->options(function (...$params) {
                $row = $params[2]; /** @var Row $row */
                $options = $row->steam ? [$row->steam->appid => $row->steam->name] : [];
                $closest = Steam::search($row->name)->take(5)->get();
                $options += $closest->mapWithKeys(fn (Steam $steam) => [$steam->appid => $steam->name])->all();

                return $options;
            }),
            Tables\Columns\TextColumn::make('steam.name'),
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

    public function render()
    {
        return view('livewire.list-games');
    }
}
