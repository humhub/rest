# API development 

## Documentation

To completely adapt the API documentation after a change, the following steps are necessary.

### Swagger

The Swagger documentation is located in the folder `/docs/swagger`, you need to rebuild the html documentation 
at `/docs/html` which is based on the Swagger YAML files.

To create a HTML documentation you need to install the `redoc-cli` NPM package.

Build HTML documentation:

```
cd docs/swagger
./build-all.sh
```

### PostMan

Also add examples to the PostMan API request collection located in the folder: `/docs/postman`.

### Other module support

To append endpoints from another module:

1) Add event in the file `config.php` of your module the following line:

```php
['class' => 'humhub\modules\rest\Module', 'event' => 'restApiAddRules', 'callback' => ['humhub\modules\your_module\Events', 'onRestApiAddRules']],
```

2) Implement `humhub\modules\your_module\Events::onRestApiAddRules` like this:

```php
public static function onRestApiAddRules()
{
    /* @var humhub\modules\rest\Module $restModule */
    $restModule = Yii::$app->getModule('rest');
    $restModule->addRules([
        ['pattern' => 'your_module/<objectId:\d+>/user/<userId:\d+>', 'route' => 'your_module/rest/user/add', 'verb' => 'POST'],
        ...
    ]);
}
```

3) Create a new controller, for example, here `protected/modules/your_module/controllers/rest/UserController.php`:

```php
namespace humhub\modules\your_module\controllers\rest;

use humhub\modules\rest\components\BaseController;

class UserController extends BaseController
{
    public function actionAdd($messageId, $userId)
    {
        return [...];
    }
}
```