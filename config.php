<?php

define('APP_NAME', getenv('APP_NAME') ?: 'Global Exchange Portal');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_NAME', getenv('DB_NAME') ?: 'exchange_portal');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');



define('BASE_URL', getenv('APP_URL') ? '/exchange_portal' : '/exchange_portal');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_MAX_MB', (int) (getenv('UPLOAD_MAX_MB') ?: 5));
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

define('ITEMS_PER_PAGE', 10);

define('ALLOW_WEB_INSTALL', false);

define('APP_ENV', getenv('APP_ENV') ?: 'production');


