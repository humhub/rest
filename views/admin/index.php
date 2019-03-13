<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

/* @var $this \humhub\components\View */

use yii\helpers\Url;
use yii\helpers\Html;
use humhub\widgets\GridView;

?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('RestModule.base', '<strong>API</strong> Configuration'); ?></div>
    <div class="panel-body">
        <?php echo \humhub\modules\rest\widgets\ApiUserMenu::widget(); ?>
        <p />

        <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            [
                'attribute' => 'id',
                'options' => ['style' => 'width:40px;'],
                'format' => 'raw',
                'value' => function($data) {
                    return $data->id;
                },
            ],
            'client',
            'api_key',
            [
                'attribute' => 'active',
                'options' => ['style' => 'width:50px;'],
                'format' => 'raw',
                'value' => function($data) {
                    return $data->active == 0 ? 'No' : 'Yes';
                },
            ],

            [
                'header' => 'Actions',
                'class' => 'yii\grid\ActionColumn',
                'buttons' => [
                        'view' => function($url, $model) {
                            return Html::a('<i class="fa fa-eye"></i>',Url::toRoute(['view', 'id' => $model->id]), ['class' => 'btn btn-primary btn-xs tt']);
                        },
                        'update' => function($url, $model) {
                            return Html::a('<i class="fa fa-pencil"></i>', Url::toRoute(['update', 'id' => $model->id]), ['class' => 'btn btn-primary btn-xs tt']);
                        },
                        'delete' => function($url, $model) {
                            return Html::a('<i class="fa fa-times"></i>', Url::toRoute(['delete', 'id' => $model->id]), ['class' => 'btn btn-danger btn-xs tt']);
                        }
                ]
            ],
        ],
    ]); ?>
    </div>
</div>