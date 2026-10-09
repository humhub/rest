<?php

namespace rest\api;

use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\stream\actions\Stream;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class SpaceCest extends HumHubApiTestCest
{
    protected $recordModelClass = Space::class;
    protected $recordDefinitionFunction = [SpaceDefinitions::class, 'getSpace'];

    public function testList(ApiTester $I)
    {
        $I->wantTo('see all spaces list');
        $I->amAdmin();

        $I->seePaginationGetResponse('space', $this->getRecordDefinitions([1,2,3,4,5]));
    }

    public function testGetById(ApiTester $I)
    {
        $I->wantTo('see all spaces list');
        $I->amAdmin();

        $I->sendGet('space/1');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendGet('space/2');
        $I->seeForbiddenMessage('You don\'t have an access to this space!');

        $I->sendGet('space/123');
        $I->seeNotFoundMessage('Space not found!');
    }

    public function testCreateWithoutPermission(ApiTester $I)
    {
        $I->wantTo('create a space by user without permission');
        $I->amUser3();

        $I->sendPost('space');
        $I->seeForbiddenMessage('You are not allowed to create spaces!');
    }

    public function testCreateWithPermission(ApiTester $I)
    {
        $I->wantTo('create a space by user with permission');
        $I->amAdmin();

        $I->sendPost('space', [
            'name' => 'New Space Name',
            'description' => 'New Space Description',
            'visibility' => 1,
            'join_policy' => 1,
        ]);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(6));
    }

    public function testUpdate(ApiTester $I)
    {
        $I->wantTo('update a space');
        $I->amAdmin();

        $I->sendPut('space/2', [
            'name' => 'Updated Space 2',
            'description' => 'Updated Space 2 description',
            'tags' => 'first, second, third',
            'color' => '#EE3300',
            'defaultStreamSort' => Stream::SORT_CREATED_AT,
        ]);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(2));
    }

    public function testUpdatePartial(ApiTester $I)
    {
        $I->wantTo('update only the name of a space');
        $I->amAdmin();

        $I->sendPut('space/2', ['name' => 'Partially updated Space 2']);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(2));

        $I->sendPut('space/2', ['name' => 'Space 2', 'defaultStreamSort' => 'invalid']);
        $I->seeCodeResponseContainsJson(422, ['message' => 'Validation failed']);
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a space');
        $I->amAdmin();

        $I->sendDelete('space/2');
        $I->seeSuccessMessage('Space successfully deleted!');

        $I->sendDelete('space/2');
        $I->seeNotFoundMessage('Space not found!');
    }

    public function testChangeOwnerAsOwner(ApiTester $I)
    {
        $I->wantTo('change the owner of my space');
        $I->amUser1();

        // User2 is not a member of Space 2 yet
        $I->sendPut('space/2/owner', ['userId' => 3]);
        $I->seeSuccessResponseContainsJson(['id' => 2, 'owner' => ['id' => 3]]);

        $I->seeRecord(Space::class, ['id' => 2, 'created_by' => 3]);
        $I->seeRecord(Membership::class, ['space_id' => 2, 'user_id' => 3, 'group_id' => Space::USERGROUP_ADMIN]);
    }

    public function testChangeOwnerAsSystemAdmin(ApiTester $I)
    {
        $I->wantTo('change the owner of a space as system admin');
        $I->amAdmin();

        $I->sendPut('space/2/owner', ['userId' => 4]);
        $I->seeSuccessResponseContainsJson(['id' => 2, 'owner' => ['id' => 4]]);
        $I->seeRecord(Space::class, ['id' => 2, 'created_by' => 4]);
    }

    public function testChangeOwnerAsSpaceAdmin(ApiTester $I)
    {
        $I->wantTo('see that a space admin cannot change the space owner');
        $I->amUser1(); // Space admin of Space 4, owned by Admin

        $I->sendPut('space/4/owner', ['userId' => 2]);
        $I->seeForbiddenMessage('You are not allowed to change the owner of this space!');
        $I->seeRecord(Space::class, ['id' => 4, 'created_by' => 1]);
    }

    public function testChangeOwnerAsMember(ApiTester $I)
    {
        $I->wantTo('see that a space member cannot change the space owner');
        $I->amUser2(); // Member of Space 4

        $I->sendPut('space/4/owner', ['userId' => 3]);
        $I->seeForbiddenMessage('You are not allowed to change the owner of this space!');
        $I->seeRecord(Space::class, ['id' => 4, 'created_by' => 1]);
    }

    public function testChangeOwnerErrors(ApiTester $I)
    {
        $I->wantTo('see errors when changing the space owner with wrong data');
        $I->amAdmin();

        $I->sendPut('space/999/owner', ['userId' => 2]);
        $I->seeNotFoundMessage('Space not found!');

        $I->sendPut('space/2/owner', ['userId' => 99999]);
        $I->seeNotFoundMessage('User not found!');

        $I->sendPut('space/2/owner', []);
        $I->seeBadMessage('User id is required!');

        $I->sendPut('space/2/owner', ['userId' => 5]); // DisabledUser
        $I->seeBadMessage('User is not active!');
    }

}
