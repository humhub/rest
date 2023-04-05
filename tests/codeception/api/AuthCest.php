<?php

namespace rest\api;

use Codeception\Util\HttpCode;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class AuthCest extends HumHubApiTestCest
{
    public function testLoginWrong(ApiTester $I)
    {
        $I->wantTo('login with wrong username/password');

        $I->sendPost('auth/login');
        $I->seeBadMessage('Wrong username or password');

        $I->sendPost('auth/login', ['username' => 'wrong_login']);
        $I->seeBadMessage('Wrong username or password');

        $I->sendPost('auth/login', ['username' => 'admin', 'password' => 'wrong_password']);
        $I->seeBadMessage('Wrong username or password');
    }

    public function testLoginAdmin(ApiTester $I)
    {
        $I->wantTo('login Admin');
        $I->sendPost('auth/login', ['username' => 'admin', 'password' => 'test']);
        $I->seeSuccessMessage('Success');
    }

    public function testLoginUser(ApiTester $I)
    {
        $I->wantTo('login User3');
        $I->sendPost('auth/login', ['username' => 'User3', 'password' => '123qwe']);
        $I->seeSuccessMessage('Success');
    }

    public function testLoginByEmail(ApiTester $I)
    {
        $I->wantTo('login by email');
        $I->sendPost('auth/login', ['username' => 'user1@example.com', 'password' => '123qwe']);
        $I->seeSuccessMessage('Success');
    }

    public function testLoginByJwtBearerToken(ApiTester $I)
    {
        $I->wantTo('login by bearer token');

        $I->sendPost('auth/login', ['username' => 'User3', 'password' => '123qwe']);
        $I->seeSuccessMessage('Success');
        list($auth_token) = $I->grabDataFromResponseByJsonPath('auth_token');

        $I->sendGet('auth/current');
        $I->seeCodeResponseContainsJson(HttpCode::UNAUTHORIZED, ['message' => 'Your request was made with invalid credentials.']);

        $I->amBearerAuthenticated($auth_token);
        $I->sendGet('auth/current');
        $I->seeUserDefinition('User3');
    }

    public function testCurrent(ApiTester $I)
    {
        $I->wantTo('see current logged in Admin');
        $I->amAdmin();
        $I->sendGet('auth/current');
        $I->seeUserDefinition('Admin');
    }

}
