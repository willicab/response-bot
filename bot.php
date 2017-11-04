<?php
# Token del bot
if (!defined("TOKEN")) define("TOKEN", "000000000000000000000000000000000000000000");
# Ruta del JSON
if (!defined("JSON")) define("JSON", "respuestas.json");
# Define si se coloca modo debug
if (!defined("DEBUG")) define("DEBUG", false);

# Definir métodos dependiendo el tipo de mensaje
if (!defined("METHOD")) define("METHOD", array(
    "text" => "sendMessage",
    "photo" => "sendPhoto",
    "sticker" => "sendSticker",
    "document" => "sendDocument",
    "voice" => "sendVoice",
    "audio" => "sendAudio",
    "video" => "sendVideo"
));

if (!function_exists('send')) {
	function send($method, $params = array()){
		global $curl_mock;
		if(isset($curl_mock)) {
			return $curl_mock->send($method, $params);
		}
		
		$url   = "https://api.telegram.org/bot".TOKEN."/$method";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		return curl_exec($ch);
	}
	
	function send_new_member_reply($result) {
		$str = file_get_contents(JSON);
		$obj = json_decode($str, true);
		$response = array_rand($obj['ingreso'], 1);
		$key = $obj['ingreso'][$response];
		
		return send_reply($result, $key, $response);
	}
	
	function send_respuestas_reply($result) {
		$text = isset($result->message->text) ? $result->message->text : "";
		$str = file_get_contents(JSON);
		$obj = json_decode($str, true);
		foreach ($obj['respuestas'] as $k => $v) {
			$re = '/'.$k.'/i';
			preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);
			if (count($matches) == 0) {
				continue;
			}
			
			$response = array_rand($obj['respuestas'][$k], 1);
			$key = $obj['respuestas'][$k][$response];
			send_reply($result, $key, $response);
		}
	}
	
	function send_files($result) {
		# Envía archivos al privado para que este develva el file_id que se
		# puede usar en el json
		if ($result->message->chat->type != 'private') {
			return;
		}
		send_file($result, "sticker");
		send_file($result, "document");
		send_file($result, "audio");
		send_file($result, "video");		
		if (isset($result->message->photo)) {
			$key = count($result->message->photo) - 1;
			$text = '"'.$result->message->photo[$key]->file_id.'":"photo"';
			send_reply($result, "text", $text);
		}
	}
	
	function send_file($result, $file_type) {
		if (isset($result->message->$file_type)) {
			$text = '"'.$result->message->$file_type->file_id.'":"'.$file_type.'"';
			send_reply($result, "text", $text);
		}
	}
	
	function send_reply($result, $key, $value) {
		$params = array(
			"chat_id" => $result->message->chat->id,
			$key => $value,
			"reply_to_message_id" => $result->message->message_id
		);
		$response = send(METHOD[$key], $params);
		if (DEBUG) print_r(json_decode($response));
	}
}

$update_id = 0;
while(1) { # Loop Infinito
    # Obtiene todos los mensajes que se han enviado al bot
    $str = send("getUpdates", array("offset"=>($update_id + 1)));
    $json = json_decode($str);
    if (DEBUG) if (count($json->result) > 0) print_r($json->result);
    foreach ($json->result as $result) {
        $update_id = $result->update_id;
        if(isset($result->message)) {
            $chat_id = $result->message->chat->id;
            $message_id = $result->message->message_id;
            if (isset($result->message->entities)) { # Si se recibe un comando
                $username = $result->message->from->username;
                $message = $result->message->text;
            	$length = $result->message->entities[0]->length;
            	$command = substr($message, 0, $length);
                if ($result->message->entities[0]->type == "bot_command") {
                	switch ($command) {
                        case '/start':
                    	case '/help':
                            break;
                        case '/link':
                    }
                }
            } elseif (isset($result->message->new_chat_participant)) { # Si entra un nuevo miembro
				send_new_member_reply($result);
            } else { # Los otros casos
				send_respuestas_reply($result);
            }
			
			send_files($result);
        }
    }
	
	global $curl_mock;
	if(isset($curl_mock)) {
		break;
	}
	
    sleep(1);
}
