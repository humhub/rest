# HumHub Rest API 

>Note: Work in progress! Not ready for production yet!


## Requirements

- HumHub URL Rewriting enabled
    - HumHub 1.3+
    - Enable pretty Urls in humhub configuration file **protected/config/common.php**
       ```php
        <?php
    
        return [
            'components' => [
                'urlManager' => [
                    'showScriptName' => false,
                    'enablePrettyUrl' => true,
                ],
            ]
        ];
        ```
    - Enable rewriting in Apache server
        - Rename the file `.htaccess-dist` in humhub home dir to `.htaccess`
        - Edit the Apache configuration file **/etc/apache2/sites-available/000-default.conf** 
        ```editorconfig
          <VirtualHost *:80>
                  <Directory /var/www/html>
                        Options Indexes FollowSymLinks MultiViews
                        AllowOverride All
                        Require all granted
                  </Directory>
           </VirtualHost>
        ```
        - Enable `mod-rewrite` in Apache by invoking `a2enmod rewrite` then restart apache by `service apache2 restart` 
 
## Installation

1. Download module files and put it into: **/protected/modules/rest**
2. Make sure module directory owned by Web user : `chmod -R www-data:www-data {humhub-Path}/protected/modules/rest`
2. Enable module (Administration -> Modules -> Installed -> RESTful API Interface -> Enable)
4. Configure module by one of these 2 ways:
    - set API Key (Administration -> Modules -> Installed ->  RESTful API Interface API -> Configure)
    - By simply click on `Humhub API` sub menu in the `Administrator` menu (Only visible when module is enabled)

## Documentation

* [Usage basics](docs/usage.md)
* [Changelog](docs/CHANGELOG.md)