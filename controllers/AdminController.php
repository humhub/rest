<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use humhub\components\Response;
use humhub\modules\admin\components\Controller;
use humhub\modules\rest\models\ConfigureForm;
use humhub\modules\rest\models\RestUserBearerToken;

class AdminController extends Controller
{

    public function actionIndex()
    {

        Yii::$app->response->format = Response::FORMAT_HTML;

        $model = new ConfigureForm();
        $model->loadSettings();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveSettings()) {
            $this->view->saved();
            return $this->redirect(['index']);
        }

        $bearerTokenModel = new RestUserBearerToken();

        if ($bearerTokenModel->load(Yii::$app->request->post()) && $bearerTokenModel->save()) {
            $this->view->saved();
            $bearerTokenModel->loadDefaultValues();
        }

//        echo '<pre>';var_dump(Yii::$app->request->post());die;

        return $this->render('index', [
            'model' => $model,
            'bearerTokenModel' => $bearerTokenModel,
            'bearerTokensProvider' => new ActiveDataProvider([
                'query' => RestUserBearerToken::find()->with('user'),
                'sort'=> [
                    'defaultOrder' => ['id' => SORT_ASC]
                ],
            ])
        ]);
    }

    public function actionRevokeAccessToken($id)
    {
        RestUserBearerToken::deleteAll(['id' => $id]);

        $this->view->success(Yii::t('RestModule.base','Bearer Access Token Successfully Revoked'));

        return $this->redirect(['index']);
    }
}
