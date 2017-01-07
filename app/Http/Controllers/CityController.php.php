<?php

namespace App\Http\Controllers;

use App\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function search(Request $request)
    {
        $res = City::search($request->get('city'))->get();
        if (isset($res[0])) {
            return $res[0];
        }

    }
}
