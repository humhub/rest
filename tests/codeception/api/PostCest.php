<?php

namespace rest\api;

use humhub\modules\post\models\Post;
use humhub\modules\rest\definitions\PostDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class PostCest extends HumHubApiTestCest
{
    protected $recordModelClass = Post::class;
    protected $recordDefinitionFunction = [PostDefinitions::class, 'getPost'];

    public function testList(ApiTester $I)
    {
        $I->wantTo('see all posts');
        $I->amAdmin();

        $I->seePaginationGetResponse('post', $this->getRecordDefinitions([1,2,4,6,7,8,9,10,12,13,14]));
    }

    public function testFindByContainer(ApiTester $I)
    {
        $I->wantTo('find posts by container');
        $I->amAdmin();

        $I->seePaginationGetResponse('post/container/1', $this->getRecordDefinitions([1,2]));
        $I->seePaginationGetResponse('post/container/4', $this->getRecordDefinitions([7,8,9]));
    }

    public function testView(ApiTester $I)
    {
        $I->wantTo('see a post by id');
        $I->amAdmin();

        $I->sendGet('post/1');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendGet('post/3');
        $I->seeForbiddenMessage('You cannot view this content!');

        $I->sendGet('post/123');
        $I->seeNotFoundMessage('Requested content not found!');
    }

    public function testCreate(ApiTester $I)
    {
        $I->wantTo('create a post');
        $I->amAdmin();

        $I->sendPost('post/container/1', [
            'data' => [
                'message' => 'New created message from API test',
            ]
        ]);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(15));
    }

    public function testUpdate(ApiTester $I)
    {
        $I->wantTo('update a post');
        $I->amAdmin();

        $I->sendPut('post/1', [
            'data' => [
                'message' => 'Updated message for Post 1',
            ]
        ]);
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendPut('post/123');
        $I->seeNotFoundMessage('Request object not found!');
    }

    public function testUpdateWithoutPermission(ApiTester $I)
    {
        $I->wantTo('update a post by user without permission');
        $I->amUser1();

        $I->sendPut('post/1');
        $I->seeForbiddenMessage('You are not allowed to update this content!');
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a post');
        $I->amAdmin();

        $I->sendDelete('post/1');
        $I->seeSuccessMessage('Successfully deleted!');

        $I->sendDelete('post/123');
        $I->seeNotFoundMessage('Content record not found!');
    }

}
