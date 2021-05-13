<?php

namespace rest\api;

use humhub\modules\comment\models\Comment;
use humhub\modules\rest\definitions\CommentDefinitions;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;

class CommentCest extends HumHubApiTestCest
{
    public function testSeeComment(ApiTester $I)
    {
        $I->wantTo('see a comment by id');
        $I->amAdmin();

        $I->sendGet('comment/1');
        $I->seeSuccessResponseContainsJson($this->getCommentDefinition(1));

        $I->sendGet('comment/123');
        $I->seeNotFoundMessage('Comment not found!');
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

    private function getCommentDefinition(int $id): array
    {
        $comment = Comment::findOne(['id' => $id]);
        return ($comment ? CommentDefinitions::getComment($comment) : []);
    }

}
