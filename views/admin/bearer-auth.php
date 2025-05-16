<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/**
 * @var \humhub\modules\ui\view\components\View $this
 * @var \humhub\modules\rest\models\RestUserBearerToken $bearerTokenModel
 * @var \yii\data\ActiveDataProvider $bearerTokensProvider
 */

use yii\bootstrap\ActiveForm;
use yii\grid\ActionColumn;
use yii\grid\SerialColumn;
use yii\helpers\Html;
use yii\web\JsExpression;
use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\GridView;
use humhub\widgets\Button;
use humhub\modules\ui\form\widgets\DatePicker;
use humhub\modules\ui\form\widgets\TimePicker;
use humhub\widgets\Link;

?>

<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base','Access Tokens') ?></div>
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
                    'label' => $bearerTokenModel->getAttributeLabel('userIds'),
                ],
                [
                    'attribute' => 'token',
                    'value' => function () {
                        return str_repeat('*', 30);
                    }
                ],
                'expiration:datetime',
                [
                    'class' => ActionColumn::class,
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

<?php if(!empty($bearerTokenModel->newToken)): ?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base','Bearer Token Created Successfully') ?></div>
    <div class="panel-body">
        <p class="form-heading">
            <?= Yii::t('RestModule.base', 'This token is displayed only once for security reasons. Please copy and securely store it now. You will not be able to view it again after leaving this page. If you lose it, you will need to generate a new token.') ?>
        </p>
        <div class="form-group">
            <?= Html::label(Yii::t('RestModule.base', 'Access Token for {user}', ['user' => $bearerTokenModel->user->displayName])); ?>
            <?= Html::textInput(null, $bearerTokenModel->newToken, ['disabled' => true, 'class' => 'form-control']) ?>
            <div class="text-right help-block">
                <div id="token" class="hidden"><?= $bearerTokenModel->newToken ?></div>
                <?= Link::withAction(Yii::t('RestModule.base', 'Copy to clipboard'), 'copyToClipboard', null, '#token')->icon('fa-clipboard')->style('color:#777') ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base','Add Access Token') ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($bearerTokenModel, 'userIds')->widget(UserPickerField::class, [
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
