<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\cfiles;

use humhub\modules\cfiles\models\FileSystemItem;
use humhub\modules\cfiles\models\Folder;
use humhub\modules\cfiles\models\forms\MoveForm;
use humhub\modules\cfiles\models\forms\SelectionForm;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseController;
use Yii;

class ManageController extends BaseController
{
    public function actionDelete($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $form = new SelectionForm();
        $result = $this->prepareItems($form, $container);
        
        if ($form->hasErrors()) {
            return $this->returnError(400, 'Bad request', [
                'errors' => $form->getErrors()
            ]);
        }

        foreach ($result as $item) {
            if(! $item->delete()) {
                Yii::error('Could not delete cFiles items.', 'api');
                return $this->returnError(500, 'Internal error while deleting cFiles item!');
            }
        }

        return $this->returnSuccess('Selected items are successfully deleted!');
    }

    public function actionMove($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $params = Yii::$app->request->getBodyParams();

        $root = Folder::find()->contentContainer($container)->andWhere(['type' => 'root'])->one();
        if (empty($params['source_id'])) {
            return $this->returnError(400, 'Source folder id is required!');
        }

        $source = Folder::findOne(['id' => $params['source_id']]);
        if ($source === null) {
            return $this->returnError(404, 'cFiles folder not found!');
        }

        $model = new MoveForm([
            'root' => $root,
            'sourceFolder' => $source
        ]);

        $this->prepareItems($model, $container);

        if ($model->hasErrors()) {
            return $this->returnError(400, 'Bad request', [
                'errors' => $model->getErrors()
            ]);
        }

        if($model->load($params) && $model->save()) {
            return $this->returnSuccess('Items successfully moved.');
        }

        if ($model->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'errors' => $model->getErrors()
            ]);
        } else {
            Yii::error('Could not move cFiles items.', 'api');
            return $this->returnError(500, 'Internal error while move cFiles items!');
        }
    }

    public function actionMakePublic($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $form = new SelectionForm();
        $result = $this->prepareItems($form, $container);

        if ($form->hasErrors()) {
            return $this->returnError(400, 'Bad request', [
                'errors' => $form->getErrors()
            ]);
        }

        foreach ($result as $item) {
            $item->updateVisibility(Content::VISIBILITY_PUBLIC);
            if(! $item->content->save()) {
                Yii::error('Could not set public visibility for cFiles items.', 'api');
                return $this->returnError(500, 'Internal error while setting public visibility for cFiles item!');
            }
        }

        return $this->returnSuccess('Items successfully marked public!');
    }

    public function actionMakePrivate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        $form = new SelectionForm();
        $result = $this->prepareItems($form, $container);

        if ($form->hasErrors()) {
            return $this->returnError(400, 'Bad request', [
                'errors' => $form->getErrors()
            ]);
        }

        foreach ($result as $item) {
            $item->updateVisibility(Content::VISIBILITY_PRIVATE);
            if(! $item->content->save()) {
                Yii::error('Could not set private visibility for cFiles items.', 'api');
                return $this->returnError(500, 'Internal error while setting private visibility for cFiles item!');
            }
        }

        return $this->returnSuccess('Items successfully marked private!');
    }

    private function prepareItems(SelectionForm $form, $container)
    {
        $result = [];

        if (empty($form->selection) || ! is_array($form->selection)) {
            $form->addError('selection', 'No items selected.');
            return $result;
        }
        foreach ($form->selection as $itemId) {
            $item = FileSystemItem::getItemById($itemId);
            if (! $item) {
                $form->addError($itemId, 'File system item is not found.');
                return $result;
            }
            if (! $item->content->canEdit() || $item->content->container->id !== $container->id) {
                $form->addError($itemId, 'You are not allowed to manage this item.');
                return $result;
            }
            if ($this->action->id == 'delete' && ! $item->isDeletable()) {
                $form->addError($itemId, 'You are not allowed to delete this item.');
            } elseif ($this->action->id == 'make-public') {
                if ($item->parentFolder && $item->parentFolder->content->visibility === Content::VISIBILITY_PRIVATE) {
                    $form->addError($itemId, 'You can not make item public inside a private directory.');
                } else $result[] = $item;
            } else {
                $result[] = $item;
            }
        }
        
        return $result;
    }
}