<?php

namespace rest\api;

use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class UserCest extends HumHubApiTestCest
{
    public function testList(ApiTester $I)
    {
        $I->wantTo('see all users list');
        $I->amAdmin();

        $I->sendGet('user');
        $I->seePaginationResponseContainsJson('user', $I->getUserDefinitions(['Admin', 'User1', 'User2', 'User3', 'DisabledUser', 'UnapprovedUser', 'UnapprovedNoGroup', 'AdminNotMember']));
    }

    public function testGetByUsername(ApiTester $I)
    {
        $I->wantTo('see user by username');
        $I->amAdmin();

        $I->sendGet('user/get-by-username', ['username' => 'User2']);
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User2'));

        $I->sendGet('user/get-by-username', ['username' => 'Unknown']);
        $I->seeNotFoundMessage('User not found!');
    }

    public function testGetByEmail(ApiTester $I)
    {
        $I->wantTo('see user by email');
        $I->amAdmin();

        $I->sendGet('user/get-by-email', ['email' => 'disabled@example.com']);
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('DisabledUser'));

        $I->sendGet('user/get-by-email', ['email' => 'unknown@example.com']);
        $I->seeNotFoundMessage('User not found!');
    }

    public function testGetById(ApiTester $I)
    {
        $I->wantTo('see user by id');
        $I->amAdmin();

        $I->sendGet('user/3');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User2'));

        $I->sendGet('user/123');
        $I->seeNotFoundMessage('User not found!');
    }

    public function testCreate(ApiTester $I)
    {
        $I->wantTo('create user');
        $I->amAdmin();

        $I->sendPost('user', [
            'account' => [
                'username' => 'new_user',
                'email' => 'new_user@mail.local',
            ],
            'profile' => [
                'firstname' => 'Peter Updated',
                'lastname' => 'Tester Updated',
            ],
            'password' => [
                'newPassword' => 'SecretQ!',
            ],
        ]);
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('new_user'));
    }

    public function testCreateWithoutPassword(ApiTester $I)
    {
        $I->wantTo('create user without password');
        $I->amAdmin();

        $I->sendPost('user', [
            'account' => [
                'username' => 'new_user_without_password',
                'email' => 'new_user_without_password@mail.local',
            ],
            'profile' => [
                'firstname' => 'Test User',
                'lastname' => 'Without Password',
            ],
        ]);
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('new_user_without_password'));
    }

    public function testUpdate(ApiTester $I)
    {
        $I->wantTo('update user');
        $I->amAdmin();

        $I->sendPut('user/2', [
            'account' => [
                'username' => 'User1_updated',
            ],
            'profile' => [
                'firstname' => 'Peter Updated',
                'lastname' => 'Tester Updated',
            ],
        ]);
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));
    }

    public function testSoftDelete(ApiTester $I)
    {
        $I->wantTo('soft delete user');
        $I->amAdmin();

        $I->sendDelete('user/4');
        $I->seeSuccessMessage('User successfully soft deleted!');

        $I->sendDelete('user/123');
        $I->seeNotFoundMessage('User not found!');
    }

    public function testHardDelete(ApiTester $I)
    {
        $I->wantTo('hard delete user');
        $I->amAdmin();

        $I->sendDelete('user/full/4');
        $I->seeSuccessMessage('User successfully deleted!');

        $I->sendDelete('user/full/4');
        $I->seeNotFoundMessage('User not found!');
    }

}
