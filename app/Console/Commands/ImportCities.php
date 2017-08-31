<?php

namespace App\Console\Commands;

use App\City;
use DB;
use Illuminate\Console\Command;
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

        $pdo = app('db')->connection()->getPdo();

        $this->stmt = $pdo->prepare("INSERT INTO cities (country, city, region, population, latitude, longitude, n_grams) VALUES (:country, :city, :region, :population, :latitude, :longitude, :n_grams)");

        DB::beginTransaction();
        foreach (new \SplFileObject(storage_path().'/worldcitiespop.txt') as $lineNumber => $lineContent) {
            if ($lineNumber == 0) {
                continue;
            }
            $line = explode(',', $lineContent);
            if (count($line) < 7) {
                continue;
            }

            $this->insertCity($line);
        }
        DB::commit();
    }

    public function insertCity($cityArray)
    {
        //we enter only cities wich have a population set
        if ($cityArray[4] < 1) {
            return;
        }

        $city      = utf8_encode($cityArray[2]);
        $ngrams    = $this->createNGrams($city);
        $latitude  = trim($cityArray[5]);
        $longitude = trim($cityArray[6]);

        $this->stmt->bindParam(':country', $cityArray[0]);
        $this->stmt->bindParam(':city', $city);
        $this->stmt->bindParam(':region', $cityArray[3]);
        $this->stmt->bindParam(':population', $cityArray[4]);
        $this->stmt->bindParam(':latitude', $latitude);
        $this->stmt->bindParam(':longitude', $longitude);
        $this->stmt->bindParam(':n_grams', $ngrams);
        $this->stmt->execute();
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
