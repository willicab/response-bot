<?php
# Define si se coloca modo debug
define("DEBUG", true);
define("BOTNAME", 'crul_test_bot');
define("LA_TRANQUILIDAD", "CgADBAADSgIAApJSCFAQy7SJpIxWCgI");
define("LA_TRANQUILIDAD_TXT", "Lo que más se valora ...");
define("LA_TRANQUILIDAD_TIMEOUT", 1 * 60 * 60); # in seconds ([hours] * 60 * 60)

class BotTranquilidadEngine
{
    private $httpService;
    private $last_message_update_id;
	
    function __construct($httpService)
    {
        $this->httpService = $httpService;
    }
    
    public function run() : void
    {
        $results = $this->get_last_message();
        foreach ($results as $result) {
            $this->process_message($result);
        }
    }
    
    private function get_last_message() : array
    {
        $str = $this->httpService->send("getUpdates", array("offset"=> -1 ));
        $json = json_decode($str);
        $result_prop = "result";
        if (!isset($json->$result_prop)) {
            print($str);
            return [];
        }
        if (DEBUG) if (count($json->result) > 0) print_r($json->result);
        
        return $json->result;
    }
    
    private function process_message($result) : void
    {
		$time_since_last_message = $this->time_from($result->message->date);
		
		if ($this->last_message_update_id == $result->update_id) {
			if (DEBUG) print("Ignore message and sleep ".LA_TRANQUILIDAD_TIMEOUT."\n");
			sleep(LA_TRANQUILIDAD_TIMEOUT);
			return;
		}
		
		if (DEBUG) {
			print("\n");
			print("message->date       = ".$result->message->date."\n");
			print("time()              = ".time()."\n");
			print("time_since_last_msg = ".$time_since_last_message."\n");
			print("\n");
		}
		
		if ($time_since_last_message < LA_TRANQUILIDAD_TIMEOUT) {
			$sleep_time = (LA_TRANQUILIDAD_TIMEOUT - $time_since_last_message);
			if (DEBUG) print("Sleeping ".$sleep_time."\n");
			
			sleep($sleep_time);
		} else {
			
			$this->last_message_update_id = $result->update_id;
			$is_last_messsage_la_tranquilidad = ($result->message->from->username == BOTNAME and $result->message->text == LA_TRANQUILIDAD);
			if ($is_last_messsage_la_tranquilidad) {
				if (DEBUG) print("Sleeping LA_TRANQUILIDAD_TIMEOUT\n");
				
				sleep(LA_TRANQUILIDAD_TIMEOUT);
			} else {
				if (DEBUG) print("Sending LA_TRANQUILIDAD\n");
				$this->send_message($result);
			}
		}
    }
	
    private function send_message(stdClass $result) : void
    {
        $params = array(
            "chat_id" => $result->message->chat->id,
			"caption" => LA_TRANQUILIDAD_TXT,
            "document" => LA_TRANQUILIDAD
        );
        $response = $this->httpService->send("sendDocument", $params);
        if (DEBUG) print_r(json_decode($response));
    }
	
	private function time_from($reference) {
		return time() - $reference;
	}
    
}
