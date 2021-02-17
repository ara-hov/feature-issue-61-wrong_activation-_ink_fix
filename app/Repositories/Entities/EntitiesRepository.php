<?php

namespace App\Repositories\Entities;

use App\Models\Entity;
use App\Repositories\Core\CoreRepository;

class EntitiesRepository extends CoreRepository implements EntitiesInterface{
    
    public function __construct(Entity $model){
        parent::__construct($model);
    }



}
