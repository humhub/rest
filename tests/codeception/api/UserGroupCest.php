<?php

namespace rest\api;

use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\user\models\Group;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class UserGroupCest extends HumHubApiTestCest
{
    protected $recordModelClass = Group::class;
    protected $recordDefinitionFunction = [UserDefinitions::class, 'getGroup'];

    public function testList(ApiTester $I)
    {
        $I->wantTo('see all groups list');
        $I->amAdmin();

        $I->seePaginationGetResponse('user/group', [
            $this->getRecordDefinition(1),
            $this->getRecordDefinition(2),
            $this->getRecordDefinition(3),
        ]);
    }

    public function testGetById(ApiTester $I)
    {
        $I->wantTo('see a group by id');
        $I->amAdmin();

        $I->sendGet('user/group/3');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(3));

        $I->sendGet('user/group/123');
        $I->seeNotFoundMessage('Group not found!');
    }

    public function testCreate(ApiTester $I)
    {
        $I->wantTo('create a group');
        $I->amAdmin();

        $I->sendPost('user/group', [
            'name' => 'New group',
            'description' => 'Description of the new created group.',
            'show_at_directory' => 0,
            'show_at_registration' => 0,
            'sort_order' => 1000,
        ]);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(4));
    }

    public function testUpdate(ApiTester $I)
    {
        $I->wantTo('update a group');
        $I->amAdmin();

        $I->sendPut('user/group/3', [
            'name' => 'Moderators - Updated',
            'sort_order' => 123,
        ]);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(3));
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a group');
        $I->amAdmin();

        $I->sendDelete('user/group/3');
        $I->seeSuccessMessage('Group successfully deleted!');

        $I->sendDelete('user/group/3');
        $I->seeNotFoundMessage('Group not found!');
    }

    public function testGetMembers(ApiTester $I)
    {
        $I->wantTo('see the group members');
        $I->amAdmin();

        $I->seePaginationGetResponse('user/group/3/member', $I->getUserDefinitions(['User2', 'UnapprovedUser'], 'short'));
    }

    public function testAddMembers(ApiTester $I)
    {
        $I->wantTo('add a member to a group');
        $I->amAdmin();

        $I->sendPut('user/group/3/member?userId=3');
        $I->seeBadMessage('User is already a member of the group!');

        $I->sendPut('user/group/3/member?userId=4');
        $I->seeSuccessMessage('Member added!');
    }

    public function testRemoveMembers(ApiTester $I)
    {
        $I->wantTo('remove a member from a group');
        $I->amAdmin();

        $I->sendDelete('user/group/3/member?userId=3');
        $I->seeSuccessMessage('Member removed!');

        $I->sendDelete('user/group/3/member?userId=3');
        $I->seeBadMessage('User is not a member of the group!');
    }

}
