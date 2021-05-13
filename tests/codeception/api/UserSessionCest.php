<?php

namespace rest\api;

use Codeception\Util\HttpCode;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

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

}
