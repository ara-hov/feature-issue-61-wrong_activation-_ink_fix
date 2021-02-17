<?php

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use App\Http\Controllers\User\UserController;

Route::post('user-login', [AccessTokenController::class, 'issueToken'])
    ->middleware(['api-auth', 'verify_email','throttle']);

Route::post('user-signup', 'User\UserController@signup');
Route::post('forgot-password', 'User\UserController@forgotPassword');
Route::post('reset-password', 'User\UserController@resetPassword');

Route::post('address', 'Comms\CommsController@adddressAutocomplete');

Route::get('send_test_email', 'Comms\CommsController@mail');
Route::post('verify', 'User\UserController@verify');
Route::group(['middleware' => ['auth:api']], function () {
    //password forgot

    Route::post('send-invitation', 'Comms\CommsController@sendInvitaion');
    Route::post('send-property-invitation', 'Comms\CommsController@sendPropertyInvitaion');
    Route::post('send-referrals', 'Comms\CommsController@sendReferrals');
    Route::get('get-user-notifications', 'Comms\CommsController@getuserNotifications');
    Route::post('update-user-notifications', 'Comms\CommsController@updateUserNotifications');
    Route::post('update-user', 'User\UserController@updateUser');
    Route::post('update-user-role', 'User\UserController@updateUserRoleType');
    Route::post('business-profile', 'User\UserController@submitBusinessProfile');
    Route::get('get-user-profile', 'User\UserController@userProfile');
    Route::post('submit-property', 'Entities\EntitiesController@submitProperty');
    Route::post('submit-pre-approvals', 'Entities\EntitiesController@submitPreApprovals');
    Route::get('get-pre-approvals/{id}', 'Entities\EntitiesController@getPreApprovals');
    Route::get('my-properties', 'User\UserController@properties');
    Route::get('property-details/{id}', 'User\UserController@getSingleProperty');
    Route::get('bid-negotiation/{id}', 'User\UserController@getBidNegotiations');
    Route::get('bid-history/{id}', 'User\UserController@getBidHistory');
    Route::get('get-user-act/{roleId}', 'User\UserController@getUserByRoles');
    //Questionnaire Route
    Route::get('all-questions/{role}/{buying_room_id}', 'Questionnaire\QuestionnaireController@getAllQuestions');
    // Submit Feedback from user
    Route::post('submit-feedback', 'Feedback\FeedbackController@submitFeedback');

    Route::post('verify-mobile-number', 'Comms\CommsController@verifyUserMobileNumber');
    Route::post('verify-mobile-otp', 'Comms\CommsController@verifyUserMobileOtp');
    Route::post('process-bid', 'Events\EventsController@bidProcessing');
    Route::get('bid-details/{id}', 'Events\EventsController@getSingleBid');
    Route::post('update-bid-status', 'Events\EventsController@updatebidStatus');
    Route::post('update-bid', 'Events\EventsController@updatebid');
    Route::post('negotiate-bid', 'Events\EventsController@bidNegotiate');
    Route::get('invited-proprties', 'Entities\EntitiesController@getInvitation');
    Route::get('client-list', 'Entities\EntitiesController@getClientList');
    Route::get('props-offers', 'Entities\EntitiesController@getOfferes');
    // Route::get('accepted-bid/{id}', 'Entities\EntitiesController@acceptedBid');
    Route::post('buying-room/{id}', 'Entities\EntitiesController@createBuyingRoom');
    Route::get('buying-room/{id}', 'Entities\EntitiesController@buyingRoom')->middleware('buyingroom');
    Route::post('buying-room-progress', 'Entities\EntitiesController@buyingRoomProgress');
    Route::get('buying-room-progress/{id}', 'Entities\EntitiesController@getBuyingRoomProgress');
    Route::get('download-attachment/{id}', 'Comms\CommsController@getAttachment');
    Route::get('check-list/{id}', 'CheckList\CheckListController@index');
    Route::post('check-list/{id}', 'CheckList\CheckListController@update');
    Route::post('cancel-deal', 'Events\EventsController@cancelDeal');
    Route::post('buying-room-documents-download/{id}', 'Comms\CommsController@buyingroomDocumentDownload');
    Route::get('buying-room-documents/{id}', 'Comms\CommsController@buyingroomDocument')->middleware('buyingroom');
    Route::get('user-profile/{id}', 'User\UserController@show');

    Route::get('user-buyingrooms', 'Entities\EntitiesController@showBuyingRooms');

    Route::get('closed-deals', 'Entities\EntitiesController@closedDeals');

    /*Resolving Calls*/
    Route::group(['prefix' => 'resolve'], function () {
        Route::get('dashboard-stats', 'Comms\CommsController@dashboardStats');
    });

    /* CHAT ROUTES */
    Route::post('chat', 'Chat\ChatController@create');
    Route::get('chat/{id}', 'Chat\ChatController@show');
    Route::post('chat/{id}/message', 'Chat\ConversationController@create');
    // Route::get('chat/{id}/message', 'Chat\ConversationController@show');
    Route::get('chat/{id}/message', 'Chat\ConversationController@index');
    Route::get('user/chats', 'Chat\ChatController@index');
    Route::get('mark-chat/{id}', 'Chat\ChatController@markAsRead');
    Route::get('message-notify', 'Chat\ChatController@messageNotification');
    Route::get('message-notify/update/{id}', 'Chat\ChatController@updateChatNotification');
    /* CHAT ROUTES */
});
