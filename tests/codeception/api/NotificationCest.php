<?php

namespace rest\api;

use humhub\modules\notification\models\Notification;
use humhub\modules\rest\definitions\NotificationDefinitions;
use humhub\modules\rest\notifications\CustomTextNotification;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class NotificationCest extends HumHubApiTestCest
{
    protected $recordModelClass = Notification::class;
    protected $recordDefinitionFunction = [NotificationDefinitions::class, 'getNotification'];

    public function testList(ApiTester $I)
    {
        $I->wantTo('see all notifications');
        $I->amAdmin();

        $I->seePaginationGetResponse('notification', $this->getRecordDefinitions([1,3,4]), ['perPage' => 10]);
    }

    public function testUnseen(ApiTester $I)
    {
        $I->wantTo('see the unseen notifications');
        $I->amAdmin();

        $I->seePaginationGetResponse('notification/unseen', $this->getRecordDefinitions([1,3]), ['perPage' => 10]);
    }

    public function testView(ApiTester $I)
    {
        $I->wantTo('see a notification by id');
        $I->amAdmin();

        $I->sendGet('notification/1');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendGet('notification/2');
        $I->seeNotFoundMessage('Notification not found');

        $I->sendGet('notification/3');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(3));

        $I->sendGet('notification/123');
        $I->seeNotFoundMessage('Notification not found');
    }

    public function testMarkAsSeen(ApiTester $I)
    {
        $I->wantTo('mark a notification as seen');
        $I->amAdmin();

        $I->sendPatch('notification/mark-as-seen');
        $I->seeSuccessMessage('All notifications successfully marked as seen');
    }

    public function testSendCustom(ApiTester $I)
    {
        $I->wantTo('send a custom text notification to a user');
        $I->amAdmin();

        $I->sendPost('notification/send-custom', [
            'userId' => 2,
            'text' => 'API custom notification',
            'url' => 'https://example.com/custom',
        ]);
        $I->seeSuccessMessage('Notification successfully sent');
        $I->seeRecord(Notification::class, [
            'class' => CustomTextNotification::class,
            'user_id' => 2,
            'originator_user_id' => 1,
            'module' => 'rest',
        ]);
    }

    public function testSendCustomValidation(ApiTester $I)
    {
        $I->wantTo('validate custom notification payload');
        $I->amAdmin();

        $I->sendPost('notification/send-custom', [
            'userId' => 5,
            'text' => 'API custom notification',
            'url' => 'https://example.com/custom',
        ]);
        $I->seeValidationMessage('User Id is invalid.');

        $I->sendPost('notification/send-custom', [
            'userId' => 123,
            'text' => 'API custom notification',
            'url' => 'https://example.com/custom',
        ]);
        $I->seeValidationMessage('User Id is invalid.');

        $I->sendPost('notification/send-custom', [
            'userId' => 2,
            'text' => 'API custom notification',
            'url' => '',
        ]);
        $I->seeValidationMessage('Url cannot be blank.');
    }
}
