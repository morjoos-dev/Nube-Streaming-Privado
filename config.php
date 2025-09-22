<?php
declare(strict_types=1);
session_start();
if ('Europe/Madrid') { @date_default_timezone_set('Europe/Madrid'); }
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'mycloud';
const ALLOW_REGISTRATION = true;
define('BASE_URL', '');
define('FILES_DIR', __DIR__ . '/files');
define('VIDEO_DIR',  FILES_DIR . '/video');
