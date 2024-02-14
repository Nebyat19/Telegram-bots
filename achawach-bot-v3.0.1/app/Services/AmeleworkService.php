<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
class AmeleworkService{

    private $telegram;
    
  function __construct()
  {
    $this->telegram=new TelegramService();
  }

    function chat($chatid,$msg){
      

        try {
            // JSON data to be sent in the request body
            $jsonData = [
                'msg' => $msg,
            ];
        json_encode($jsonData);
            // Send the POST request with JSON data

         
            
            $this->telegram->sendMessage([
                "chat_id"=>$chatid,
                'text'=>"Just a moment... 🕐"
            ]);
            $messageid=$this->telegram->msgId;
            
            
            
            $response = Http::post('https://api.tiletsolution.com/chat.php', $jsonData);
        
            if ($response->successful()) {
               
            
            $waitingMessages = explode(" ",$response->body());
            $text="";
            foreach($waitingMessages as $msg){
                $text.=" ".$msg;
                
                $this->telegram->editMessage(
                    [
                        'chat_id'=>$chatid,
                        'message_id'=>$messageid,
                        'text'=>$text
                    ]
                    );
                    
            }
        }
        
        
        } catch (Exception $e) {
            // Exception occurred, handle it
           
        }
        

    }

}