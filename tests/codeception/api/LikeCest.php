<?php

namespace rest\api;

use humhub\modules\like\models\Like;
use humhub\modules\post\models\Post;
use humhub\modules\rest\definitions\LikeDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class LikeCest extends HumHubApiTestCest
{
    protected $recordModelClass = Like::class;
    protected $recordDefinitionFunction = [LikeDefinitions::class, 'getLike'];

    public function testFindByObject(ApiTester $I)
    {
        $I->wantTo('find a like by object id');
        $I->amAdmin();

        $I->seePaginationGetResponse('like/find-by-object', $this->getRecordDefinitions([1,3]), [], [
            'model' => Post::class,
            'pk' => 1,
        ]);

        $I->seePaginationGetResponse('like/find-by-object', $this->getRecordDefinitions([2]), [], [
            'model' => Post::class,
            'pk' => 2,
        ]);

        $I->sendGet('like/find-by-object', [
            'model' => Post::class,
            'pk' => 123,
        ]);
        $I->seeNotFoundMessage('Content not found!');
    }

    public function testView(ApiTester $I)
    {
        $I->wantTo('see a like');
        $I->amAdmin();

        $I->sendGet('like/1');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendGet('like/123');
        $I->seeNotFoundMessage('Like not found!');
    }

    public function testViewWithoutPermission(ApiTester $I)
    {
        $I->wantTo('see a like by user without permission');
        $I->amUser1();

        $I->sendGet('like/1');
        $I->seeForbiddenMessage('You cannot read this content!');
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a like');
        $I->amUser2();

        $I->sendDelete('like/1');
        $I->seeSuccessMessage('Like successfully deleted!');

        $I->sendDelete('like/2');
        $I->seeForbiddenMessage('You cannot delete this content!');
    }

}
