<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Chapa\Chapa\Facades\Chapa as Chapa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class  PaymentController extends Controller
{
    /**
     * Initialize Rave payment process
     * @return void
     */
    protected $reference;

    public function __construct(){
        $this->reference = Chapa::generateReference();

    }
    public function initialize(Request $request,$chatId)
    {
        $amount=5;
        //This generates a payment reference
        $reference = $this->reference;
        /** */
// 
        // Enter the details of the payment
        $data = [
            
            'amount' => $amount,
            'email' => 'info@tiletsolution.com',//$request['email'],
            'tx_ref' => $reference,
            'currency' => "ETB",
           
            'callback_url' =>   "https://achawach.tiletsolution.com/public/api/callback/".$reference, 
        
            'return_url'=>'https://t.me/achawach_beta_bot/start',
            'first_name' => "premium",
            'last_name' => "user",
            "customization" => [
                "title" => 'Achawach beta',
                "description" => "Buy coins for chatting"
            ]
        ];
        

       $payment = Chapa::initializePayment($data);


        if ($payment['status'] !== 'success') {
            return response()->json(["message"=>$payment, "email"=>$request['email']]);
        }
       $payments=new Payment();
       $payments->user_id=$chatId;
       $payments->reference=$reference;
       $payments->status="false";
       $payments->amount=$amount;
       $payments->save();
      //return $data;
     return redirect($payment['data']['checkout_url']);

        
    }

    /**
     * Obtain Rave callback information
     * @return void
     */
    public function callback($reference)
    {
        $coin=500;
        $payment=Payment::where('reference',$reference)->first();
        $user=$payment->user()->first();
       $data = Chapa::verifyTransaction($reference);

       if ($data['status'] ==  'success') { 
        
       
       $payment->response=json_encode($data);
        $payment->status="true";
        $payment->save();
        
        $user->paid="true";
        $user->coin=$user->coin+$coin;
        $user->save();
            
       $msg=[
            'chat_id'=>$user->id,
            "text"=>"Congratulations! 🎉 You've paid 5 birr and successfully registered. Check your balance at /balance. Welcome aboard! 🌟"
        ];
$telegram =new TelegramService;
return $telegram->sendMessage($msg);
        

   }
    else{
  $msg=[
        'chat_id'=>$user->id,
        "text"=>"Sorry! 🙁 You've failed to pay 5 birr. Please try again.
        
        /balance to check your balance. "
    ];
$telegram =new TelegramService;
return $telegram->sendMessage($msg);
          
     }


    }
}