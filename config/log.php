<?php

require __DIR__ . '/../vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Level;

$logger = new Logger('sicoob_logger');

$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Level::Debug));

$logger->pushHandler(new NativeMailerHandler('med24hora@gmail.com', 'Critical Error', 'gustavojsc9@example.com', Level::Critical));

return $logger;
