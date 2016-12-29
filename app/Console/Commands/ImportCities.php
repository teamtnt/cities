<?php

namespace App\Console\Commands;

use App\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;

class ImportCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports cities from MaxMind';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->tnt = new TNTIndexer;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Downloading worldcitiespop.txt.gz from MaxMind");

        $gzipedFile  = storage_path().'/worldcitiespop.txt.gz';
        $unZipedFile = storage_path().'/worldcitiespop.txt';

        if (!file_exists($gzipedFile)) {
            file_put_contents($gzipedFile, fopen("http://download.maxmind.com/download/worldcities/worldcitiespop.txt.gz", 'r'));
        }

        $this->info("Unziping worldcitiespop.txt.gz to worldcitiespop.txt");

        $this->line("\n\nInserting cities to database");

        if (!file_exists($unZipedFile)) {
            $this->unzipFile($gzipedFile);
        }

        $cities = fopen(storage_path().'/worldcitiespop.txt', "r");

        $lineNumber = 0;
        $bar = $this->output->createProgressBar(3173959);

        if ($cities) {
            while (!feof($cities)) {
                $line = fgets($cities, 4096);

                if ($lineNumber == 0) {
                    $lineNumber++;
                    continue;
                }
                //we skip the first line since it's the header
                $line = explode(',', $line);
                $this->insertCity($line);
                $lineNumber++;
                $bar->advance();
            }
            fclose($cities);
        }
        $bar->finish();
    }

    public function insertCity($cityArray)
    {
        $city             = new City;
        $city->country    = $cityArray[0];
        $city->city       = utf8_encode($cityArray[2]);
        $city->region     = $cityArray[3];
        $city->population = 0;
        if ($cityArray[4] != "") {
            $city->population = $cityArray[4];
        }
        $city->latitude  = trim($cityArray[5]);
        $city->longitude = trim($cityArray[6]);
        $city->n_grams   = $this->createNGrams($city->city);
        $city->save();

    }

    public function unzipFile($from)
    {

        // Raising this value may increase performance
        $buffer_size   = 4096; // read 4kb at a time
        $out_file_name = str_replace('.gz', '', $from);

        // Open our files (in binary mode)
        $file     = gzopen($from, 'rb');
        $out_file = fopen($out_file_name, 'wb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);
    }

    public function createNGrams($word) 
    {
        return utf8_encode($this->tnt->buildTrigrams($word));
    }
}
