<?php

namespace rest\api;

use humhub\modules\activity\models\Activity;
use humhub\modules\rest\definitions\ActivityDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class ActivityCest extends HumHubApiTestCest
{
    protected $recordModelClass = Activity::class;
    protected $recordDefinitionFunction = [ActivityDefinitions::class, 'getActivity'];

    public function testList(ApiTester $I)
    {
        $I->wantTo('see all activities');
        $I->amUser1();

        $I->seePaginationGetResponse('activity', $this->getRecordDefinitions([103]), ['perPage' => 10]);
    }

    public function testListAsSpaceMember(ApiTester $I)
    {
        $I->wantTo('see all activities of my spaces including private content');
        $I->amUser2();

        $I->seePaginationGetResponse('activity', $this->getRecordDefinitions([100, 101, 102, 103]), ['perPage' => 10]);
    }

    public function testView(ApiTester $I)
    {
        $I->wantTo('see an activity by id');
        $I->amUser1();

        $I->sendGet('activity/103');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(103));

        $I->sendGet('activity/1234');
        $I->seeNotFoundMessage('Activity not found');
    }

    public function testViewPrivateContentActivity(ApiTester $I)
    {
        $I->wantTo('see an activity about private content only as a space member');

        $I->amUser1();
        $I->sendGet('activity/100');
        $I->seeNotFoundMessage('Activity not found');
        $I->sendGet('activity/101');
        $I->seeNotFoundMessage('Activity not found');
        $I->sendGet('activity/102');
        $I->seeNotFoundMessage('Activity not found');
    }

    public function testViewPrivateContentActivityAsSpaceMember(ApiTester $I)
    {
        $I->wantTo('see an activity about private content as a space member');
        $I->amUser2();

        $I->sendGet('activity/101');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(101));
    }

    public function testFindByContainer(ApiTester $I)
    {
        $I->wantTo('find activities by container');
        $I->amUser1();

        $I->seePaginationGetResponse('activity/container/4', [], ['perPage' => 10]);
        $I->seePaginationGetResponse('activity/container/7', $this->getRecordDefinitions([103]), ['perPage' => 10]);
        $I->seePaginationGetResponse('activity/container/1', [], ['perPage' => 10]);

        $I->sendGet('activity/container/99999');
        $I->seeNotFoundMessage('Content container not found!');
    }

    public function testFindByContainerAsSpaceMember(ApiTester $I)
    {
        $I->wantTo('find activities including private content by container as a space member');
        $I->amUser2();

        $I->seePaginationGetResponse('activity/container/4', $this->getRecordDefinitions([100, 101, 102]), ['perPage' => 10]);
    }

}
