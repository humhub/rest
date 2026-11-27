<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\unit;

use humhub\modules\rest\components\auth\ImpersonateAuth;
use humhub\modules\rest\components\auth\TokenImpersonation;
use humhub\modules\rest\components\User as UserComponent;
use humhub\modules\rest\models\ImpersonateAuthToken;
use humhub\modules\user\models\User;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\web\Request;
use yii\web\Response;

class ImpersonateAuthTest extends HumHubDbTestCase
{
    public $fixtureConfig = ['default'];

    public function testImpersonateTokenRequestIsImpersonated()
    {
        $user = $this->authenticateByImpersonateToken([]);

        $this->assertSame(2, (int)$user->id);
        $this->assertInstanceOf(TokenImpersonation::class, $user->getImpersonation());
        $this->assertTrue($user->getImpersonation()->isActive());
        $this->assertTrue($user->getImpersonation()->canAccessPrivateContent());
        $this->assertNull($user->getImpersonation()->getImpersonator());
    }

    public function testImpersonateTokenRequestKeepsTheImpersonationConfig()
    {
        $user = $this->authenticateByImpersonateToken(['allowPrivateContentAccess' => false, 'log' => false]);

        $this->assertTrue($user->getImpersonation()->isActive());
        $this->assertFalse($user->getImpersonation()->canAccessPrivateContent());
        $this->assertFalse($user->getImpersonation()->log);
    }

    private function authenticateByImpersonateToken(array $impersonationConfig): UserComponent
    {
        $token = new ImpersonateAuthToken(['user_id' => 2]);
        $this->assertTrue($token->save());
        $token->refresh();

        /** @var UserComponent $user */
        $user = Yii::createObject([
            'class' => UserComponent::class,
            'identityClass' => User::class,
            'enableSession' => false,
            'impersonation' => $impersonationConfig,
        ]);

        $request = new Request();
        $request->getHeaders()->set('Authorization', 'Impersonate ' . $token->token);

        $this->assertNotNull((new ImpersonateAuth())->authenticate($user, $request, new Response()));

        return $user;
    }
}
