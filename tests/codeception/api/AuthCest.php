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
        $I->sendPost('auth/login', ['username' => 'admin', 'password' => 'admin&humhub@PASS%worD!']);
        $I->seeSuccessMessage('Success');
    }

    public function testLoginUser(ApiTester $I)
    {
        $I->wantTo('login User3');
        $I->sendPost('auth/login', ['username' => 'User3', 'password' => 'user^humhub@PASS%worD!']);
        $I->seeSuccessMessage('Success');
    }

    public function testLoginByEmail(ApiTester $I)
    {
        $I->wantTo('login by email');
        $I->sendPost('auth/login', ['username' => 'user1@example.com', 'password' => 'user^humhub@PASS%worD!']);
        $I->seeSuccessMessage('Success');
    }

    public function testLoginByJwtBearerToken(ApiTester $I)
    {
        $I->wantTo('login by Basic auth and JWT bearer token');

        $I->sendPost('auth/login', ['username' => 'User3', 'password' => 'user^humhub@PASS%worD!']);
        $I->seeSuccessMessage('Success');
        list($auth_token) = $I->grabDataFromResponseByJsonPath('auth_token');

        $I->sendGet('auth/current');
        $I->seeCodeResponseContainsJson(HttpCode::UNAUTHORIZED, ['message' => 'Your request was made with invalid credentials.']);

        $I->amBearerAuthenticated($auth_token);
        $I->sendGet('auth/current');
        $I->seeUserDefinition('User3');
    }

    public function testLoginByBearerAccessToken(ApiTester $I)
    {
        $accessToken = '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB';

        $I->wantTo('login by Bearer Access Token');

        $I->amBearerAuthenticated($accessToken);
        $I->sendGet('auth/current');
        $I->seeUserDefinition('User1');
    }

    public function testLoginByQueryParamBearerAccessToken(ApiTester $I)
    {
        $accessToken = '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB';

        $I->wantTo('login by Bearer Access Token');

        $I->sendGet("auth/current?access-token=$accessToken");
        $I->seeUserDefinition('User1');
    }

    public function testCurrent(ApiTester $I)
    {
        $I->wantTo('see current logged in Admin');
        $I->amAdmin();
        $I->sendGet('auth/current');
        $I->seeUserDefinition('Admin');
    }

    public function testImpersonateByAdmin(ApiTester $I)
    {
        $I->wantTo('Check Impersonate by Admin');
        $I->amAdmin();
        $I->sendPost('auth/impersonate', ['userId' => 2]);
        $I->canSeeResponseCodeIs(200);
        [$auth_token] = $I->grabDataFromResponseByJsonPath('token');
        $I->amUser(null);
        $I->haveHttpHeader('Authorization', "Impersonate $auth_token");
        $I->sendGet('auth/current');
        $I->seeUserDefinition('User1');
    }

    public function testImpersonateByUser(ApiTester $I)
    {
        $I->wantTo('Check Impersonate by User');
        $I->amUser1();
        $I->sendPost('auth/impersonate', ['userId' => 2]);
        $I->canSeeResponseCodeIs(403);
    }

}
