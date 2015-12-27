# What is Kleeja? 
Kleeja is a free, Feature rich, Open Source file upload system, designed to help the webmasters to provide a decent files hosting service on their sites, Kleeja comes with a simple source code and powerful User system, also with easy template system so you can easily make your styles.

# Requirements
  - A webserver (preferably Apache).
  - PHP5+
  - Mysql4.2.2+
  - GD library
  - Kleeja doesn't work well with Free hosting because of the restricted limits.
  - Other requirements and required PHP functions will be checked at installation.

# version 1.6:
* Fix XSS bug at uploading files [ thanks to Ebram Atef @geekpero ]; bug#1253
* Add useful https header to improve security.
* fix compatibility issue with php 5.5; bug#1252, bug#1240, bug#1239, bug#1241
* fix an error with thumbs.php if no GD installed
* fix bug where admin can not change the ACL [permissions] of users. bug##1229
* fix bug where user can’t see his folder if ACL to see other’s folders is off to him. bug#1228.
* remove gzip feature because user who doesn’t know how to use it keep using it. bug#1226
* no more mysql driver, all now transformed to mysqli. bug #1224
* fix bug if number of files that user can upload is 0, he can still upload! bug#1223.
* remove backup feature, no need for it.
