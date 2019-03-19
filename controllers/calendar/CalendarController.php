<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\calendar;

use humhub\libs\DbDateValidator;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\forms\CalendarEntryForm;
use humhub\modules\calendar\permissions\CreateEntry;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseContentController;
use humhub\modules\rest\definitions\CalendarDefinitions;
use Yii;
use yii\web\HttpException;


class CalendarController extends BaseContentController
{
    public static $moduleId = 'Calendar';

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

        $requestParams = $this->prepareRequestParams(Yii::$app->request->getBodyParams());

        $calendarEntryForm = new CalendarEntryForm();
        $calendarEntryForm->createNew($container);
        $calendarEntryForm->load($requestParams, '');

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

    public function actionRespond($id)
    {
        $calendarEntry = CalendarEntry::findOne(['id' => $id]);
        if (! $calendarEntry) {
            return $this->returnError(404, 'Calendar entry not found!');
        }

        $respondType = Yii::$app->request->post('type', null);

        if (is_null($respondType)) {
            return $this->returnError(400, 'Type field cannot be blank');
        }

        if (! in_array($respondType, [
                CalendarEntryParticipant::PARTICIPATION_STATE_NONE,
                CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED,
                CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE,
                CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED,
            ], true)) {
            return $this->returnError(400, 'Invalid respond type');
        }

        $participationState = $calendarEntry->respond((int)$respondType);

        if($participationState->hasErrors()) {
            return $this->returnError(400, 'Bad request', ['errors' => $participationState->getErrors()]);
        } else {
            return $this->returnSuccess('Participation successfully changed.');
        }
    }

    private function prepareRequestParams($requestParams)
    {
        if (empty($requestParams['CalendarEntryForm']['start_date']) || empty($requestParams['CalendarEntryForm']['end_date'])) {
            $message = empty($requestParams['CalendarEntryForm']['start_date']) ? 'Start ' : 'End ';
            $message .=  'date cannot be blank';
            throw new HttpException(400, $message);
        }

        if (! empty($requestParams['CalendarEntryForm']['start_time'])) {
            $requestParams['CalendarEntry']['all_day'] = 0;
            $requestParams['CalendarEntryForm']['start_date'] .= ' ' . $requestParams['CalendarEntryForm']['start_time'] . ':00';
            unset($requestParams['CalendarEntryForm']['start_time']);
        } else {
            $requestParams['CalendarEntryForm']['start_date'] .= ' 00:00:00';
        }

        if (! empty($requestParams['CalendarEntryForm']['end_time'])) {
            $requestParams['CalendarEntry']['all_day'] = 0;
            $requestParams['CalendarEntryForm']['end_date'] .= ' ' . $requestParams['CalendarEntryForm']['end_time'] . ':00';
            unset($requestParams['CalendarEntryForm']['end_time']);
        } else {
            $requestParams['CalendarEntryForm']['end_date'] .= ' 23:59:00';
        }

        if (preg_match(DbDateValidator::REGEX_DBFORMAT_DATE, $requestParams['CalendarEntryForm']['start_date']) ||
            preg_match(DbDateValidator::REGEX_DBFORMAT_DATETIME, $requestParams['CalendarEntryForm']['start_date']) ||
            preg_match(DbDateValidator::REGEX_DBFORMAT_DATE, $requestParams['CalendarEntryForm']['end_date']) ||
            preg_match(DbDateValidator::REGEX_DBFORMAT_DATETIME, $requestParams['CalendarEntryForm']['end_date'])) {
            return $requestParams;
        }
        throw new HttpException(400, 'Wrong calendar entry date format.');
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