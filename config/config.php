<?php

/*
|--------------------------------------------------------------------------
| Configurações do Banco de Dados
|--------------------------------------------------------------------------
*/
define('DB_HOST', 'localhost');
define('DB_NAME', 'workday');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/*
|--------------------------------------------------------------------------
| Configurações da Aplicação
|--------------------------------------------------------------------------
*/
define('APP_NAME', 'Workday');
define('APP_URL', 'http://localhost/workday');
define('APP_ENV', 'development'); // development | production
define('APP_DEBUG', true);
define('APP_TIMEZONE', 'America/Sao_Paulo');
define('APP_LOCALE', 'pt_BR');

/*
|--------------------------------------------------------------------------
| Configurações de Segurança
|--------------------------------------------------------------------------
*/
define('SECRET_KEY', 'workday_secret_key_change_in_production_2024');
define('JWT_EXPIRY', 86400); // 24 horas em segundos
define('SESSION_LIFETIME', 7200); // 2 horas

/*
|--------------------------------------------------------------------------
| Configurações de E-mail
|--------------------------------------------------------------------------
*/
define('MAIL_DRIVER',     'log');            // 'log' | 'smtp' | 'mail'
define('MAIL_HOST',       'smtp.mailtrap.io');
define('MAIL_PORT',       587);
define('MAIL_ENCRYPTION', 'tls');            // 'tls' | 'ssl' | ''
define('MAIL_USERNAME',   '');
define('MAIL_PASSWORD',   '');
define('MAIL_FROM',       'noreply@workday.app');
define('MAIL_FROM_NAME',  'Workday');

/*
|--------------------------------------------------------------------------
| Configurações de Arquivo
|--------------------------------------------------------------------------
*/
define('UPLOAD_MAX_SIZE', 10485760); // 10MB em bytes
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx','zip']);

/*
|--------------------------------------------------------------------------
| Configurações de Cache / Redis
|--------------------------------------------------------------------------
*/
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASS', '');

/*
|--------------------------------------------------------------------------
| WebSocket
|--------------------------------------------------------------------------
*/
define('WS_HOST', '0.0.0.0');
define('WS_PORT', 8080);
