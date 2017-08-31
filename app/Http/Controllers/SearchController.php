<?php

namespace App\Http\Controllers;

use App\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;

class SearchController extends Controller
{

    public function search(Request $request)
    {
        $query   = $request->get('city');
        $results = $this->orderByPopulation(City::search($query)->get());

        if ($results->count() && $this->isExactMatch($request, $results[0])) {
            return [
                'didyoumean' => false,
                'data'       => $results[0]
            ];
        }

        //if we don't find anything we'll try to guess
        return [
            'didyoumean' => true,
            'data'       => $this->getSuggestions($query)
        ];
    }

    public function isExactMatch($request, $result)
    {
        return strtolower($request->get('city')) == strtolower($result->city);
    }

    public function getSuggestions($query)
    {
        $indexer  = new TNTIndexer;
        $trigrams = $indexer->buildTrigrams($query);

        $suggestions = City::search($trigrams)->take(500)->get();

        return $this->sortByLevenshteinDistance($suggestions, $query);
    }

    public function sortByLevenshteinDistance($suggestions, $query)
    {
        $suggestions = $suggestions->filter(function ($city) use ($query) {
            $city->distance = levenshtein($query, $city->city);
            if ($city->distance < 3) {
                return true;
            }
            return false;
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

        return $sorted->values()->take(5);
    }

    public function orderByPopulation($results)
    {
        $sorted = $results->sort(function ($a, $b) {
            if ($a->population === $b->population) {
                return 0;
            }
            return $a->population > $b->population ? -1 : 1;
        });
        return $sorted->values();
    }
}
