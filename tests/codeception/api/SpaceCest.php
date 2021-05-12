<?php

namespace rest\api;

use rest\SpaceApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class SpaceCest extends HumHubApiTestCest
{
    public function testList(SpaceApiTester $I)
    {
        $I->wantTo('see all spaces list');
        $I->amAdmin();

        $I->seePaginationGetResponse('space', $I->getSpaceDefinitions([1,2,3,4,5]));
    }

    public function testGetById(SpaceApiTester $I)
    {
        $I->wantTo('see all spaces list');
        $I->amAdmin();

        $I->sendGet('space/1');
        $I->seeSuccessResponseContainsJson($I->getSpaceDefinition(1));

        $I->sendGet('space/2');
        $I->seeForbiddenMessage('You don\'t have an access to this space!');

        $I->sendGet('space/123');
        $I->seeNotFoundMessage('Space not found!');
    }

    public function testCreateWithoutPermission(SpaceApiTester $I)
    {
        $I->wantTo('create a space by user without permission');
        $I->amUser3();

        $I->sendPost('space');
        $I->seeForbiddenMessage('You are not allowed to create spaces!');
    }

    public function testCreateWithPermission(SpaceApiTester $I)
    {
        $I->wantTo('create a space by user with permission');
        $I->amAdmin();

        $I->sendPost('space', [
            'name' => 'New Space Name',
            'description' => 'New Space Description',
            'visibility' => 1,
            'join_policy' => 1,
        ]);
        $I->seeSuccessResponseContainsJson($I->getSpaceDefinition(6));
    }

    public function testUpdate(SpaceApiTester $I)
    {
        $I->wantTo('update a space');
        $I->amAdmin();

        $I->sendPut('space/2', [
            'name' => 'Updated Space 2',
            'description' => 'Updated Space 2 description',
            'tags' => 'first, second, third',
            'color' => '#EE3300',
        ]);
        $I->seeSuccessResponseContainsJson($I->getSpaceDefinition(2));
    }

    public function testDelete(SpaceApiTester $I)
    {
        $I->wantTo('delete a space');
        $I->amAdmin();

        $I->sendDelete('space/2');
        $I->seeSuccessMessage('Space successfully deleted!');

        $I->sendDelete('space/2');
        $I->seeNotFoundMessage('Space not found!');
    }

}
