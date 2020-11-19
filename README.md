# Emag Product Checker project
[![Build Status](https://travis-ci.com/mihaitmf/emag-product-checker.svg?branch=main)](https://travis-ci.com/mihaitmf/emag-product-checker)

## Requirements
- [![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-blue)](https://php.net/)
- composer

### Check if Emag product is available according to some constraints and send push notification
Check if a product is in stock, has a price below a given maximum and is sold by Emag.
Send a push notification on mobile if these conditions are fulfilled. 

You must have an IFTTT (https://ifttt.com/) account and an applet with a webhook created.

The webhook URL from the IFTTT account must be defined in the `config.ini` file.

In summary, this application does the following operations:
* fetch the Emag product page from the URL given as argument 
* parse the relevant product data from the HTML page, such as product price, stock availability and seller name
* compare fetched product data to the constraints: stock is available, price is less than the given maximum, seller is Emag
* send request to the IFTTT webhook which will trigger a push notification to the IFTTT mobile app
(you need to have the mobile app installed and logged in to the IFTTT account) 

#### Example of command to check a product:
* `php <script-name>.php check "<productShortName>" "<productMaxPrice>" "<productUrl>"`
* Linux: `php bin/run.php check "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"`
* Windows: `php bin\run.php check "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"`

### Check a list of Products
* For convenience, you can directly define a list of products and check all of them with a single command 
* Update the list of products directly in the constant array from `EmagProductListCheckerCommand`
* The structure of the array is the following:
```
[
    [
        <productShortName1>,
        <productMaxPrice1>,
        <productUrl1>,
    ],
    [
        <productShortName2>,
        <productMaxPrice2>,
        <productUrl2>,
    ],
]
```
* The `check-list` command will iterate over the list and trigger a `check` command for each item.
* There is a random wait time added between the requests to try to simulate a more human behaviour
(in order not to get blocked by Emag).
 
#### Example of command to check a list of products:
* `php <script-name>.php check-list`
* Linux: `php bin/run.php check-list`
* Windows: `php bin\run.php check-list`


### How to set up cron job on Linux
* List crons: crontab -l
* Edit crontab file: crontab -e
* Add new entry at the end of the file:
  * 30,59 9-23 * * * /home/mihai/projects/emag-product-checker/bin/run.php check-list
  * This is an example to run: "At minute 30 and 59 past every hour from 9 through 23."
* Crontab syntax: https://crontab.guru/#30,59_9-23_*_*_*

### How to Create Task Scheduler in Windows
* Win+q -> Task Scheduler -> Create Task
* General Tab -> Run whether user is logged on or not
* Triggers Tab -> New
  * On a schedule -> Daily -> Start (set time) -> Recur every 1 days
  * Repeat task every 30 minutes -> for a duration of 12 hours -> Stop task if it runs longer than 3 days
  * Enabled
* Actions Tab -> New -> Start a program
  * Program/script: php
  * Add arguments:
    * bin\run.php check-list
    * OR
    * bin\run.php check "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"
  * Start in: C:\_dev\emag-product-checker 
