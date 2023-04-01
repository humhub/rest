<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers;

use humhub\modules\rest\models\JwtAuthForm;
use Yii;
use yii\data\ActiveDataProvider;
use humhub\components\Response;
use humhub\modules\admin\components\Controller;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\rest\models\RestUserBearerToken;

class AdminController extends Controller
{
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_HTML;

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $model = new ConfigureForm();
        $model->loadSettings();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveSettings()) {
            $this->view->saved();
            return $this->refresh();
        }

        return $this->render('tabs', [
            'tab' => $this->renderPartial('index', [
                'model' => $model,
            ])
        ]);
    }

    public function actionJwtAuth()
    {
        $model = new JwtAuthForm();
        $model->loadSettings();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveSettings()) {
            $this->view->saved();
            return $this->refresh();
        }

        return $this->render('tabs', [
            'tab' => $this->renderPartial('jwt-auth', [
                'model' => $model,
            ])
        ]);
    }

    public function actionBearerAuth()
    {
        $bearerTokenModel = new RestUserBearerToken();

        if ($bearerTokenModel->load(Yii::$app->request->post()) && $bearerTokenModel->save()) {
            $this->view->saved();
            $bearerTokenModel->loadDefaultValues();
        }

        return $this->render('tabs', [
            'tab' => $this->renderPartial('bearer-auth', [
                'bearerTokenModel' => $bearerTokenModel,
                'bearerTokensProvider' => new ActiveDataProvider([
                    'query' => RestUserBearerToken::find()->with('user'),
                    'sort'=> [
                        'defaultOrder' => ['id' => SORT_ASC]
                    ],
                ])
            ])
        ]);
    }

    public function actionRevokeAccessToken($id)
    {
        RestUserBearerToken::deleteAll(['id' => $id]);

        $this->view->success(Yii::t('RestModule.base','Bearer Access Token Successfully Revoked'));

        return $this->redirect(['bearer-auth']);
    }
}
