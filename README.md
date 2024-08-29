EasyAdmin No Private Composer Plugin
==================================

When using EasyAdmin No Final Composer Plugin, EasyAdmin classes can be inherited.
But some contructors remain private...

This project is a Composer plugin that replaces the `private function __construct` PHP keyword from all
EasyAdmin classes by `public function __construct`, so you can call parent constructors when extending a class.

It shares a lot of code from the original EasyAdmin No Final Composer Plugin: https://github.com/EasyCorp/easyadmin-no-final-plugin

Run the following command to install this Composer plugin in your projects:

```
$ composer require bytespin/easyadmin-no-private-plugin
```

When does this plugin update EasyAdmin classes?

* Just after installing this Composer plugin;
* Just after installing or updating any EasyAdmin version.
