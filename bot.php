<?php
require_once "BotEngine.php";

$bot_engine = new BotEngine();
$update_id = 0;
while(1) { # Loop Infinito
	$update_id = $bot_engine->run($update_id);
	
	global $curl_mock;
	if(isset($curl_mock)) {
		break;
	}
	
    sleep(1);
}
