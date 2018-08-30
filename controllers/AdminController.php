<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers;

use humhub\components\Response;
use humhub\modules\admin\components\Controller;
use humhub\modules\rest\models\ConfigureForm;
use Yii;

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

        return $this->render('index', ['model' => $model]);
    }

}