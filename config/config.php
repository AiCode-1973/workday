<?php

/*
|--------------------------------------------------------------------------
| Configurações do Banco de Dados
|--------------------------------------------------------------------------
*/
define('DB_HOST', '186.209.113.107');
define('DB_NAME', 'dema5738_workday');
define('DB_USER', 'dema5738_workday');
define('DB_PASS', 'Dema@1973');
define('DB_CHARSET', 'utf8mb4');

/*
|--------------------------------------------------------------------------
| Configurações da Aplicação — detecta ambiente pelo host
|--------------------------------------------------------------------------
*/
define('APP_NAME', 'Workday');
define('APP_TIMEZONE', 'America/Sao_Paulo');
define('APP_LOCALE', 'pt_BR');

$_detected_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($_detected_host === 'workday.aicode.dev.br') {
    // ── Produção ──────────────────────────────────────────────────────────
    define('APP_URL',   'https://workday.aicode.dev.br');
    define('APP_ENV',   'production');
    define('APP_DEBUG', false);
    define('SECRET_KEY', 'TROQUE_POR_UMA_CHAVE_ALEATORIA_E_SEGURA_PRODUCAO');
} else {
    // ── Desenvolvimento (localhost) ───────────────────────────────────────
    define('APP_URL',   'http://localhost/workday');
    define('APP_ENV',   'development');
    define('APP_DEBUG', true);
    define('SECRET_KEY', 'workday_secret_key_change_in_production_2024');
}

unset($_detected_host);

/*
|--------------------------------------------------------------------------
| Configurações de Segurança
|--------------------------------------------------------------------------
*/
define('JWT_EXPIRY',        86400); // 24 horas em segundos
define('SESSION_LIFETIME',  7200);  // 2 horas

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
