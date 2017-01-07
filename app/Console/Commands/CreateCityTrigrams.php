<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TeamTNT\TNTSearch\TNTSearch;

class CreateCityTrigrams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'city:trigrams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an index of city trigrams';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Creating index of city trigrams");
        $tnt = new TNTSearch;

        $driver = config('database.default');
        $config = config('scout.tntsearch') + config("database.connections.$driver");

        $tnt->loadConfig($config);
        $tnt->setDatabaseHandle(app('db')->connection()->getPdo());

        $indexer = $tnt->createIndex('cityngrams.index');
        $indexer->query('SELECT id, n_grams FROM cities;');
        $indexer->setLanguage('no');
        $indexer->run();
    }
}
