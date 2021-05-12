<?php

namespace rest\api;

use rest\SpaceApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class SpaceMembershipCest extends HumHubApiTestCest
{
    public function testList(SpaceApiTester $I)
    {
        $I->wantTo('list members of a space');
        $I->amAdmin();

        $I->seePaginationGetResponse('space/4/membership', $I->getSpaceMembershipDefinitions(4));
    }

    public function testAdd(SpaceApiTester $I)
    {
        $I->wantTo('add a member to a space');
        $I->amAdmin();

        $I->sendPost('space/1/membership/2');
        $I->seeSuccessMessage('Member added!');
    }

    public function testRole(SpaceApiTester $I)
    {
        $I->wantTo('change a member role in a space');
        $I->amAdmin();

        $I->sendPatch('space/3/membership/2/role?role=moderator');
        $I->seeSuccessMessage('Member updated!');
    }

    public function testRemove(SpaceApiTester $I)
    {
        $I->wantTo('remove a member from a space');
        $I->amAdmin();

        $I->sendDelete('space/3/membership/2');
        $I->seeSuccessMessage('Member deleted');
    }

}
