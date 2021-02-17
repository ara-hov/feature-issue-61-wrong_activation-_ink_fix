<?php

namespace App\Services\Aggregate;

use App\Repositories\Aggregate\AggregateInterface;
use Illuminate\Support\Facades\Storage;

class AggregateService
{
    private $aggregate;

    public function __construct( AggregateInterface $aggregate )
    {
        $this->aggregate = $aggregate;
    }

    public function autocompleteAddress($address)
    {
        return $this->aggregate->autocompleteAddress($address);
    }

    public function uploadOnAws($data)
    {
        return $path = Storage::disk('s3')->putFileAs($data['directory'],$data['file'],$data['file_name'] ,$data['privacy']);
    }

}
