# MEGA World v3.0
## Multiplayer Educational Game for All
Players can move through a virtual world meeting NPCs, completing quests to level up, and interact with other players in the game environment.

## Installation Instructions
To install a production MEGA World environment, follow these instructions.

### 1. Download
If you have git installed, it is preferred that you install it by cloning this repository
   to the directory on your webserver where you want to serve it from, eg: /var/web/mega/public_html.
   
**Alter this command with the correct repository URL and destination directory.**  

```git clone https://github.com/maiga-vip/mega/ /var/www/mega/public_html```

If you can't use git, you may download the zip file of the site from GitHub and manually unzip it to the web server. **This makes it much more difficult to keep MEGA World up to date!**

### 2. Install the Database
If this is a new installation of MEGA World, you will need to install the database. Choose a strong password for the DB, and a different password for the management admin user and run the database installation script in dev/. Be prepared to enter your database root password.
It is recommended you use the default "mega" database and username unless these names are already taken.

```bash dev/install_mega_db.sh```

### 3. Create the Database Configuration File
MEGA World looks for the database configuration in the .megadb.ini file outside of the root of MEGA World, which should be a folder not accessible to the web, as this file contains your sensitive database credentials. Create a new configuration file with the following command from the MEGA World public root (this folder):

```cp dev/.megadb.ini.example ../.megadb.ini```

Next, edit the file to ensure it has the correct database name, username and password. If you used the default settings you should only need to change the password.

```nano ../.megadb.ini```

*(Or use vim or any editor of your choice.)*

### 4. Ensure the Config.class.php file points to the correct path for .megadb.ini
If everything is already working you may ignore this step, but if you have an atypical setup, you may need to place your .megadb.ini in a different location than suggested.
If this is the case, edit classes/Config.class.php to set the appropriate path for the .megadb.ini file on the line
```php
const DATABASE_CONFIG_FILE = "../.megadb.ini";
```

### 5. Enable Language Support
Some servers may not already have the required language support to allow MEGA World to support certain locales. You may
need to configure this on your server.

#### Debian systems
Run this command
```sudo dpkg-reconfigure locale```

Select the UTF-8 version of any languages you would like to support. Currently MEGA World suppports French (France),
French (Canada), and Chinese Simplified (China). Ensure these languages are enabled on your system.

#### Ubuntu systems
You can check the status of any locales with 

```locale -a```

If you don't see all the locales you want supported, you can install them with

```apt-get install language-pack-fr``` (for French)

```apt-get install language-pack-zh-hans``` (for Chinese Simplified)

```apt-get install language-pack-zh-hant``` (for Chinese Traditional)

And after, restart your web server by running
```apache2ctl restart```


### 6. Configure your Webserver
At this point, you should have a functional installation of MEGA World, and all that is left is to configure your webserver.
Reference the documentation for your web server if you aren't familiar with how to create and enable sites.

### 7. Install Management
By default, MEGA World has no content. You will have to create maps, NPCs, and quests for users to interact with. To do this, you will need the [MEGA World Management](https://github.com/maiga-vip/management) application. Use the instructions at the link to get that installed.

## Development Instructions
If you wish to work on development of MEGA World, it is recommended that you style using [Tailwind CSS](https://tailwindcss.com/).
This is a design system that uses single-purpose classes in your HTML to style everything, allowing you to stay in your HTML/PHP code without having to keep switching to external stylesheets and spend time naming classes. The result ends up being a lot easier to maintain and leads to consistent styling.
The source CSS is in the ```css/tailwind-src.css``` folder if you need to edit any of the existing styles that are not part of stock Tailwind. This is the source for the Tailwind compiler and it produces ```css/tailwind.css``` as output with all the normal CSS required by MEGA World.

The Tailwind configuration is in ```tailwind.config.js```.

To install the Dependencies for Tailwind, use the following command:

```npm install```

While working on any code that may involve styling changes, you should be running Tailwind CLI at the command line in "watch" mode so that it automatically adds any new classes you use into the compiled css/tailwind.css file and keeps it up to date. To do this you can use

```npm run watch```


## Unit Testing
If you wish to take development to the next level, you can use automated unit testing. A few unit tests for PHP Unit have been set up in the ```tests/``` folder. To run these unit tests, you will have to install PHP Unit with the following command.

```composer install```

If you don't already have Composer, it is highly recommended you install it. You can install it with instructions at https://getcomposer.org/

## Localization Instructions
If you wish to support additional languages, you will need to do the following.
1. Ensure that any translatable text is wrapped in the ```gettext()``` or ```_()``` functions in PHP code.
2. Extract those text strings into a Portable Object (.PO) file using xgettext. ```dev/update_po.sh``` can be used to run this command and create a fresh PO file in ```locale/en_CA/LC_MESSAGES```.
3. Enter the equivalent of all the strings in the PO file in the target language. Note that some strings may require multiple forms (for singular and plural). The translator may use [POEdit](https://poedit.net/) to make it easier than working with the plain text file.
4. Compile the .PO file to an .MO (Machine Object) file using ```msgfmt messages.po -o messages.mo``` in the LC_MESSAGES folder for each locale.
5. Additionally, you can create a user guide in a new language by making a new version of one of the  ```locale/guide_content_en_CA.html``` files in the target language and give it the correct locale name.
6. Enable the language using the Languages view in [MEGA World Management](https://github.com/maiga-vip/management/).
