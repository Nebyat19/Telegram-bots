<?php



namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;

class BotPaymentController extends Controller


{

      private  $chatId;
     private  $telegram;  
     private $providerToken= "5788067111:LIVE:laeBqlRyHkEa3dxdVBIg";
     //"6141645565:TEST:cO0vpqkmSFSXwzkEAVas";//
       function __construct($chatId, $telegram = new TelegramService  )
   {
    $this->chatId=$chatId;
    $this->telegram=$telegram;
   }
public function answerPreCheckoutQuery($message){
if($message['pre_checkout_query']['invoice_payload']=="Chat-Points-Package") $data['ok']=true; else $data['ok']=false;  
    $options = [
        'pre_checkout_query_id' => $message['pre_checkout_query']['id'],
        'ok' => true, //$data['ok'],
        'error_message' => 'Sorry, something went wrong. Please try again later.',
    ];
    
   return  $this->telegram->answerPreCheckoutQuery($options);
}

public function successful_payment($user){
  $coin=500;
        $user->paid="true";
        $user->coin=$user->coin+$coin;
        $user->save();
            
       $msg=[
            'chat_id'=>$user->id,
            "text"=>"Congratulations! 🎉 You've paid 5 birr and successfully registered. Check your balance at /balance. Welcome aboard! 🌟"
        ];

return  $this->telegram->sendMessage($msg);
        
}
  public   function sendInvoice($hasShipping = false)
{
     $paymentLink=route('pay', $this->chatId) ;
   
    $chatId =$this->chatId; // Here's the link 👉 {$paymentLink}
    $title = "🔒 Account Registration";
    $description = "🛍️ Upgrade Your Experience!

To send requests and withdraw, you need to register and buy at least 500 points for just 5 birr!

 💳 If you encounter any issues, simply type /pay and use the link. 🚀💫";
    $payload = "Chat-Points-Package";
    $currency = "ETB";
    $price = 5;
    $prices = [['label' => 'Test', 'amount' => $price * 100]];


    if ($hasShipping) {
        $options['need_shipping_address'] = true;
    }

    $data = [
        'chat_id' => $chatId,
        'title' => $title,
        'description' => $description,
        'payload' => $payload,
        'provider_token' => $this->providerToken,
        'currency' => $currency,
        'prices' => $prices,
    ];


    return $this->telegram->sendInvoice($data);
    // Handle the response if needed
}
}