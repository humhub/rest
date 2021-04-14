<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 * @author Usama Ayaz <usama.ayaz@siliconplex.com>
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\friendship\models\Friendship;
use humhub\modules\user\models\User;
use yii\helpers\Url;

/**
 * Class AccountController
 */
class FriendshipDefinitions {

   
    public static function getFriendship(Friendship $friendship) {

        return [
            'id' => $friendship->id,
            'created_at' => $friendship->created_at,
            'friend' => UserDefinitions::getUserShort($friendship->friendUser),
            'user' => UserDefinitions::getUserShort($friendship->user)
                
        ];
    }
    
    
    public static function getFriendShipForSend(Friendship $friendship) {

        return [
            'id' => $friendship->id,
            'created_at' => $friendship->created_at,
            'friend' => UserDefinitions::getUserShort($friendship->user),
            
                
        ];
    }
    
    public static function getFriendShipForReceive(Friendship $friendship) {

        return [
            'id' => $friendship->id,
            'created_at' => $friendship->created_at,
            'friend' => UserDefinitions::getUserShort($friendship->friendUser),
            
                
        ];
    }
    

}
