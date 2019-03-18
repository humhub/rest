<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\calendar;

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use humhub\modules\rest\definitions\CalendarDefinitions;
use Yii;


class CalendarController extends BaseContentController
{
    public function actionIndex()
    {
        return $this->returnError(404, 'Calendar module does not installed. Please install or enable Calendar module to use this API');
    }

    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return CalendarEntry::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var CalendarEntry $contentRecord */
        return CalendarDefinitions::getCalendarEntry($contentRecord);
    }

    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }
        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        if (! $container->permissionManager->can(CreateEntry::class)) {
            return $this->returnError(403, 'You are not allowed to create calendar entry!');
        }
        $calendarEntryForm = new CalendarEntryForm();
        $calendarEntryForm->createNew($container);

        $calendarEntryForm->load(Yii::$app->request->getBodyParams(), '');

        if ($calendarEntryForm->save()) {
            return CalendarDefinitions::getCalendarEntry($calendarEntryForm->entry);
        }

        if ($calendarEntryForm->hasErrors() || $calendarEntryForm->entry->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'entryForm' => $calendarEntryForm->getErrors(),
                'calendarEntry' => $calendarEntryForm->entry->getErrors(),
            ]);
        } else {
            Yii::error('Could not create validated calendar entry.', 'api');
            return $this->returnError(500, 'Internal error while save calendar entry!');
        }
    }

    public function actionUpdate($id)
    {
        $calendarEntry = CalendarEntry::findOne(['id' => $id]);
        if (! $calendarEntry) {
            return $this->returnError(404, 'Calendar entry not found!');
        }

        $calendarEntryForm = new CalendarEntryForm(['entry' => $calendarEntry]);
        if(! $calendarEntryForm->entry->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to update this calendar entry!');
        }

        $this->prepareFormDate($calendarEntryForm, Yii::$app->request->post('CalendarEntryForm', []));

        $calendarEntryForm->load(Yii::$app->request->getBodyParams(), '');

        if ($calendarEntryForm->save()) {
            return CalendarDefinitions::getCalendarEntry($calendarEntryForm->entry);
        }

        if ($calendarEntryForm->hasErrors() || $calendarEntryForm->entry->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                'entryForm' => $calendarEntryForm->getErrors(),
                'calendarEntry' => $calendarEntryForm->entry->getErrors(),
            ]);
        } else {
            Yii::error('Could not create validated calendar entry.', 'api');
            return $this->returnError(500, 'Internal error while save calendar entry!');
        }
    }

    private function prepareFormDate($calendarEntryForm, $entryForm)
    {
        if (empty($entryForm['start_date'])) {
            $calendarEntryForm->start_date = $calendarEntryForm->entry->start_datetime;
        }
        if (empty($entryForm['end_date'])) {
            $calendarEntryForm->end_date = $calendarEntryForm->entry->end_datetime;
        }
    }
}