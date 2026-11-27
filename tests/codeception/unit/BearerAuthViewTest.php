<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\unit;

use humhub\modules\rest\controllers\AdminController;
use humhub\modules\rest\models\RestUserBearerToken;
use humhub\modules\user\Module as UserModule;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;
use yii\data\ActiveDataProvider;

class BearerAuthViewTest extends HumHubDbTestCase
{
    public $fixtureConfig = ['default'];

    public function testCreatedTokenPanelEncodesTheOwnerDisplayName()
    {
        $this->becomeUser('Admin');
        /** @var UserModule $userModule */
        $userModule = Yii::$app->getModule('user');
        $userModule->displayNameCallback = static fn() => 'Tom <b class="xss">Bold</b>';
        Yii::$app->runtimeCache->flush();

        $token = new RestUserBearerToken(['user_id' => 2]);
        $token->newToken = 'shown-once';

        // The view builds URLs relative to the current request and controller
        Yii::$app->request->setUrl('/rest/admin/bearer-auth');
        $controller = new AdminController('admin', Yii::$app->getModule('rest'));
        Yii::$app->controller = $controller;
        $html = $controller->renderPartial('bearer-auth', [
            'bearerTokenModel' => $token,
            'bearerTokensProvider' => new ActiveDataProvider(['query' => RestUserBearerToken::find()->with('user')]),
        ]);

        $this->assertStringContainsString('Access Token for Tom &lt;b class=&quot;xss&quot;&gt;Bold&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('Access Token for Tom <b', $html);
    }
}
