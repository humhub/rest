<?php

namespace rest\api;

use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\space\models\Membership;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class SpaceMembershipCest extends HumHubApiTestCest
{
    public function testList(ApiTester $I)
    {
        $I->wantTo('list members of a space');
        $I->amAdmin();

        $I->seePaginationGetResponse('space/4/membership', $this->getSpaceMembershipDefinitions(4));
    }

    public function testAdd(ApiTester $I)
    {
        $I->wantTo('add a member to a space');
        $I->amAdmin();

        $I->sendPost('space/1/membership/2');
        $I->seeSuccessMessage('Member added!');
    }

    public function testRole(ApiTester $I)
    {
        $I->wantTo('change a member role in a space');
        $I->amAdmin();

        $I->sendPatch('space/3/membership/2/role?role=moderator');
        $I->seeSuccessMessage('Member updated!');
    }

    public function testRemove(ApiTester $I)
    {
        $I->wantTo('remove a member from a space');
        $I->amAdmin();

        $I->sendDelete('space/3/membership/2');
        $I->seeSuccessMessage('Member deleted');
    }

    private function getSpaceMembershipDefinitions(int $spaceId): array
    {
        $memberships = Membership::findAll(['space_id' => $spaceId]);
        $membershipDefinitions = [];
        foreach ($memberships as $membership) {
            $membershipDefinitions[] = SpaceDefinitions::getSpaceMembership($membership);
        }
        return $membershipDefinitions;
    }

}
