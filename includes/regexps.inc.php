<?php

/**
* AT CMS version 2.0.0
* Система управления содержимым веб-сайта
* © 2007–2008 группа разработчиков AT CMS
* ----------------------------------------------------------------
* Главный разработчик: Илья “WatchRooster” Аверков
* Интерпретатор BBML от Алексея “shade” Золотова
* Многие идеи заимствованы из проекта ArigatoCMS,
* разработанного Алексеем “Arigato” Акатовым в 2006 г.
* Выражаю также благодарность следующим лицам:
* MayKoPSKiy, Erik, Вася Триллер
* ----------------------------------------------------------------
* http://shamangrad.net/project.php?act=view&prj=atcms2
* ----------------------------------------------------------------
* Лицензировано на условиях GNU GPL v 2.0,
* за более подробной информацией смотрите COPYING.
* ----------------------------------------------------------------
*
*/

define('PCREGEXP_EMAIL', '#^[a-z0-9_-][a-z0-9_\.-]*@[a-z0-9_-][a-z0-9_\.]*\.[a-z]{2,5}$#iU'); //Адрес электронной почты
define('PCREGEXP_HOSTNAME', '#^([a-z0-9][\.a-z0-9_-]*[a-z0-9])(:([0-9]{1,5}))?$#iU');
define('PCREGEXP_PASSWORD', '#^.{3,16}$#iU'); //Пароль
define('PCREGEXP_USERNAME', '#^[a-z0-9_-]{3,32}$#iU'); //Логин
define('PCREGEXP_LAYOUT', '#^[a-z0-9_-]{1,32}$#iU'); //Скин
define('PCREGEXP_JABBER', '#^.+@[a-z0-9_-][a-z0-9_\.]+\.[a-z]{2,5}$#iU'); //JID
define('PCREGEXP_ICQ', '#^[0-9]{5,11}$#iU'); //ICQ uin
define('PCREGEXP_TIMEZONE', '#^\-?[0-9]([1-3]|\.5)?$#iU'); //Временная зона
define('PCREGEXP_MODULE_NAME', '#^[a-z][0-9a-z_]*$#iU'); //Имя модуля
define('PCREGEXP_LANGFILE_NAME', '#^([a-z]{2})\.php$#iU'); //Имя языкового файла
define('PCREGEXP_LANGNAME', '#^[a-z]{2}$#iU'); //Имя языка
define('PCREGEXP_INTEGER', '#^[0-9]+$#U');
define('PCREGEXP_INTEGER_NOZERO', '#^[1-9]{1}[0-9]*$#U'); //Натуральное число
define('PCREGEXP_DIGIT', '#^[1-9]$#U');
define('PCREGEXP_MD5_HASH', '#[0-9a-z]{32}#iU');
define('PCREGEXP_DATE', '#^([1-3]{1}[0-9]{1}|[1-9]{1})\.([1-3]{1}[0-9]{1}|[1-9]{1})\.[0-9]{4}$#U');
define('PCREGEXP_WEBPATH', '#^.*$#U');
define('PCREGEXP_PHONE_NUMBER', '#^\+[0-9]+$#U');
define('PCREGEXP_SITE_URL', '#^https?://[0-9a-z_\-/~\.]+$#iU');

?>
