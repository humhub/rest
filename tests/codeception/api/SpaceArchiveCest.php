<?php

namespace rest\api;

use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class SpaceArchiveCest extends HumHubApiTestCest
{
    public function testArchive(ApiTester $I)
    {
        $I->wantTo('archive a space');
        $I->amAdmin();

        $I->sendPatch('space/2/archive');
        $I->seeSuccessMessage('Space successfully archived!');
    }

    public function testUnarchive(ApiTester $I)
    {
        $I->wantTo('unarchive a space');
        $I->amAdmin();

        $I->sendPatch('space/2/unarchive');
        $I->seeBadMessage('Space is not archived!');

        $I->sendPatch('space/2/archive');
        $I->sendPatch('space/2/unarchive');
        $I->seeSuccessMessage('Space successfully unarchived!');
    }

}
