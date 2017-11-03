<?php
# Token del bot
define("TOKEN", "000000000000000000000000000000000000000000");
# Ruta del JSON
define("JSON", "respuestas.json");
# Define si se coloca modo debug
define("DEBUG", true);

# Definir métodos dependiendo el tipo de mensaje
define("METHOD", array(
    "text" => "sendMessage",
    "photo" => "sendPhoto",
    "sticker" => "sendSticker",
    "document" => "sendDocument",
    "voice" => "sendVoice",
    "audio" => "sendAudio",
    "video" => "sendVideo"
));
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
                $str = file_get_contents(JSON);
                $obj = json_decode($str, true);
                $response = array_rand($obj['ingreso'], 1);
                $key = $obj['ingreso'][$response];
                $params = array(
                    "chat_id" => $chat_id,
                    $key => $response,
                    "reply_to_message_id" => $message_id
                );
                $result = send(METHOD[$key], $params);
                if (DEBUG) print_r(json_decode($result));
            } else { # Los otros casos
                $text = isset($result->message->text) ? $result->message->text : "";
                $str = file_get_contents(JSON);
                $obj = json_decode($str, true);
                foreach ($obj['respuestas'] as $k => $v) {
                    $re = '/'.$k.'/i';
                    preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);
                    if (count($matches) > 0) {
                        $response = array_rand($obj['respuestas'][$k], 1);
                        $key = $obj['respuestas'][$k][$response];
                        $params = array(
                            "chat_id" => $chat_id,
                            $key => $response,
                            "reply_to_message_id" => $message_id
                        );
                        $result = send(METHOD[$key], $params);
                        if (DEBUG) print_r(json_decode($result));
                    }
                }
            }
            # Envía archivos al privado para que este develva el file_id que se
            # puede usar en el json
            if ($result->message->chat->type == 'private') {
                if (isset($result->message->sticker)) {
                    $params = array(
                        "chat_id" => $chat_id,
                        "text" => '"'.$result->message->sticker->file_id.'":"sticker"',
                        "reply_to_message_id" => $message_id
                    );
                    $result = send("sendMessage", $params);
                    if (DEBUG) print_r(json_decode($result));
                }
                if (isset($result->message->document)) {
                    $params = array(
                        "chat_id" => $chat_id,
                        "text" => '"'.$result->message->document->file_id.'":"document"',
                        "reply_to_message_id" => $message_id
                    );
                    $result = send("sendMessage", $params);
                    if (DEBUG) print_r(json_decode($result));
                }
                if (isset($result->message->audio)) {
                    $params = array(
                        "chat_id" => $chat_id,
                        "text" => '"'.$result->message->audio->file_id.'":"audio"',
                        "reply_to_message_id" => $message_id
                    );
                    $result = send("sendMessage", $params);
                    if (DEBUG) print_r(json_decode($result));
                }
                if (isset($result->message->video)) {
                    $params = array(
                        "chat_id" => $chat_id,
                        "text" => '"'.$result->message->video->file_id.'":"video"',
                        "reply_to_message_id" => $message_id
                    );
                    $result = send("sendMessage", $params);
                    if (DEBUG) print_r(json_decode($result));
                }
                if (isset($result->message->photo)) {
                    $key = count($result->message->photo) - 1;
                    $params = array(
                        "chat_id" => $chat_id,
                        "text" => '"'.$result->message->photo[$key]->file_id.'":"photo"',
                        "reply_to_message_id" => $message_id
                    );
                    $result = send("sendMessage", $params);
                    if (DEBUG) print_r(json_decode($result));
                }
            }
        }
    }
    sleep(1);
}

function send($method, $params = array()){
    $url   = "https://api.telegram.org/bot".TOKEN."/$method";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    return curl_exec($ch);
}
