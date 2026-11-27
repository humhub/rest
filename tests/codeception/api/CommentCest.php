<?php

namespace rest\api;

use humhub\modules\comment\models\Comment;
use humhub\modules\post\models\Post;
use humhub\modules\rest\definitions\CommentDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class CommentCest extends HumHubApiTestCest
{
    protected $recordModelClass = Comment::class;
    protected $recordDefinitionFunction = [CommentDefinitions::class, 'getComment'];

    public function testSeeComment(ApiTester $I)
    {
        $I->wantTo('see a comment by id');
        $I->amAdmin();

        $I->sendGet('comment/1');
        $I->seeSuccessResponseContainsJson($this->getRecordDefinition(1));

        $I->sendGet('comment/123');
        $I->seeNotFoundMessage('Comment not found!');
    }

    public function testCreateForNonexistentContent(ApiTester $I)
    {
        $I->wantTo('see an error when commenting a nonexistent content');
        $I->amAdmin();

        $I->sendPost('comment?contentId=99999', ['message' => 'Test']);
        $I->seeNotFoundMessage('Content not found!');
    }

    public function testCreate(ApiTester $I)
    {
        $I->wantTo('comment a content');
        $I->amUser1();

        $contentId = Post::findOne(['id' => 11])->content->id; // Private post of User1 in Space 2

        $I->sendPost('comment?contentId=' . $contentId, ['message' => 'Test comment']);
        $I->seeSuccessResponseContainsJson(['message' => 'Test comment']);
        $I->seeRecord(Comment::class, ['content_id' => $contentId, 'message' => 'Test comment']);
    }

    public function testCreateWithoutViewPermission(ApiTester $I)
    {
        $I->wantTo('see that I cannot comment a content I cannot view');
        $I->amUser2(); // Not a member of Space 2

        $contentId = Post::findOne(['id' => 11])->content->id; // Private post of User1 in Space 2

        $I->sendPost('comment?contentId=' . $contentId, ['message' => 'Test comment']);
        $I->seeForbiddenMessage('You cannot view this content!');
        $I->dontSeeRecord(Comment::class, ['content_id' => $contentId, 'message' => 'Test comment']);
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete a comment');
        $I->amAdmin();

        $I->sendDelete('comment/1');
        $I->seeSuccessMessage('Comment successfully deleted!');

        $I->sendDelete('comment/1');
        $I->seeNotFoundMessage('Comment not found!');
    }

    public function testDeleteWithoutPermission(ApiTester $I)
    {
        $I->wantTo('delete a comment by user without permission');
        $I->amUser1();

        $I->sendDelete('comment/1');
        $I->seeForbiddenMessage('You cannot delete this comment!');
    }

}
