# Notifier project
### Send push notifications to phone via IFTTT webhook.

Example command:
* Linux: `php run/notifier-push.php "hello my friend" "http://google.com"`
* Windows: `php run\notifier-push.php "hello my friend" "http://google.com"`

### Check if Emag product is in stock and below a price, and send push notification
Example command:
* `php run/emag-product-check-and-notify.php "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone
   -no-mop-alb-s5e02-00-white/pd/D888WWBBM/"
`

### How to Create Task Scheduler in Windows
* Win+q -> Task Scheduler -> Create Task
* General Tab -> Run whether user is logged on or not
* Triggers Tab -> New
  * On a schedule -> Daily -> Start (set time) -> Recur every 1 days
  * Repeat task every 5 minutes -> for a duration of 12 hours -> Stop task if it runs longer than 3 days
  * Enabled
* Actions Tab -> New -> Start a program
  * Program/script: php
  * Add arguments: run\emag-product-check-and-notify.php "Roborock S5 Max" 1800 "https://www.emag.ro/robot-de-aspirare-roborock-cleaner-s5-max-wifi-aspirator-si-mop-smart-top-up-navigare-lidar-setare-bariere-virtuale-zone-no-mop-alb-s5e02-00-white/pd/D888WWBBM/"
  * Start in: C:\_dev\notifier 
