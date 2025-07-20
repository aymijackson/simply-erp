<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\StoreShelf;
use App\Models\City;

class LocationController extends Controller
{
    public function getStates($country_id)
    {
        return response()->json(State::where('country_id', $country_id)->orderBy('name')->get());
    }

    public function getCities($state_id)
    {
        return response()->json(City::where('state_id', $state_id)->orderBy('name')->get());
    }

    public function shelvesByStore($storeId)
    {
        $shelves = StoreShelf::where('store_id', $storeId)->get(['id', 'name']);
        return response()->json($shelves);
    }
}
