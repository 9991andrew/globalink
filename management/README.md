# MEGA World Management
This application is the management interface for creating content for and administering the [MEGA World](https://github.com/maiga-vip/mega) multiplayer educational game.
User documentation is included in the app and a [comprehensive walkthrough is available on YouTube](https://www.youtube.com/watch?v=SAL6YAYWuFo).

## Installation Instructions (for Production)
### 1. Download Management
For a production installation, first clone the Management project to a dedicated folder in your webserver. Note that only the ```public``` folder in the project will be accessible to the web. /var/www/management.megaworld will be used for purposes of this example.

```git clone https://github.com/maiga-vip/management /var/www/management.megaworld ```

It is ideal if Management is on its own subdomain, as setting it up within a subfolder is more complicated and difficult to maintain.

### 4. Run the Install Script
This script will
* Install the production dependencies
* Generate a .env file and app key
* Configure your filesystem permissions.

To do all this, run the install.sh script with

```bash install.sh```


### 5. Configure the .env
Finally, open the new .env file in your preferred editor and change the values appropriately

```nano .env```

* **APP_ENV** should be set to ```production``` if this is a production server.
* **APP_KEY** should have been set to a long, random key when you ran ```php artisan key:generate```
* **APP_DEBUG** should be ```false``` in production. In a development environment, set this to ```true``` so you can see what is causing errors. 
* **APP_URL** is the URL you will use to access management (eg https://management.megaworld.com)
* **MEGAWORLD_URL** is the URL of the MEGA World game (eg https://megaworld.com)
* **MEGAWORLD_FOLDER** is the directory where your MEGA World website is on your server. This is required so management can access the game images. (eg /var/www/mega/public_html/)
* **LOG_LEVEL** should probably be set to ```notice``` for production purposes.
* **DB_** set the DB_ variables as per your MEGA World database installation.
* **DB_PASSWORD** If you used the default settings, this may be the only DB_ variable you need to set, set it to the password for the mega DB user. Put this in double quotes.

The other settings are not important for now.

### 6. Allow Apache Overrides
If rewrites aren't working and valid routes are giving you 404 Not Found errors, this is likely because Apache is not allowing the .htaccess file in the ```public``` folder to enable URL rewrites.

To fix this, you will have to add ```AllowOverride All``` to your Apache configuration for this site.

Add the following lines to your ```/etc/apache2.conf```, replacing ```/var/www/management.megaworld.com/public``` with your actual management public folder:
```apacheconf
<Directory /var/www/management.megaworld.com/public>
    AllowOverride All
</Directory>
```
Put the changes into effect by running

```sudo systemctl reload apache2```

### 7. Test Your Installation
Now visit the website where you configured Management and test that it is working. If you are getting any errors, check your apache error log (```tail -f /var/log/apache2/error.log```) and **temporarily** set APP_DEBUG=true in the .env file.

If you are having trouble getting Management running at this point, you may still have issues with your filesystem permissions.

Apache needs to be able to write to certain directories to generate views and cache. To ensure of this, run the following commands, substituting your web server user for www-data in the first two lines.
* ```sudo chgrp -R www-data storage```
* ```sudo chgrp -R www-data bootstrap/cache```
* ```sudo chmod -R ug+rwx storage/framework/cache```
* ```sudo chmod -R ug+rwx storage/framework/views```
* ```sudo chmod -R ug+rwx storage/logs/```
* ```sudo chmod -R ug+rwx bootstrap/cache```


## About Laravel
Management is made with Laravel, a web application framework with expressive, elegant syntax. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

Laravel has extensive and thorough [documentation](https://laravel.com/docs), making it a breeze to get started.

If you prefer video tutorials, [Laracasts](https://laracasts.com) is a great place to learn about Laravel.

