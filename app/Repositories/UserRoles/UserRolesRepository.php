<?php
namespace App\Repositories\UserRoles;

use App\Models\UserRoles;
use App\Repositories\Core\CoreRepository;

class UserRolesRepository extends CoreRepository implements UserRolesInterface
{
    public function __construct(UserRoles $model)
        {
            parent::__construct($model);
        }
}
