<?php

namespace App\Services;

use App\Models\User;
use App\Models\Chat;
use App\Models\Payment;

class AdminService
{
   private $chatId=594090978;
   private $telegram;

   function __construct(){

   
    $this->telegram=new TelegramService();
   }
      public function updateFreeStatusForAllUsers($value)
    {
        try {
            User::query()->update(['free' => $value]);
        } catch (exception $e) {
            return 0;
        }
    }
    public function statistics()
    {
        $totalUsers = User::count();
        $totalBonus = User::sum('bonus');
        $totalReferrals = User::whereNotNull('referred_by')->count();
        $totalOnChat = Chat::count();
        $totalPendingRequests = Chat::where('status', 'onrequest')->count();
        $totalPayments = Payment::sum('amount');

        return [
            'totalUsers' => $totalUsers,
            'totalBonus' => $totalBonus,
            'totalReferrals' => $totalReferrals,
            'totalOnChat' => $totalOnChat,
            'totalPendingRequests' => $totalPendingRequests,
            'totalPayments' => $totalPayments,
        ];
    }
    public function sendStat($chatId)
    {
        if($this->chatId!=$chatId) return;
        // Calculate the statistics
        $statistics = $this->statistics();

        // Format the statistics as a plain text message
        $plainMessage =  "📊 Statistics 📊
        
👥 Total Users: {$statistics['totalUsers']} 👥
💰 Total Bonus: {$statistics['totalBonus']} 💰
👥 Total Referrals: {$statistics['totalReferrals']} 👥
💬 Total On Chat: {$statistics['totalOnChat']} 💬
⏳ Total Pending Requests: {$statistics['totalPendingRequests']} ⏳
💳 Total Payments: {$statistics['totalPayments']} 💳
                ";
        
      
        $options=[
            'chat_id' => $this->chatId  ,
            'text' => $plainMessage,
        ];

       return $this->telegram->sendMessage($options);

    }
public function getAllUserChatIds(){
    $users = User::all();
    $userChatIds = [];

    foreach ($users as $user) {
        $userChatIds[] = $user->id;
    }

    return $userChatIds;
}
   public function sendAds($message){
    if($this->chatId!=  594090978) return;
  // Get the list of all users' chat IDs
  $userChatIds = $this->getAllUserChatIds();

  // Loop through all users' chat IDs and send the message to each user
  foreach ($userChatIds as $chatId) {
      $finalMessage = [
          'chat_id' => $chatId,
          'text' =>  $message['text'] ?? null,
          'animation' => null, // $message['animation'] ??
          'photo' => $message['photo'] ?? null,
          'caption' => $message['caption'] ?? null,
          'sticker' => null, //$message['sticker'] ?? 
         
      ];

      // Send the appropriate type of message based on the content of the finalMessage array
      if (!empty($finalMessage['animation'])) {
          $this->telegram->sendAnimation($finalMessage);
      } elseif (!empty($finalMessage['photo'])) {
          $this->telegram->sendPhoto($finalMessage);
      } elseif (!empty($finalMessage['sticker'])) {
          $this->telegram->sendSticker($finalMessage);
      } else {
          $this->telegram->sendMessage($finalMessage);
      }
  }

    }
    public function processIncomingMessage($message,$chatId)
    { if($this->chatId!=  594090978) return;
        // Check if the received message is "/admin"
        if ($message['text'] === '/admin') {
            // Prepare the inline keyboard with options "Statistics" and "Send Message"
          $inlineKeyboard = [
                [
                    ['text' => 'Statistics', 'callback_data' => 'stats'],
                    
                ],
                [
                    ['text' => 'Send Message', 'callback_data' => 'send_message']
                    
                ]
                ,
                [
                    ['text' => 'Free', 'callback_data' => 'free_admin']
                    
                ]
                ,
                [
                    ['text' => 'Paid', 'callback_data' => 'paid_admin']
                    
                ]
            ];

            // Send the keyboard to the user
            $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'Please select an option:',
                'reply_markup' => ['inline_keyboard' => $inlineKeyboard],
            ]);
        } 
    }

    public function processCallbackQuery($callbackQuery,$chatId)
    {
        if($this->chatId!=  $chatId) return;
        // Extract the callback data from the callback query
        $callbackData = $callbackQuery;

        // Check the callback data to determine the action
        if ($callbackData === 'stats') {
            // User selected "Statistics," send the statistics to the user
            $statisticsMessage = $this->sendStat($chatId); // Implement this function to retrieve the statistics message
           /* $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => $statisticsMessage,
            ]);
            */
        } else if ($callbackData === 'send_message') {
            // User selected "Send Message," prompt the user to enter a message
            $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'Please enter the message you want to send:',
            ]);
        } else if ($callbackData === 'free_admin'){
              $this-> updateFreeStatusForAllUsers("true");
               $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'user status updated to free:',
            ]);
        }//free_admin 
        else if($callbackData === 'paid_admin'){
            $this-> updateFreeStatusForAllUsers("false");
               $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'user status updated to paid:',
            ]);
        }
    }

    public function processUserMessage($userMessage,$chatId)
    {  if($this->chatId!=  $chatId) return;
        // Check if the user canceled the operation (e.g., sent "/cancel" or an empty message)
        if ($userMessage === '/cancel' || empty($userMessage)) {
            // User canceled the operation
            $this->telegram->sendMessage([
                'chat_id' => $this->chatId,
                'text' => 'Message sending canceled.',
            ]);
        } else {
            // User provided a message, send the message to all users
            $this->sendAds($this->paraseMessage($userMessage,$chatId),$chatId);
        }
    }
    public function paraseMessage($request,$chatId){
     
        $callback_query=isset($request['callback_query'])? $request['callback_query']:null;
        if($callback_query) $message=$callback_query['message'];
        else    $message = $request['message'];
        $result = [
            'text' => $message['text'] ?? null,
            'animation' => isset($message['animation']) ? $message['animation']['file_id'] : null,
            'photo' => isset($message['photo']) ? $message['photo'][0]['file_id'] : null,
            'caption' => $message['caption'] ?? null,
            'sticker' => isset($message['sticker']) ? $message['sticker']['file_id'] : null,
        ];
   
}
}


