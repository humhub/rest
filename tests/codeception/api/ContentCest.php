<?php

namespace rest\api;

use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\definitions\ContentDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class ContentCest extends HumHubApiTestCest
{
    protected $recordModelClass = Content::class;
    protected $recordDefinitionFunction = [ContentDefinitions::class, 'getContent'];

    public function testListContainers(ApiTester $I)
    {
        $I->wantTo('see all containers');
        $I->amAdmin();

        $I->seePaginationGetResponse('content/container', $this->getContainerDefinitions([1,2,3,4,5,6,7,8,9,10,11,12,13]));
    }

    public function testFindByContainer(ApiTester $I)
    {
        $I->wantTo('find content by container');
        $I->amAdmin();

        $I->seePaginationGetResponse('content/find-by-container/1', $this->getRecordDefinitions([1,2]));

        $I->sendGet('content/find-by-container/123');
        $I->seeNotFoundMessage('Content container not found!');
    }

    public function testSeeContent(ApiTester $I)
    {
        $I->wantTo('see a content by id');
        $I->amAdmin();

        $I->sendGet('content/1');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendGet('content/123');
        $I->seeNotFoundMessage('Content not found!');
    }

    public function testSeeContentWithoutPermission(ApiTester $I)
    {
        $I->wantTo('see a content without view permission');
        $I->amUser1();

        $I->sendGet('content/1');
        $I->seeForbiddenMessage('You cannot view this content!');
    }

    private function getContainerDefinitions(array $ids): array
    {
        return $this->getRecordDefinitions($ids, ContentContainer::class, [ContentDefinitions::class, 'getContentContainer']);
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a content');
        $I->amAdmin();

        $I->sendDelete('content/1');
        $I->seeSuccessMessage('Content successfully deleted!');

        $I->sendDelete('content/1');
        $I->seeNotFoundMessage('Content not found!');
    }

    public function testDeleteWithoutPermission(ApiTester $I)
    {
        $I->wantTo('delete a content by user without permission');
        $I->amUser3();

        $I->sendDelete('content/1');
        $I->seeForbiddenMessage('You cannot delete this content!');
    }

}
