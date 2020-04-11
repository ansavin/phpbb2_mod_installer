# phpbb2_mod_installer

is a set of PHP libraries that aimed to automate [phpBB 2 MODs installation](https://wiki.phpbb.com/MOD_Text_Template)

## libMODio.php

this class contains code that actually make changes in patched files

## libMODparser.php

this class contains parser code for [phpBB 2 MOD Text format](https://wiki.phpbb.com/MOD_Text_Template)

## example.html

file needed for library usage demonstration

## example_install.txt

dummy mod instruction example for library usage demonstration

## exampleInstall.php

dummy mod install script for library usage demonstration

## expected.html

file that contains expected result of dummy mod installing

## demonstrate.sh

Bash script to demonstrate what does this mod

## Usage example

To see how does this mod work, you need web-server with ability to run php files (for example, apache2 with apache2-mod-php installed)
Just put this repo in folder where web-server can see it (for example, /var/www/html as default for apache2 run by GNU/Linux) and type in browser 127.0.0.1/phpbb2_mod_installer/exampleInstall.php (in case of running script on localhost)
Check example.html before and after running script to see the changes

If you don`t want to run this script via web-server, there is one more option (for GNU/Linux OS) - just run demonstrate.sh in terminal

## Feature

Implementing INCREMENT instruction (see [documantation](https://wiki.phpbb.com/MOD_Text_Template))
Limitations:

* Incrementing string should start from non-digit char. In other case, in string "7 Samurai" we can`t increment 7
* No negative number are supported. In other case, script assumes all digit are natural (with zero)
* Now INCREMENT supports only one-digit numbers (but you can increbent or decrement it by any natiral number)

## Copyright

(c) Alek$ <du-sky@ya.ru> - 2008

(c) [Aradmin Software](http://aradmin.org) - 2008

[Alek$ actual github account](https://github.com/nevkontakte)
