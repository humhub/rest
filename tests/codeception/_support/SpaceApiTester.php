<?php
namespace rest;

use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class SpaceApiTester extends ApiTester
{
    use _generated\ApiTesterActions;

    /**
     * Define custom actions here
     */

    public function getSpaceDefinition(int $id): array
    {
        $space = Space::findOne(['id' => $id]);
        return ($space ? SpaceDefinitions::getSpace($space) : []);
    }

    public function getSpaceDefinitions(array $ids): array
    {
        $spaces = Space::find()->where(['IN', 'id', $ids])->all();
        $spaceDefinitions = [];
        foreach ($spaces as $space) {
            $spaceDefinitions[] = SpaceDefinitions::getSpace($space);
        }
        return $spaceDefinitions;
    }

    public function getSpaceMembershipDefinitions(int $spaceId): array
    {
        $memberships = Membership::findAll(['space_id' => $spaceId]);
        $membershipDefinitions = [];
        foreach ($memberships as $membership) {
            $membershipDefinitions[] = SpaceDefinitions::getSpaceMembership($membership);
        }
        return $membershipDefinitions;
    }

}
