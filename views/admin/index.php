<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $this \humhub\modules\ui\view\components\View */
/* @var $model \humhub\modules\rest\models\ConfigureForm */
/* @var $bearerTokenModel \humhub\modules\rest\models\RestUserBearerToken */
/* @var $bearerTokensProvider \yii\data\ActiveDataProvider */

use yii\bootstrap\ActiveForm;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\web\JsExpression;
use kartik\widgets\DateTimePicker;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\GridView;
use humhub\widgets\Button;
use humhub\modules\ui\form\widgets\DatePicker;
use humhub\modules\ui\form\widgets\TimePicker;

$apiModuleOptions = $model->getApiModuleOptions();
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base', '<strong>API</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'enableClientValidation' => false, 'enableClientScript' => false]); ?>

        <?= $form->field($model, 'enabledForAllUsers')->checkbox(); ?>

        <?= $form->field($model, 'enabledUsers')->widget(UserPickerField::class); ?>
        <br />
        <?= $form->field($model, 'jwtKey'); ?>
        <?= $form->field($model, 'jwtExpire'); ?>

        <br />

        <?= $form->field($model, 'enableBasicAuth')->checkbox(); ?>
        <?= $form->field($model, 'enableBearerAuth')->checkbox(); ?>
        <?= $form->field($model, 'enableQueryParamAuth')->checkbox(); ?>

        <br/>

        <?= $form->field($model, 'apiModules')->checkboxList($apiModuleOptions)
            ->hint(empty($apiModuleOptions) ? Yii::t('RestModule.base', 'No enabled modules found with additional REST API endpoints.') : false); ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('base', 'Save'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
        </div>

        <?php ActiveForm::end(); ?>

        <?php if ($model->enableBearerAuth) : ?>
            <div class="panel panel-default">
                <div class="panel-heading"><?= Yii::t('RestModule.base','Bearer Access Tokens') ?></div>
                <div class="panel-body">
                    <?= GridView::widget([
                        'dataProvider' => $bearerTokensProvider,
                        'columns' => [
                            [
                                'class' => SerialColumn::class
                            ],
                            [
                                'attribute' => 'user.email',
                                'format' => 'email',
                                'label' => $bearerTokenModel->getAttributeLabel('userGuid'),
                            ],
                            'token',
                            'expiration:datetime',
                            [
                                'class' => \yii\grid\ActionColumn::class,
                                'visibleButtons' => [
                                    'view' => false,
                                    'update' => false,
                                ],
                                'buttons' => [
                                    'delete' => function ($url, $model, $id) {
                                        return Button::primary()
                                                ->link(['revoke-access-token', 'id' => $id])
                                                ->icon('trash')
                                                ->xs();
                                    },
                                ],
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading"><?= Yii::t('RestModule.base','Add Bearer Access Token') ?></div>
                <div class="panel-body">
                    <?php $form = ActiveForm::begin(); ?>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($bearerTokenModel, 'userGuid')->widget(UserPickerField::class, [
                                'maxSelection' => 1,
                                'itemKey' => 'id',
                            ]); ?>

                            <div class="row">
                                <div class="col-sm-6 col-xs-6">
                                    <?= $form
                                        ->field($bearerTokenModel, 'expiration')
                                        ->widget(DatePicker::class, [
                                            'clientOptions' => [
                                                'minDate' => new JsExpression('new Date()')
                                            ],
                                        ]) ?>
                                </div>
                                <div class="col-sm-6 col-xs-6">
                                    <?= $form
                                        ->field($bearerTokenModel, 'expirationTime')
                                        ->widget(TimePicker::class)
                                        ->label("&nbsp") ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <?= Html::submitButton(Yii::t('base', 'Add'), ['class' => 'btn btn-primary', 'data-ui-loader' => '']) ?>
                            </div>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#configureform-enabledforallusers").click(function() {
            if ($(this).prop("checked")) {
                $(".field-configureform-enabledusers").hide();
            } else {
                $(".field-configureform-enabledusers").show();
            }
        });
        if ($(this).prop("checked")) {
            $(".field-configureform-enabledusers").hide();
        } else {
            $(".field-configureform-enabledusers").show();
        }
    });
</script>
