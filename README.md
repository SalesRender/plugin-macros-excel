## Install the Application

Run this command from the directory in which you want to install your new application.

    php composer.phar create-project leadvertex/plugin-export-excel

* Create `const.php` from `const.php.example` and set environment values
* Point your virtual host document root to your new application's `public/` directory.
* Ensure `runtime/` and `public/compiled/` is web writeable.