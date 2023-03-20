<?php

namespace App\Console\Commands;

use App\Http\Integrations\Steam\Requests\GetAppList;
use App\Http\Integrations\Steam\SteamConnector;
use App\Models\Steam;
use Illuminate\Console\Command;

class PopulateSteam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steam:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes Steam apps table';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $steam = new SteamConnector();
        $response = $steam->send(new GetAppList());

        $body = $response->json();
        $apps = $body['applist']['apps'];
        $appChunks = collect($apps)->lazy()->chunk(5000);

        $bar = $this->output->createProgressBar(count($apps));
        $bar->start();

        Steam::query()->truncate();
        foreach ($appChunks as $apps) {
            Steam::query()->insert($apps->toArray());
            $bar->advance($apps->count());
        }

        $bar->finish();
    }
}
