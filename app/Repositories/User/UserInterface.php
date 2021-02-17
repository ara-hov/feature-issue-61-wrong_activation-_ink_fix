<?php
namespace App\Repositories\User;

use App\Repositories\Core\CoreInterface;

interface UserInterface extends CoreInterface {
    public function getInvitedProperties($userId, $roleId);

    public function getClientList($userId, $roleId);

    public function getUserRoles($userId, $role);

    public function getUserLendsRealters($role, $userId);
}
 
