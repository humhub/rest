<?php

namespace rest\api;

use rest\SpaceApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class SpaceArchiveCest extends HumHubApiTestCest
{
    public function testArchive(SpaceApiTester $I)
    {
        $I->wantTo('archive a space');
        $I->amAdmin();

        $I->sendPatch('space/2/archive');
        $I->seeSuccessMessage('Space successfully archived!');
    }

    public function testUnarchive(SpaceApiTester $I)
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
