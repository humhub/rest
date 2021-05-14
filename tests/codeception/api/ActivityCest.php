<?php

namespace rest\api;

use humhub\modules\activity\models\Activity;
use humhub\modules\rest\definitions\ActivityDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class ActivityCest extends HumHubApiTestCest
{
    public function testList(ApiTester $I)
    {
        $I->wantTo('see all activities');
        $I->amAdmin();

        $I->seePaginationGetResponse('activity', $this->getActivityDefinitions([100, 101, 102, 103]), ['perPage' => 10]);
    }

    public function testView(ApiTester $I)
    {
        $I->wantTo('see an activity by id');
        $I->amAdmin();

        $I->sendGet('activity/100');
        $I->seeSuccessResponseContainsJson($this->getActivityDefinition(100));

        $I->sendGet('activity/1234');
        $I->seeNotFoundMessage('Activity not found');
    }

    public function testFindByContainer(ApiTester $I)
    {
        $I->wantTo('find activities by container');
        $I->amAdmin();

        $I->seePaginationGetResponse('activity/container/4', $this->getActivityDefinitions([100, 101, 102]), ['perPage' => 10]);
        $I->seePaginationGetResponse('activity/container/7', $this->getActivityDefinitions([103]), ['perPage' => 10]);
        $I->seePaginationGetResponse('activity/container/1', [], ['perPage' => 10]);
    }

    private function getActivityDefinition(int $id): array
    {
        $activity = Activity::findOne(['id' => $id]);
        return ($activity ? ActivityDefinitions::getActivity($activity) : []);
    }

    private function getActivityDefinitions(array $ids): array
    {
        $activitiesQuery = Activity::find()->where(['IN', 'id', $ids]);

        $activities = [];
        foreach ($activitiesQuery->all() as $activity) {
            $activities[$activity->id] = ActivityDefinitions::getActivity($activity);
        }

        $activityDefinitions = [];
        foreach ($ids as $id) {
            $activityDefinitions[] = isset($activities[$id]) ? $activities[$id] : null;
        }

        return $activityDefinitions;
    }


}
