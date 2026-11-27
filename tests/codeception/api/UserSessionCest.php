<?php

namespace rest\api;

use Codeception\Util\HttpCode;
use humhub\libs\BasePermission;
use humhub\modules\admin\permissions\ManageUsers;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;
use Yii;

class UserSessionCest extends HumHubApiTestCest
{
    public function testList(ApiTester $I)
    {
        $I->wantTo('delete all sessions by user id');
        $I->amAdmin();

        $I->sendDelete('user/session/all/1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->sendDelete('user/session/all/123');
        $I->seeNotFoundMessage('User not found!');
    }

    public function testDeleteAsUserManager(ApiTester $I)
    {
        $I->wantTo('see that a user manager cannot delete the sessions of a system admin');
        // User2 is a member of the Moderators group
        Yii::$app->user->permissionManager->setGroupState(3, ManageUsers::class, BasePermission::STATE_ALLOW);
        $I->amUser2();

        $I->sendDelete('user/session/all/1'); // Admin
        $I->seeForbiddenMessage('You are not allowed to delete the sessions of this user!');

        $I->sendDelete('user/session/all/4'); // User3
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
    }

}
