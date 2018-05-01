<?php

const APP_ROOT = __DIR__ . "/..";

require APP_ROOT.'/vendor/autoload.php';
require APP_ROOT . '/App/config.php';

$app = new \Slim\App(["settings" => [
    'addContentLengthHeader' => false,
    'displayErrorDetails' => true,
]]);

require APP_ROOT . "/app/routes.php";

$app->run();

