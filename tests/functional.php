<?php
use PHPUnit\Framework\TestCase;

abstract class CurlMock {
	public abstract function send(string $method, array $params): string;
}

class FunctionalTestCase extends TestCase 
{
	private $chat_id = 7;
	private $message_id = 13;
    protected $curl_mock;
	
    protected function setUp() : void
    {
        $this->curl_mock = $this->createMock(CurlMock::class);
    }
	
	public function functionalTestsProvider() : array
	{
		$test_configurations = json_decode(file_get_contents("tests/functional.json"));
		$test_configurations = array_merge($test_configurations, $test_configurations);
		$test_configurations = array_merge($test_configurations, $test_configurations);
		
		return $test_configurations;
	}
	
    /**
     * @dataProvider functionalTestsProvider
     */
    public function testFunctional(stdClass $definition, array $expected_send_calls): void
    {
		global $curl_mock;
		$curl_mock = $this->curl_mock;
		
		$this->setFakeCurlResponse($definition);
		$this->expectSendCallCount(sizeof($expected_send_calls) + 1);
		$this->expectGetUpdatesCall();
		$this->expectSendCalls($expected_send_calls);
		
		include "bot.php";
	}
	
	private function setFakeCurlResponse(stdClass $definition) : void
	{
		$message = array();
		$message["message_id"] = $this->message_id;
		$message["chat"] = [ "id" => $this->chat_id, "type" => "private" ];
		$message["from"] = [ "username" => "Moderdonio" ];
		if (property_exists($definition, "new_chat_participant")) {
			$message["new_chat_participant"] = $definition->new_chat_participant;
		} else {
			$message["new_chat_participant"] = null;
		}
		if (property_exists($definition, "text")) {
			$message["text"] = $definition->text;
			if ($definition->text[0] == "/") {
				$message["entities"] = [ [ "length" => strpos($definition->text, " "), "type" => "bot_command" ] ];
			}
		}
		if (property_exists($definition, "sticker")) {
			$message["sticker"] = [ "file_id" => $definition->sticker->file_id ];
		}
		if (property_exists($definition, "document")) {
			$message["document"] = [ "file_id" => $definition->document->file_id ];
		}
		if (property_exists($definition, "audio")) {
			$message["audio"] = [ "file_id" => $definition->audio->file_id ];
		}
		if (property_exists($definition, "video")) {
			$message["video"] = [ "file_id" => $definition->video->file_id ];
		}
		if (property_exists($definition, "photo")) {
			$message["photo"] = [ [ "file_id" => $definition->photo[0]->file_id ] ];
		}
		
		$response = [ "result" => [ [ "update_id" => 1, "message" => $message ] ] ];
		
		$json = json_encode($response);
		
		$this->curl_mock
			->method("send")
			->willReturn($json);
	}
	
	private function expectSendCallCount(int $times) : void
	{
		$this->curl_mock
			->expects($this->exactly($times))
			->method("send");
	}
	
	private function expectGetUpdatesCall() : void
	{
		$this->expectSendCall(0, ["getUpdates"], [ [ "offset" => 1 ] ]);
	}
	
	private function expectSendCalls(array $expected_send_calls) : void
	{
		foreach($expected_send_calls as $index => $expected_send_call) {
			$expected_methods = array_map(array($this, "getExpectedMethod"), $expected_send_call);
			$expected_params = array_map(array($this, "getExpectedParamArray"), $expected_send_call);
			
			$this->expectSendCall($index + 1, $expected_methods, $expected_params);
		}
	}
	
	private function expectSendCall(int $at_index, array $expected_methods, array $expected_params) : void
	{
		$this->curl_mock
			->expects($this->at($at_index))
			->method("send")
			->with(
				call_user_func_array(array($this, "logicalOr"), $expected_methods),
				call_user_func_array(array($this, "logicalOr"), $expected_params)
			);
	}
	
	private function getExpectedMethod(array $expected_send_call) : string
	{
		return $expected_send_call[0];
	}
	
	private function getExpectedParamArray(array $expected_send_call) : array
	{
		$params = [
			"chat_id" => $this->chat_id,
			"reply_to_message_id" => $this->message_id
		];
		$params[$expected_send_call[1]] = $expected_send_call[2];
		
		return $params;
	}
}
