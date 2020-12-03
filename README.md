## Install the Application

Run this command from the directory in which you want to install your new application.

```shell script
composer create-project leadvertex/plugin-macros-excel
```

* Create `const.php` from `const.php.example` and set environment values
* Point your virtual host document root to your new application's `public/` directory.
* Ensure `runtime/` and `public/output/` is web writeable.