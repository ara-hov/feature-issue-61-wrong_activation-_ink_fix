<?php

namespace App\Repositories\Events;

use App\Repositories\Core\CoreInterface;

interface EventsInterface extends CoreInterface
{
    public function saveBidDocuemtns($data);
}
