<?php
# Token del bot
define("TOKEN", "000000000000000000000000000000000000000000");

require_once "BotEngine.php";
require_once "HttpService.php";

$http_service = new HttpService(TOKEN);
$bot_engine = new BotEngine($http_service);
$update_id = 0;
while(1) { # Loop Infinito
    $update_id = $bot_engine->run($update_id);
    sleep(1);
}
