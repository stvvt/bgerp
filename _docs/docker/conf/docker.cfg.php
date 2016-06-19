<?php

/*****************************************************************************
 *                                                                           *
 *      Примерен конфигурационен файл за приложение в Experta Framework      *
 *                                                                           *
 *      След като се попълнят стойностите на константите, този файл          *
 *      трябва да бъде записан в [conf] директорията под име:                *
 *      [име на приложението].cfg.php                                        *
 *                                                                           *
 *****************************************************************************/




/***************************************************
*                                                  *
* Параметри за връзка с базата данни               *
*                                                  *
****************************************************/ 

// Име на базата данни. По подразбиране е същото, като името на приложението
DEFINE('EF_DB_NAME', EF_APP_NAME);

// Потребителско име. По подразбиране е същото, като името на приложението
DEFINE('EF_DB_USER', 'root');

// По-долу трябва да се постави реалната парола за връзка
// с базата данни на потребителят дефиниран в предходния ред
DEFINE('EF_DB_PASS', ''); 

// Сървъра за на базата данни
DEFINE('EF_DB_HOST', 'localhost');
 
// Кодировка на забата данни
DEFINE('EF_DB_CHARSET', 'utf8');


/**
 * Секретени ключове използвани за кодиране в рамките на системата
 * Трябва да са различени, за различните инсталации
 * Моля сменете стойността, ако правите нова инсталация.
 * След като веднъж е установен, този параметъри не трябва да се променят
 **/
// Обща сол
DEFINE('EF_SALT', '');

// "Подправка" за кодиране на паролите
DEFINE('EF_USERS_PASS_SALT', '');

// Препоръчителна стойност между 200 и 500
DEFINE('EF_USERS_HASH_FACTOR', 400);

// Git бранч - на основният пакет
DEFINE('BGERP_GIT_BRANCH', 'master');

// Вербално заглавие на приложението
DEFINE('EF_APP_TITLE', 'bgERP');

/***************************************************
*                                                  *
* Някои от другите възможни константи              *
*                                                  *
****************************************************/ 

// Базова директория, където се намират по-директориите за
// временните файлове. По подразбиране е в
// EF_ROOT_PATH/temp
 # DEFINE( 'EF_TEMP_BASE_PATH', 'PATH_TO_FOLDER');

// Базова директория, където се намират по-директориите за
// потребителски файлове. По подразбиране е в
// EF_ROOT_PATH/uploads
 # DEFINE( 'EF_UPLOADS_BASE_PATH', 'PATH_TO_FOLDER');

// Език на интерфейса по подразбиране. Ако не се дефинира
// се приема, че езика по подрзбиране е български
 # DEFINE('EF_DEFAULT_LANGUAGE', 'en');

// Дали вместо ник, за име на потребителя да се приема
// неговия имейл адрес. По подразбиране се приема, че
// трябва да се изисква отделен ник, въведен от потребителя
 # DEFINE('EF_USSERS_EMAIL_AS_NICK', TRUE);

// Твърдо, фиксирано име на мениджъра с контролерните функции. 
// Ако се укаже, цялото проложение може да има само един такъв 
// мениджър функции. Това е удобство за специфични приложения, 
// при които не е добре името на мениджъра да се вижда в URL-то
 # DEFINE('EF_CTR_NAME', 'FIXED_CONTROLER');

// Твърдо, фиксирано име на екшън (контролерна функция). 
// Ако се укаже, от URL-то се изпускат екшъните.
 # DEFINE('EF_ACT_NAME', 'FIXED_CONTROLER');
 
// Дефинира се ако има нужда да се достъпват прикачените файлове през друг домейн 
 # DEFINE('BGERP_ABSOLUTE_HTTP_HOST', 'experta2.local');
 
// Дефинира пътят до частно репозитори
 # DEFINE('EF_PRIVATE_PATH', 'ABSOLUTE_PATH_TO_PRIVATE_REPOSITORY');

// Git бранч - на частният пакет - ако не е дефинирано се взима бранча на основния пакет
# DEFINE('PRIVATE_GIT_BRANCH', 'master');


// Игнориране на затварянето на модул "Help"
DEFINE('BGERP_DEMO_MODE', FALSE);


