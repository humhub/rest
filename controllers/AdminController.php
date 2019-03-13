<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\rest\models\ApiUser;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use humhub\compat\HForm;

class AdminController extends Controller
{

    /**
     * Lists all ApiUser models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ApiUser::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ApiUser model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
         $model = $this->findModel($id);

        // Add User Form
        $definition['elements']['ApiUser'] = array(
            'type' => 'form',
            'title' => 'Api User',
            'elements' => array(
                'id' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 20,
                    'readonly' => 'true',
                ),
                'client' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 255,
                    'readonly' => 'true',
                ),
                'api_key' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 25,
                    'readonly' => 'true',
                ),
                'active' => array(
                    'type' => 'dropdownlist',
                    'class' => 'form-control',
                    'items' => ['1' => 'Yes', '0' => 'No'],
                    'readonly' => 'true',
                ),
            ),
        );
        $definition['buttons'] = array();
        $form = new HForm($definition);
        $form->models['ApiUser'] = $model;

        return $this->render('view', array('hForm' => $form));
    }

    /**
     * Creates a new ApiUser model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ApiUser();
        $model->api_key = $this->generateRandomString(24);

        // Add User Form
        $definition['elements']['ApiUser'] = array(
            'type' => 'form',
            'title' => 'Api User',
            'elements' => array(
                'client' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ),
                'api_key' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 25,
                ),
                'active' => array(
                    'type' => 'dropdownlist',
                    'class' => 'form-control',
                    'items' => ['1' => 'Yes', '0' => 'No'],
                ),
            ),
        );
        $definition['buttons'] = array(
            'save' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary',
                'label' => 'Create account',
            ),
        );

        $form = new HForm($definition);
        $form->models['ApiUser'] = $model;

        if ($form->submitted('save') && $form->validate()) {

            $this->forcePostRequest();

            if ($form->models['ApiUser']->save()) {
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', array('hForm' => $form));
    }

    /**
     * Updates an existing ApiUser model.
     * If update is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
         $model = $this->findModel($id);

        // Add User Form
        $definition['elements']['ApiUser'] = array(
            'type' => 'form',
            'title' => 'Api User',
            'elements' => array(
                'id' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 20,
                    'readonly' => 'true',
                ),
                'client' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ),
                'api_key' => array(
                    'type' => 'text',
                    'class' => 'form-control',
                    'maxlength' => 25,
                ),
                'active' => array(
                    'type' => 'dropdownlist',
                    'class' => 'form-control',
                    'items' => ['1' => 'Yes', '0' => 'No'],
                ),
            ),
        );
        $definition['buttons'] = array(
            'save' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary',
                'label' => 'Update Account',
            ),
        );
        $form = new HForm($definition);
        $form->models['ApiUser'] = $model;

        if ($form->submitted('save') && $form->validate()) {

            $this->forcePostRequest();

            if ($form->models['ApiUser']->save()) {
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', array('hForm' => $form));
    }

    /**
     * Deletes an existing ApiUser model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the ApiUser model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ApiUser the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ApiUser::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Generates a randomized string of characters of the length provided.
     * @param integer $length
     * @return string
     */
    private function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}