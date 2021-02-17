<?php

namespace App\Repositories\Aggregate;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use SmartyStreets\PhpSdk\StaticCredentials;
use SmartyStreets\PhpSdk\ClientBuilder;
use SmartyStreets\PhpSdk\US_Autocomplete\Lookup;

class AggregateRepository implements AggregateInterface
{

    public function __construct()
    {
        
    }

    public function autocompleteAddress($address)
    {
        $response = array();
        $authId = '62067afd-e532-edcc-6fae-1aafec411689';
        $authToken = '6p8I2ZnsmfiVPJ2SV34n';
        
        $staticCredentials = new StaticCredentials($authId, $authToken);
        $client = (new ClientBuilder($staticCredentials))->buildUSAutocompleteApiClient();

        $lookup = new Lookup($address);
        $client->sendLookup($lookup);
        foreach ($lookup->getResult() as $suggestion)
            array_push($response, $suggestion->getText());

        $lookup->addCityFilter("Ogden");
        $lookup->addStateFilter("IL");
        $lookup->addPrefer("Ogden, IL");
        $lookup->getGeolocateType(GEOLOCATE_TYPE_NONE);
        $lookup->setPreferRatio(0.333333);
        $lookup->setMaxSuggestions(5);

        $client->sendLookup($lookup);
        
        return response()->json($response);
    }
}
