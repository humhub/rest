<?php

namespace rest\api;

use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class FileCest extends HumHubApiTestCest
{
    public function testDownload(ApiTester $I)
    {
        $I->wantTo('download a file');
        $I->amAdmin();

        $I->sendGet('file/download/123');
        $I->seeNotFoundMessage('File not found!');

        $I->sendGet('file/download/1');
        $I->seeNotFoundMessage('File doesn\'t not exist!');
    }

}
