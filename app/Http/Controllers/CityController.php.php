<?php

namespace App\Http\Controllers;

use App\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use TeamTNT\TNTSearch\TNTSearch;

class CityController extends Controller
{
    public function search(Request $request)
    {
        $res = City::search($request->get('city'))->get();
        if (isset($res[0])) {
            return [
                'didyoumean' => false,
                'data'       => $res[0]
            ];
        }

        //if we don't find anything we'll try to guess
        $TNTIndexer = new TNTIndexer;
        $trigrams   = $TNTIndexer->buildTrigrams($request->get('city'));

        $tnt = new TNTSearch;

        $driver = config('database.default');
        $config = config('scout.tntsearch') + config("database.connections.$driver");

        $tnt->loadConfig($config);
        $tnt->setDatabaseHandle(app('db')->connection()->getPdo());

        $tnt->selectIndex("cityngrams.index");
        $res  = $tnt->search($trigrams, 10);
        $keys = collect($res['ids'])->values()->all();

        $suggestions = City::whereIn('id', $keys)->get();

        $suggestions->map(function ($city) use ($request) {
            $city->distance = levenshtein($request->get('city'), $city->city);
        });

        $sorted = $suggestions->sort(function ($a, $b) {
            if ($a->distance === $b->distance) {
                if ($a->population === $b->population) {
                    return 0;
                }
                return $a->population > $b->population ? -1 : 1;
            }
            return $a->distance < $b->distance ? -1 : 1;
        });

        return [
            'didyoumean' => true,
            'data'       => $sorted->values()->all()
        ];
    }
}
