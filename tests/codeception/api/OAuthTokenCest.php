<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2026 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace rest\api;

use humhub\modules\oauth\Module as OAuthModule;
use humhub\modules\oauth\services\PersonalAccessTokenService;
use humhub\modules\oauth\tests\codeception\_support\OAuthTestHelper;
use humhub\modules\rest\Module;
use humhub\modules\user\models\User;
use rest\ApiTester;
use rest\ScopedAuthController;
use tests\codeception\_support\HumHubApiTestCest;
use Yii;

/**
 * Tokens of core's OAuth server (OAuth access tokens, personal access tokens) on `/api/v1`.
 */
class OAuthTokenCest extends HumHubApiTestCest
{
    public function _before()
    {
        parent::_before();

        OAuthModule::instance()->getKeyStore()->generateIfMissing();
        OAuthModule::instance()->settings->set('allowPersonalAccessTokens', true);

        // A copy of `GET auth/current` that declares `users:read`.
        Yii::$app->getModule('rest')->controllerMap['scoped-auth'] = ScopedAuthController::class;
        Yii::$app->urlManager->addRules([
            ['pattern' => Module::API_URL_PREFIX . 'scoped-auth/current', 'route' => 'rest/scoped-auth/current', 'verb' => ['GET']],
        ], false);
    }

    public function _after()
    {
        OAuthModule::instance()->settings->delete('allowPersonalAccessTokens');
        OAuthModule::instance()->settings->delete('patScopes');
    }

    private function pat(array $scopes): string
    {
        [$token] = (new PersonalAccessTokenService())->create(User::findOne(['username' => 'User1']), 'x', $scopes, 30);

        return $token;
    }

    private function accessToken(): string
    {
        return OAuthTestHelper::issueTokens(User::findOne(['username' => 'User1'])->id)['access_token'];
    }

    public function testEndpointWithoutScopesRefusesTokens(ApiTester $I)
    {
        OAuthModule::instance()->settings->set('patScopes', json_encode(['full', 'users:read']));

        foreach ([$this->accessToken(), $this->pat(['full']), $this->pat(['users:read'])] as $token) {
            $I->amBearerAuthenticated($token);
            $I->sendGet('auth/current');
            $I->seeResponseCodeIs(401);
        }
    }

    public function testEndpointWithScopeAcceptsItsScopeAndFull(ApiTester $I)
    {
        OAuthModule::instance()->settings->set('patScopes', json_encode(['full', 'users:read', 'content:read']));

        $I->amBearerAuthenticated($this->pat(['users:read']));
        $I->sendGet('scoped-auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));

        $I->amBearerAuthenticated($this->pat(['full']));
        $I->sendGet('scoped-auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));

        $I->amBearerAuthenticated($this->accessToken());
        $I->sendGet('scoped-auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));

        $I->amBearerAuthenticated($this->pat(['content:read']));
        $I->sendGet('scoped-auth/current');
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['error' => 'insufficient_scope', 'scope' => 'users:read']);
    }

    public function testTokenOfUserNotEnabledForTheApiIsRefused(ApiTester $I)
    {
        Yii::$app->getModule('rest')->settings->set('enabledForAllUsers', false);
        $token = $this->pat(['users:read']);

        $I->amBearerAuthenticated($token);
        $I->sendGet('scoped-auth/current');
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['code' => 403]);

        Yii::$app->getModule('rest')->settings->setSerialized('enabledUsers', [User::findOne(['username' => 'User1'])->guid]);
        $I->sendGet('scoped-auth/current');
        $I->seeSuccessResponseContainsJson($I->getUserDefinition('User1'));
    }
}
