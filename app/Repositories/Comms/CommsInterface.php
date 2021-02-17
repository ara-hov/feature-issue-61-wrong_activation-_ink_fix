<?php

namespace App\Repositories\Comms;

interface CommsInterface
{

    public function askCommsToSendSms($mobileNumber);

    public function askCommsToVerifyOtp($code);

    public function getUser($code);

    public function askCommsToSaveInvitations($data);

    public function askCommsToSaveUserReferrals($data);

    public function askCommsToCheckDuplicateInvitations($data);

    public function askCommsToGetUserNotifications($userId);

    public function askCommsToUpdateUserNotifications($userId);

    public function askCommsToSendEmail($to);

    public function askCommsToSendNotifications($userId, $notificationText, $notificationType);
}
 
