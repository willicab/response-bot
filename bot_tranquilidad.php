<?php
require_once "config.php";
require_once "BotTranquilidadEngine.php";
require_once "HttpService.php";

$http_service = new HttpService(TOKEN);
$bot_engine = new BotTranquilidadEngine($http_service);
while(1) { # Loop Infinito
    $bot_engine->run();
}
