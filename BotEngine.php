<?php
require_once "BotEngine.php";

# Ruta del JSON
define("JSON", "respuestas.json");
# Define si se coloca modo debug
define("DEBUG", false);

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

class BotEngine
{
	private $httpService;
	
	function __construct($httpService)
	{
		$this->httpService = $httpService;
	}
	
	public function run(int $update_id) : int
	{
		$results = $this->get_pending_messages($update_id);
		foreach ($results as $result) {
			$update_id = $result->update_id;
			if(!isset($result->message)) {
				continue;
			}
		
			$chat_id = $result->message->chat->id;
			$message_id = $result->message->message_id;
			if (isset($result->message->entities)) { # Si se recibe un comando
				$this->process_command($result);
			} elseif (isset($result->message->new_chat_participant)) { # Si entra un nuevo miembro
				$this->send_new_member_reply($result);
			} else { # Los otros casos
				$this->send_respuestas_reply($result);
			}
			
			$this->send_files($result);
		}
		
		return $update_id;
	}
	
	private function get_pending_messages(int $update_id) : array
	{
		$str = $this->httpService->send("getUpdates", array("offset"=>($update_id + 1)));
		$json = json_decode($str);
		$result_prop = "result";
		if (!isset($json->$result_prop)) {
			print($str);
			return [];
		}
		if (DEBUG) if (count($json->result) > 0) print_r($json->result);
		
		return $json->result;
	}
	
	private function process_command($result) : void
	{	
		$message = $result->message;
		$length = $message->entities[0]->length;
		$command = substr($message->text, 0, $length);
		if ($message->entities[0]->type == "bot_command") {
			switch ($command) {
				case '/start':
				case '/help':
					break;
				case '/link':
			}
		}
	}
	
	private function send_new_member_reply(stdClass $result) : void
	{
		$str = file_get_contents(JSON);
		$obj = json_decode($str, true);
		$response = array_rand($obj['ingreso'], 1);
		$key = $obj['ingreso'][$response];
		
		$this->send_reply($result, $key, $response);
	}
	
	private function send_respuestas_reply(stdClass $result) : void
	{
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
			$this->send_reply($result, $key, $response);
		}
	}
	
	private function send_files(stdClass $result) : void
	{
		# Envía archivos al privado para que este develva el file_id que se
		# puede usar en el json
		if ($result->message->chat->type != 'private') {
			return;
		}
		$this->send_file($result, "sticker");
		$this->send_file($result, "document");
		$this->send_file($result, "audio");
		$this->send_file($result, "video");		
		if (isset($result->message->photo)) {
			$key = count($result->message->photo) - 1;
			$text = '"'.$result->message->photo[$key]->file_id.'":"photo"';
			$this->send_reply($result, "text", $text);
		}
	}
	
	private function send_file(stdClass $result, string $file_type) : void
	{
		if (isset($result->message->$file_type)) {
			$text = '"'.$result->message->$file_type->file_id.'":"'.$file_type.'"';
			$this->send_reply($result, "text", $text);
		}
	}
	
	private function send_reply(stdClass $result, string $key, string $value) : void
	{
		$params = array(
			"chat_id" => $result->message->chat->id,
			$key => $value,
			"reply_to_message_id" => $result->message->message_id
		);
		$response = $this->httpService->send(METHOD[$key], $params);
		if (DEBUG) print_r(json_decode($response));
	}
	
}
