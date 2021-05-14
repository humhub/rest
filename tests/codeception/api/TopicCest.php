<?php

namespace rest\api;

use humhub\modules\rest\definitions\TopicDefinitions;
use humhub\modules\topic\models\Topic;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class TopicCest extends HumHubApiTestCest
{
    public function testList(ApiTester $I)
    {
        $I->wantTo('see all topics');
        $I->amAdmin();

        $I->seePaginationGetResponse('topic', $this->getTopicDefinitions([1,2,3,4,5]));
    }

    public function testFindByContainer(ApiTester $I)
    {
        $I->wantTo('find topics by container');
        $I->amAdmin();

        $I->seePaginationGetResponse('topic/container/1', $this->getTopicDefinitions([1]));
        $I->seePaginationGetResponse('topic/container/2', $this->getTopicDefinitions([2,4]));
        $I->seePaginationGetResponse('topic/container/3', $this->getTopicDefinitions([3]));
        $I->seePaginationGetResponse('topic/container/4', $this->getTopicDefinitions([5]));
    }

    public function testView(ApiTester $I)
    {
        $I->wantTo('see a topic by id');
        $I->amAdmin();

        $I->sendGet('topic/1');
        $I->seeSuccessResponseContainsJson($this->getTopicDefinition(1));

        $I->sendGet('topic/123');
        $I->seeNotFoundMessage('Topic not found!');
    }

    public function testCreate(ApiTester $I)
    {
        $I->wantTo('create a topic');
        $I->amUser1();

        $I->sendPost('topic/container/1');
        $I->seeForbiddenMessage('You are not allowed to create topic for user!');

        $I->sendPost('topic/container/4');
        $I->seeForbiddenMessage('You are not allowed to create topic for space!');

        $I->sendPost('topic/container/2', [
            'name' => 'Topic created by API test',
            'color' => '#06F',
            'sort_order' => 800,
        ]);
        $I->seeSuccessResponseContainsJson($this->getTopicDefinition(6));
    }

    public function testUpdate(ApiTester $I)
    {
        $I->wantTo('update a topic');
        $I->amUser1();

        $I->sendPut('topic/2', [
            'name' => 'Updated topic from API test',
            'color' => '#9F0',
            'sort_order' => 200,
        ]);
        $I->seeSuccessResponseContainsJson($this->getTopicDefinition(2));

        $I->sendPut('topic/123');
        $I->seeNotFoundMessage('Topic not found!');
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a topic');
        $I->amAdmin();

        $I->sendDelete('topic/1');
        $I->seeSuccessMessage('Topic successfully deleted!');

        $I->sendDelete('topic/123');
        $I->seeNotFoundMessage('Topic not found!');
    }

    private function getTopicDefinition(int $id): array
    {
        $topic = Topic::findOne(['id' => $id]);
        return ($topic ? TopicDefinitions::getTopic($topic) : []);
    }

    private function getTopicDefinitions(array $ids): array
    {
        $topics = Topic::find()->where(['IN', 'id', $ids])->all();
        $topicDefinitions = [];
        foreach ($topics as $topic) {
            $topicDefinitions[] = TopicDefinitions::getTopic($topic);
        }
        return $topicDefinitions;
    }

}
