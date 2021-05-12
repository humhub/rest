<?php

namespace rest\api;

use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class UserSessionCest extends HumHubApiTestCest
{
    public function testList(ApiTester $I)
    {
        $I->wantTo('delete all sessions by user id');
        $I->amAdmin();

        $I->sendDelete('user/session/all/2');
        $I->seeSuccessMessage('0 user sessions deleted!');

        $I->sendDelete('user/session/all/123');
        $I->seeNotFoundMessage('User not found!');
    }

}
