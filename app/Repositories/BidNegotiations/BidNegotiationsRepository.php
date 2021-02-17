<?php
namespace App\Repositories\BidNegotiations;

use App\Models\BidNegotiations;
use App\Repositories\Core\CoreRepository;

class BidNegotiationsRepository extends CoreRepository  implements BidNegotiationsInterface
{
    public function __construct(BidNegotiations $model)
        {
            parent::__construct($model);
        }
}
