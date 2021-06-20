## Installation

The following is a guide to install this application on your local machine:

1) install xampp
2) install composer for xampp https://thecodedeveloper.com/install-composer-windows-xampp/
3) install drupal using composer: https://www.drupal.org/docs/installing-drupal.
  Documentation will show the importance of using composer at it will allow to run "composer install" automatically.
    This will download all dependencies required by Drupal in the "/vendor" folder including Sumfony packages
4) create your database (do not forget to manage your user accounts in your phpmyadmin in case using xampp) and open your local url http://localhost/my_web_app/web ("my_web_app" being the name of the drupal installation when running the composer installation. Do not forget to adjust your php.ini requirements https://www.drupal.org/docs/develop/local-server-setup/windows-development-environment/quick-install-drupal-with-xampp-on
5) install and configure drupal console https://www.drupal.org/docs/installing-and-configuring-drupal-console-with-xampp-running-on-windows-10

## Notes

1) your xampp may not have OPcode caching, it is recommended to install it to improve site performance https://www.drupal.org/forum/support/installing-drupal/2015-11-26/how-to-enable-opcache-in-xampp
2) you can configure the port number in your php.ini
3) After installation, run a status check in /admin/reports/status, you might find errors and warnings

## Database

In the root folder you will see a .sql file. This file can be imported to import the views created in the back office for the user display of beers.
Note that in this database beers has already been imported.
Two views are created: one to display beers to user (/beers) and one to administrator (/admin/config/beers-data-view)
A custom menu link under "Structure" is added for the latter

## Drupal

A custom theme has been created in this directory and can be downloaded and added to the themes folder. This is an empty custom theme that can be activated in the back-office to be developed from scratch.
Folder system has been created with templates folder, .info.yml, .libraries.yml and .theme files.
In the templates/views folder a views template for the view page "/beers" can be added with the name "views-view--[viewid]--[view-display-id].html.twig" and more.
This theme has already attached css and js libraries (no frameworks or automation tools such as Foundation/Bootstrap or Gulp/Grunt are implemented)

## Module

The custom punkapi module is added under /modules/custom folder with a READ.ME file explaining installation and functionalities
