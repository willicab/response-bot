<?php
class HttpService
{
    private $token;
    
    function __construct(string $token)
    {
        $this->token = $token;
    }
    
    public function send(string $method, array $params = array()) : string
    {
        $url   = "https://api.telegram.org/bot".$this->token."/$method";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        return curl_exec($ch);
    }    
}
