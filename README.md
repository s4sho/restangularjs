To run this project, you need to:

DATABASE
- import the database restci.sql
- set your database password in application/config/database.php

CONFIG
In the application/config.php file you need to:
- set salt
- set admin_email
- set bot_email

E-MAIL
In the application/models/users_model.php you need to:
- insert your e-mail into send_validation_email() function (the very beginning of the function)
- config your e-mail in the send_validation_email() function (around line 117)