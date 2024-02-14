<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use PDO;

class TelegramService
{
    protected $botToken;
    public $msgId=0;
    public function __construct()
    {
        $this->botToken = env('BOT_TOKEN');
    }

    public function sendMessage($options)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        $data = [
            'chat_id' => $options['chat_id'],
            'text' => $options['text'],
        ];

        if (isset($options['reply_markup'])) {
            $data['reply_markup'] = $options['reply_markup'];
        }


        return $this->sendRequest($url, $data);
    }

    public function sendPhoto($options)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendPhoto";
        $data = [
            'chat_id' => $options['chat_id'],
            'photo' => $options['photo'],
        ];

        if (isset($options['caption'])) {
            $data['caption'] = $options['caption'];
        }

        if (isset($options['reply_markup'])) {
            $data['reply_markup'] = $options['reply_markup'];
        }
        return $this->sendRequest($url, $data);
    }

    public function sendSticker($options)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendSticker";
        $data = [
            'chat_id' => $options['chat_id'],
            'sticker' => $options['sticker'],
        ];

        if (isset($options['reply_markup'])) {
            $data['reply_markup'] = $options['reply_markup'];
        }

        return $this->sendRequest($url, $data);
    }
    public function sendAnimation($options)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendAnimation";
        $data = [
            'chat_id' => $options['chat_id'],
            'animation' => $options['animation'],
        ];
    
        if (isset($options['caption'])) {
            $data['caption'] = $options['caption'];
        }
    
        if (isset($options['reply_markup'])) {
            $data['reply_markup'] = $options['reply_markup'];
        }
    
        return $this->sendRequest($url, $data);
    }
    public function sendInvoice($options){
        $url = "https://api.telegram.org/bot{$this->botToken}/sendInvoice";
       
        return $this->sendRequest($url, $options);
    }
    public function sendChatAction ($options){
    
    $url = "https://api.telegram.org/bot{$this->botToken}/sendChatAction";
   $data= ([
    'chat_id' => $options['chat_id'],
    'action' => $options['action'],
   ]);
}
    private function sendRequest($url, $data)
    {
       //return $data;
        $response = Http::post($url, $data);
        $data= $response->body();
       
       $data=json_decode($data,true);
      // return $data;
       if($data){
        
       if(isset($data['result']['message_id'])){
        $this->msgId=$data['result']['message_id'];
       }
    }
     
    return $this->msgId;

    }
    public function deleteMessage($options){
        $url = "https://api.telegram.org/bot{$this->botToken}/deleteMessage";
        $data =  [
            'chat_id' => $options['chat_id'],
            'message_id' => $options['msgId'],
        ];
        return $this->sendRequest($url, $data);
    }
    public function editMessageCaption($options){
        $url = "https://api.telegram.org/bot{$this->botToken}/editMessageCaption";
    
    $data = [
        'chat_id' => $options['chat_id'],
        'message_id' => $options['message_id'],
        'caption' => $options['caption'],
       
    ];
    if (isset($options['reply_markup'])) {
        $data['reply_markup'] = $options['reply_markup'];
    }
    return $this->sendRequest($url, $data);
    }
    public function answerPreCheckoutQuery($options){
        $url = "https://api.telegram.org/bot{$this->botToken}/answerPreCheckoutQuery";
        if($options['pre_checkout_query']['invoice_payload']=="Chat-Points-Package") $data['ok']=true; else $data['ok']=false;  
        $data = [
            'pre_checkout_query_id' => $options['pre_checkout_query']['id'],
            'ok' => true, //$data['ok'],
            'error_message' => 'Sorry, something went wrong. Please try again later.',
        ];
        
        return $this->sendRequest($url, $data);
    }
    public function editMessage($options)
{
   
    $url = "https://api.telegram.org/bot{$this->botToken}/editMessageText";
    
    $data = [
        'chat_id' => $options['chat_id'],
        'message_id' => $options['message_id'],
        'text' => $options['text'],
       
    ];
    if (isset($options['reply_markup'])) {
        $data['reply_markup'] = $options['reply_markup'];
    }
    return $this->sendRequest($url, $data);
}
public function editInlineKeyboard($options)
{
    
    $url = "https://api.telegram.org/bot{$this->botToken}/editMessageReplyMarkup";
    
    $data = [
        'chat_id' => $options['chat_id'],
        'message_id' => $options['message_id'],
      
    ];
    if (isset($options['reply_markup'])) {
        $data['reply_markup'] = $options['reply_markup'];
    }
    return $this->sendRequest($url, $data);

}

}