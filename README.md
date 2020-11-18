# Emag Product Checker project
### Check if Emag product is available according to some constraints and send push notification
You must have an IFTTT (https://ifttt.com/) account and an applet with a webhook created.

The webhook URL from the IFTTT account must be defined in the `config.ini` file.

In summary, this project does:
* fetch the Emag product page from the URL given as argument 
* parse the relevant product data from the HTML page, such as product price, stock availability and seller name
* compare fetched product data to the constraints: stock is available, price is less than the given input, seller is Emag
* send request to the IFTTT webhook which will trigger a push notification to the IFTTT mobile app
(you need to have the mobile app installed and logged in to the IFTTT account) 

Example command:
* `php <script-name>.php "<productShortName>" "<productMaxPrice>" "<productUrl>"`
* Linux: `php bin/run.php check:single "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"`
* Windows: `php bin\run.php check:single "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"`

### How to set up cron job on Linux
* List crons: crontab -l
* Edit crontab file: crontab -e
* Add new entry at the end of the file:
  * 30,59 9-23 * * * /home/mihai/projects/emag-product-checker/bin/run.php
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
  * Add arguments: run\emag-product-checker.php "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"
  * Start in: C:\_dev\notifier 
