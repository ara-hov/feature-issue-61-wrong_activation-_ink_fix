<?php

namespace App\Repositories\Events;

use App\Models\ProcessingBids;
use App\Repositories\Core\CoreRepository;

use App\Models\BidDocuments;

class EventsRepository extends CoreRepository implements EventsInterface
{
    public function __construct(ProcessingBids $model)
    {
        parent::__construct($model);
    }

    public function saveBidDocuemtns($data)
    {
        $bDocs = new BidDocuments();
        $bDocs->bid_id = $data['bid_id'];
        $bDocs->bid_document = $data['bid_document'];
        $bDocs->save();
        return $bDocs;
    }
}
