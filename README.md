# Mousebook
a laboratory animal inventory management system (mySQL backend with HTML/PHP frontend)

INSTRUCTIONS FOR "INSTALLATION"
You will need a server that is capable of running mySQL and PHP (current versions used are mySQL PHP 5.4 and mySQL 5.6)

Schema/Tables for the mySQL backends (userbook and mousebook) are provided as an SQL file, you will need to upload/import this into you mySQL server.
php files are provided and can simply be uploaded to a directory in your "web root" directory (the default directory organization is defined below). A Zipped version of the php files is also provided that makes it easier to preserve the necessary directory layout.

/pages/databases.php
/php/mousebook/index.php
/php/mousebook/mousebook.css
/php/mousebook/php/[all of the remaining pages and folders]

*NOTE* - you will need to edit the php files
*search and replace "{server ip}" with the ip address of the server running your mySQL backend
*search and replace "{server login}" with the read only username so that the users attempting to sign in can be checked against the "userbook" database
*search and replace "{server pass}" with the read only password matched to the username used to access the "userbook" database

*NOTE - you will need to manage database users/passwords/access through the "userbook" database
