<?php

namespace App\Http\Controllers;
use Illuminate\Support\Str;
use App\Models\Chat;
use App\Models\Filter;
use App\Models\History;
use App\Models\Rate;
use App\Services\TelegramService;
use App\Services\AmeleworkService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Services\AdminService;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class WebHookController extends Controller
{
    private $telegram;
    private $chatId;
    private $message;
    private $firstName;
    private  $lastName;
    private $username='user';
    private $user;
    private  $query=null;
    private $isquery=false;
    private $message_id;
   private $admin;
   private $amelework;
    private $filterKeyboard=[
        [
            ['text' => 'Age 🎂', 'callback_data' => 'filter_age'],
        ],
        [
            ['text' => 'Location 🌍', 'callback_data' => 'filter_location'],
        ],
        [
            ['text' => 'Gender ⚧️', 'callback_data' => 'filter_gender'],
        ],
        [
            ['text' => 'Back 🔙', 'callback_data' => 'back'],
        ],
    ];

   private $profileSetting= [
        'inline_keyboard' => [
            [
                ['text' => ' Photo 📷', 'callback_data' => 'change_photo'],
                ['text' => ' Age 🎂', 'callback_data' => 'change_age'],
                ['text' => ' Gender ⚧️', 'callback_data' => 'change_gender'],
            ],
            [
                ['text' => ' Location 🌍', 'callback_data' => 'change_location'],
                ['text' => ' Name 📝', 'callback_data' => 'change_name'],
                ['text' => 'Back 🔙', 'callback_data' => 'back'],
            ],
        ]
        ];
        function __construct(TelegramService $telegramService){
            $this->telegram=$telegramService;
            $this->admin= new AdminService();
               $this->amelework=new AmeleworkService();
        }
    public function iniate(Request $request){
      

  if(isset($request['callback_query'])) $callback_query=$request['callback_query']; 
   else $callback_query=null;
        
        if($callback_query){
            $this->isquery=true;
            $message=$callback_query['message'];
            $this->query=$callback_query['data'];
        }
       else $message=$request['message'];


      


        $chatId =  $message['chat']['id']??0;
        
        $this->firstName =$message['chat']['first_name'];
        $this->lastName =isset($message['chat']['last_name'])?$message['chat']['last_name']:"";
     
        $this->chatId=(string)$chatId;
        $user=User::find($this->chatId);
         
        if(!$user){

            try{
                $user= new User();
                $user->id=$this->chatId;
                $user->username= $this->generateRandomUsername();
                $user->name=$this->firstName.' '.$this->lastName;
                
                $user->save();

            }catch(Exception $e){
               return $this->telegram->sendMessage([
                   'chat_id'=>$this->chatId,
                   'text'=>"Something went wrong! Please try again later. pleade report this to
                    @ask_tiletsolution_bot"
               ]);
            }
          
        }
        $this->user=User::find($this->chatId);
        $this->username = $this->user->username;
      
        $this->message=$this->paraseMessage($request);
       
        if(($this->message['text']==null||substr($this->message['text'],0,1)=="/")&&(!$this->message['photo']||$this->user->status!='change_photo')) {
            $this->user->status='1';
            $this->user->save();
           }else if($this->user->status=='change_photo'&&!$this->message['photo']){
            $this->user->status='1';
            $this->user->save();
           }
        $this->message_id=$message['message_id'];
   
  
 if(isset($request['message']['successful_payment'])){
    $botPayment=new BotPaymentController($this->chatId);
         $botPayment-> successful_payment($this->user);
    }   
    }    
    public function accept(Request $request){
        if(isset($request['pre_checkout_query'])){
            
            return $this->telegram->answerPreCheckoutQuery($request);

        } else if (isset($request['edited_message']) ||
        isset($request['pre_checkout_query']) ||
        isset($request['my_chat_member']) ||
        isset($request['chat_member']) ||
        isset($request['chosen_inline_result']) ||
        isset($request['inline_query'])
    ) {
        return null;
    } 

  $this->iniate($request);

  if($this->message['photo']!=null&&$this->user->status=='change_photo'){

    $this->user->image = $this->message['photo'];
    $this->user->status='1';
    $this->user->save();
   // return "Age saved successfully! ✅";
    return $this->setting();
   }else if($this->user->satus=='change_photo'){
    $this->user->status='1';
    $this->user->save();
   }

  if(isset($request['message']['location'])){
    $this->user->location="🌍"; 
    $this->user->locationData= json_encode($request['message']['location']);
    $this->user->save();

    $this->telegram->sendMessage(
        [
            "chat_id"=>$this->chatId,
            "text"=>"Location set 🌍",
            'reply_markup'=>['remove_keyboard' => true]
        ]
                );
 return $this->setting();
    }

   
  if($this->isquery) {

    if(substr($this->query,0,4)=='chat') {
       
        $data=explode('_',$this->query);
        $key=$data[1];
        $id=$data[2];
  return    $this->createRate($key, $id);
        
    }
    return $this->queryHandler();
}

 if($this->message['text']!==null){

    if($this->message['text']=='/ameleworq') {
      
       $this->user->status='startamelework';
       $this->user->save();
       return   $this->telegram->sendMessage([
        "chat_id"=>$this->chatId,
        "text"=>"Hello! How can I assist you today? 🤖"
    ]);
    }else if($this->message['text']=='/stopameleworq') {
      
        $this->user->status='1';
        $this->user->save();
        return $this->telegram->sendMessage([
            "chat_id"=>$this->chatId,
            "text"=>"Goodbye! If you have any more questions in the future, don't hesitate to ask. Have a great day! 😊👋"
        ]);
    
        
    }else       if($this->user->status=='startamelework') {
    
    return $this->amelework->chat($this->chatId,$this->message['text']);
}
    else
   
    if($this->message['text']=='/bonus'){
        return $this->addBonusPoints();
    }else
    if($this->message['text']=='/payme') {
       // $this-> sendMessageToAllUsers();
         $botPayment=new BotPaymentController($this->chatId);
         return $botPayment->sendInvoice();
    }else if($this->message['text']=='Back 🔙') {
        $this->telegram->sendMessage(
[
    "chat_id"=>$this->chatId,
    "text"=>"Location not set ❌",
    'reply_markup'=>['remove_keyboard' => true]
]
        );
        return $this->setting();
    }
    $msgStart=substr($this->message['text'],0,1);
    if($msgStart=='/') return $this->commandHandler($this->message);

    if($this->user->status=='change_age'){
        $age = $request['message']['text'];

        if (is_numeric($age) && $age >= 17 && $age <= 55) {
            $this->user->age = $age;
            $this->user->status='1';
            $this->user->save();
           // return "Age saved successfully! ✅";
            return $this->setting();
        } else {
            $this->telegram->sendMessage([
                'chat_id'=>$this->chatId,
                'text'=>"Invalid age! Please enter a valid age."
            ]) ;
        }
     
   
    } else if($this->user->status=='change_name'){
        $this->user->name = $this->message['text'];
        $this->user->status='1';
        $this->user->save();
       // return "Age saved successfully! ✅";
        return $this->setting();

    }//change_name 
 } 
       
       
   if($this->isUserOnChat() !==null){
    $message=new Message();
   try{ if(isset($this->message['text'])) $message->text=$this->message['text'];
else if(isset($this->message['photo'])) $message->text=$this->message['photo'];
else $message->text="sticker||emojie";
  //  $message->text=$this->message['text'];
    $message->chat_id=$this->isUserOnChat();
    $this->user->message()->save($message);
   }catch(Exception $e){
     
    }finally{
        return $this->sendMessage($this->message);
    }
  //  return $this->sendMessage($this->message);
   }

    }

    /****************queary ************************/
    public function queryHandler(){
      if(substr($this->query,0,5)=='guess') { 
       
        $data=explode('_',$this->query);
        $key=$data[1];
        $id=$data[2];
        $user=User::find($id);
        $user->gender=$user->gender.",".$key??$key;
        $user->save();
    return    $this->telegram->editMessage([
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
            'message_id'=>$this->message_id,
            'text'=>"Thank you for your feedback! 🙏",
            'replay_markup'=>[
                'inline_keyboard'=>[
                    [
                     ['text'=>"discover 🔍",'callback_data'=>'discover']
                    ]
                ]
            ]

        ]);
       

      }  else
if($this->query=='return'){
    $this->user->status='1';
    $this->user->save();
    $this->telegram->deleteMessage([
        'chat_id'=>$this->chatId,
        'msgId'=>$this->message_id

    ]);
 return   $this->setting();
} //change_name
else if($this->query=="withdraw"){
    $this->withdraw();
}
else if($this->query=="change_photo"){ 
    $this->user->status='change_photo';
    $this->user->save();
   
               $this->telegram->deleteMessage([
                   'chat_id'=>$this->chatId,
                   'msgId'=>$this->message_id
   
               ]);
               $options=[
                   'chat_id'=>$this->chatId,
                   'text' => "Upload your profile picture? 📷",
                   'message_id'=>$this->message_id,
                   "reply_markup" => [
                       'inline_keyboard' => [
                           [
                               ['text' => 'Return 🔙', 'callback_data' => 'return'],
                           ],
                       ],
                   ]
               ];
             return  $this->telegram->sendMessage($options);
}// change_photo
else if($this->query=="change_name"){
    $this->user->status='change_name';
    $this->user->save();
   
               $this->telegram->deleteMessage([
                   'chat_id'=>$this->chatId,
                   'msgId'=>$this->message_id
   
               ]);
               $options=[
                   'chat_id'=>$this->chatId,
                   'text'=>"What is your Name?",
                   'message_id'=>$this->message_id,
                   "reply_markup" => [
                       'inline_keyboard' => [
                           [
                               ['text' => 'Return 🔙', 'callback_data' => 'return'],
                           ],
                       ],
                   ]
               ];
             return  $this->telegram->sendMessage($options);
}
        else if($this->query=="change_age"){
 $this->user->status='change_age';
 $this->user->save();

            $this->telegram->deleteMessage([
                'chat_id'=>$this->chatId,
                'msgId'=>$this->message_id

            ]);
            $options=[
                'chat_id'=>$this->chatId,
                'text'=>"What is your age?",
                'message_id'=>$this->message_id,
                "reply_markup" => [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Return 🔙', 'callback_data' => 'return'],
                        ],
                    ],
                ]
            ];
          return  $this->telegram->sendMessage($options);
        }   // 
 else if($this->query=="buy"){
    $response=$this->telegram-> deleteMessage([
        'chat_id' =>$this->chatId,
        'msgId' => $this->message_id,
      ]);
      return $this->payLink();
  } 
  if($this->query=='remove_history') return $this->removeHistory();
  if($this->query=='coin') {
    $response=$this->telegram-> deleteMessage([
        'chat_id' =>$this->chatId,
        'msgId' => $this->message_id,
      ]);
    return $this->coin();
}if($this->query=='profile'){ 
       return $this->telegram-> editInlineKeyboard([
        'chat_id'=>$this->chatId,
        'message_id'=>$this->message_id,
        'reply_markup'=>$this->profileSetting
       ]);
}if($this->query=='back'){ 
    $response=$this->telegram-> deleteMessage([
        'chat_id' =>$this->chatId,
        'msgId' => $this->message_id,
      ]);
      $this->setting();
} else if($this->query=='privacy'){
    $options=[
   'chat_id'=>$this->chatId,
   'message_id'=>$this->message_id,
   'caption' => 'Choose your status:
    
📵 Offline: Receive messages only.
📶 Online: Discoverable and receive messages.
👻 Hidden: Not discoverable and cannot receive messages.',
   'reply_markup'=>[
    'inline_keyboard' => [
        [
            [
                'text' => '📵 Offline',
                'callback_data' => 'filter_status_offline',
            ],
        ],
        [
            [
                'text' => '📶 Online',
                'callback_data' => 'filter_status_online',
            ],
        ],
        [
            [
                'text' => '👻 Hidden',
                'callback_data' => 'filter_status_hidden',
            ],
        ],
    ],
]


    ];
return $this->telegram->editMessageCaption($options);

}
if($this->query=='stats' || $this->query=='send_message'||  $this->query=='free_admin'|| $this->query=='paid_admin'){
    return $this->admin->processCallbackQuery($this->query,$this->chatId);
}else 
if($this->query=='filters'){  

    $filter=$this->user->filter()->first();
    if($filter){

    
    $age=$filter->age??"ANY";
    $gender=$filter->gender??"ANY";
    $location=$filter->location??"ANY";
    }else{
        $age="ANY";
        $gender="ANY";
        $location="ANY";
    }
    $text= " Add filters to get the best result! 🔍✨
    
Age: ".$age."
Gender: ".$gender."
location: ".$location;

    $options=[
    'chat_id'=>$this->chatId,
   'message_id'  =>$this->message_id,
   'caption'=>$text,
   'reply_markup'=>[
    'inline_keyboard' =>$this->filterKeyboard
],
];
    

   return $this->telegram->editMessageCaption($options);
    
}
else if($this->query=='filter_age'){

    $agefilterkeyboard=[
        'inline_keyboard' => [
            [
                ['text' => '18-21', 'callback_data' => 'filter_age_18-21'],
                ['text' => '22-25', 'callback_data' => 'filter_age_22-25'],
                ['text' => '26-29', 'callback_data' => 'filter_age_26-29'],
            ],
            [
                ['text' => '30-33', 'callback_data' => 'filter_age_30-33'],
                ['text' => '34-35', 'callback_data' => 'filter_age_34-35'],
                ['text' => '35+', 'callback_data' => 'filter_age_35-plus'],
               
            ],
            [ ['text' => 'Any', 'callback_data' => 'filter_age_any'],]
        ],
    ];
    return $this->telegram->editInlineKeyboard(
        [
    'chat_id'=>$this->chatId,
    'message_id'=>$this->message_id,
    "reply_markup" => $agefilterkeyboard
        ]
        );
    
}  //filter_age

else if($this->query=='filter_location'){ //filter_location}
$locationFilter=[
    'inline_keyboard' => [
        [
            ['text' => '📍 Nearby', 'callback_data' => 'filter_location_nearby'],
        ],
        [
            ['text' => '🌍 Any Location', 'callback_data' => 'filter_location_any'],
        ],
        [
            ['text' => '🌌 Far Away', 'callback_data' => 'filter_location_far'],
        ],
    ],
];
return $this->telegram->editInlineKeyboard(
    [
'chat_id'=>$this->chatId,
'message_id'=>$this->message_id,
"reply_markup" => $locationFilter
    ]
    );
} 
else if($this->query=='filter_gender'){ //filter_location
    $genderAskFilter=[
        'inline_keyboard' => [
            [
                ['text' => '♂️ Male', 'callback_data' => 'filter_gender_male'],
                ['text' => '♀️ Female', 'callback_data' => 'filter_gender_female'],
            ],
          
            [
                ['text' => '🧑‍🤝‍🧑 Any', 'callback_data' => 'filter_gender_other'],
            ],
        ],
    ];

    return $this->telegram->editInlineKeyboard(
        [
'chat_id'=>$this->chatId,
'message_id'=>$this->message_id,
"reply_markup" => $genderAskFilter
        ]
        );
} else if(substr($this->query,0,14)=='filter_status_'){
    $active =substr($this->query,14);
    $this->user->active=$active;
    $this->user->save();
    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
        return $this->setting();
} 
else if(substr($this->query,0,11)=='filter_age_'){
    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
        $age=substr($this->query,11);
        $filter=$this->user->filter()->first();
        if($filter){
           $filter->age=$age;
           $filter->save();
           return $this->setting();
        }
        $filter= new Filter();
        $filter->age=$age;
       
        $this->user->filter()->save($filter);
        return $this->setting();;

}
else if ( $this->query=='filter_location_nearby' || $this->query=='filter_location_any'|| $this->query=='filter_location_far'  ){
    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
        if( $this->query=='filter_location_nearby') $location="nearby";
        if( $this->query=='filter_location_any') $location="any";
        if( $this->query=='filter_location_far') $location="far";
        
        $filter=$this->user->filter()->first();
        if($filter){
           $filter->location=$location;
           $filter->save();
           return $this->setting();
        }
        $filter= new Filter();
        $filter->location=$location;
       
        $this->user->filter()->save($filter);
        return $this->setting();;

}
else if ( $this->query=='filter_gender_male' || $this->query=='filter_gender_female'|| $this->query=='filter_gender_other'  ){
    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
    if( $this->query=='filter_gender_male') $gender="Male";
    if( $this->query=='filter_gender_female') $gender="Female";
    if( $this->query=='filter_gender_other') $gender="Any";
    
    $filter=$this->user->filter()->first();
    if($filter){
       $filter->gender=$gender;
       $filter->save();
       return $this->setting();
    }
    $filter= new Filter();
    $filter->gender=$gender;
   
    $this->user->filter()->save($filter);
    return $this->setting();;
    // 
}
else if($this->query=='change_gender'){

$genderAsk=[
    'inline_keyboard' => [
        [
            ['text' => '♂️ Male', 'callback_data' => 'gender_male'],
            ['text' => '♀️ Female', 'callback_data' => 'gender_female'],
        ],
        [  ['text' => 'Back 🔙', 'callback_data' => 'back'],]
        /*[
            ['text' => '🧑‍🤝‍🧑 Any', 'callback_data' => 'gender_other'],
        ],*/
    ],
];
$options=[
    'chat_id'=>$this->chatId,
    'caption'=>"What is your gender? 🧍‍♂️🧍‍♀️",
    'message_id'=>$this->message_id,
    "reply_markup" => $genderAsk
];
$this->telegram->editMessageCaption($options);
}if($this->query=='gender_male'){
    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
    $this->user->gender="Male";
    
    $this->user->save();
    return $this->setting();
}if($this->query=='gender_female'){
    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
    $this->user->gender="Female";
    $this->user->save();
    return $this->setting();
}   else    if($this->query=="search"){
    $msg=[
       'chat_id'=>$this->chatId,
       'text'=>"🔍 Please send the username you want to search in the following format: /_username."
    ];
     return $this->telegram->sendMessage($msg);
       }else if($this->query=='discover'){
        $this->telegram->deleteMessage(
            [
                'chat_id'=>$this->chatId,
                'msgId'=>$this->message_id,
            ]
            );
        return $this->discover();
       }
       else if(substr($this->query,0,5)=='next_'||substr($this->query,0,5)=='prev_') {

        $this->telegram->deleteMessage(
            [
                'chat_id'=>$this->chatId,
                'msgId'=>$this->message_id,
            ]
            );
         $id=substr($this->query,5);
         if(substr($this->query,0,5)=='next_')
        return  $this->discover($id);
         if(substr($this->query,0,5)=='prev_')
        return $this->discover(null,$id);
       }else if(substr($this->query,0,12)=='message_chat'){
       return $this-> handleChatData($this->query);
       }
else if($this->query=='change_location'){

    $this->telegram->deleteMessage(
        [
            'chat_id'=>$this->chatId,
            'msgId'=>$this->message_id,
        ]
        );
    $options=[
        'chat_id'=>$this->chatId,
        'text'=>"Please share your location 🌍",
       // 'message_id'=>$this->message_id,
        "reply_markup" => [
            'keyboard' => [
                [
                    ['text' => '🌍 Share Location', 'request_location' => true],
                    ['text' => 'Back 🔙'],
                ],
            ],
            'resize_keyboard' => true,
        ]];
   return  $this->telegram->sendMessage($options);
}


    }
    public function createRate($key, $id){

     //   if($this->chatId==$id) return 0;
  $rates=$this->user->rate()->where('user_id',$id)->first();
  if($key=='report'){
    $this->telegram->deleteMessage([
        'chat_id'=>$this->chatId,
        'msgId'=>$this->message_id
    ]); 
    $report="1";
    $options=[
        'chat_id'=>$id,
        'text'=>' ☄️ Notification 
🔥 Ohh! Someone has just reported your profile! 🔥'
    ];
    //return 
    $this->telegram->sendMessage($options);

}
  if(!$rates){
    $report=0;
    $rate=0;
if($key=='like'){
   
$options=[
    'chat_id'=>$id,
    'text'=>"⚡️ Notification 
🎉 Hooray! You've got a new like on your profile! 🎉 "
];
//return 
 $this->telegram->sendMessage($options);

$rate="1";
}
else{
    $rate="0";
}
$rates= new Rate();
$rates->user_id=$id;
//$rater_id=$this->chatId;
$rates->rate=$rate;
$rates->report=$report;
return $this->user->rate()->save($rates);
  } 
  return $rates;
    }
    public function discover($nextId=null,$prevId=null){

     
        if($this->user->filter==null) {

            $filter= new Filter();
           $this->user->filter= $this->user->filter()->save($filter);
           
        } 

if($this->user->filter !==null){
    
}
if ( $this->user->filter->discover == null) $excludeIds = []; else{
    $excludeIds = explode(',', $this->user->filter->discover);
}
$excludeIds[] = $this->chatId;

$excludeIds[] = $nextId??0;

/*
$randomUser = DB::table('users')
    ->whereNotIn('id', $excludeIds)
    ->where(function ($query) {
        $query->where('active', 'true')
            ->orWhere('active', 'online');
    })
    ->inRandomOrder()
    ->first();
    */
    $filter=$this->user->filter()->first();
  
$randomUser = DB::table('users')
    ->whereNotIn('id', $excludeIds)
    ->where(function ($query) use ($filter) {
        $query->where('active', 'true')
            ->orWhere('active', 'online');
    }) 
    ->where(function ($query) use ($filter) {
        // Filter by age
        if ($filter->age === 'Any'||$filter->age === 'any') {
            // No age filter
        }  elseif (strpos($filter->age, '+') !== false) {
            $age = str_replace('+', '', $filter->age);
            $query->where('age', '>=', $age);
        }  elseif (strpos($filter->age, '-') !== false) {
       
            list($minAge, $maxAge) = explode('-', $filter->age);
            $query->where('age', '>=', $minAge);
            $query->where('age', '<=', $maxAge);
            
           // $query->whereBetween('age', [intval($minAge), intval($maxAge)]);
        } else {
            // Single age value
            $query->where('age', $filter->age);
        }
    });
  //  ->inRandomOrder()
   // ->first();
    $filteredUsers = $randomUser->get();

    if ($filteredUsers->isEmpty()) {
        $this->user->filter->discover = null;
        $this->user->filter->save(); 
     return   $this->telegram->sendMessage([
            'chat_id'=>$this->chatId,
            'text'=>"No users found! 
😔👥 Please try again later or adjust your filter settings. 🔄🔍 /setting  "
        ]);
    }

    // Convert the result to a Laravel Collection
    $filteredUsers = collect($filteredUsers);

    // Now we can further filter by gender using the Collection methods
    if ($filter->gender !== 'Any'&&$filter->gender!==null) {
        $filteredUsers = $filteredUsers->filter(function ($user) use ($filter) {
           if(isset($this->calculateGenderPercentage($user->id)['result'])){

     return strtolower($filter->gender) ==strtolower($this->calculateGenderPercentage($user->id)['result']);
            }else return false;
          
            
        });
    }

    if ($filteredUsers->isEmpty()) {
        $this->user->filter->discover = null;
        $this->user->filter->save(); 
      return  $this->telegram->sendMessage([
            'chat_id'=>$this->chatId,
            'text'=>"No users found!
😔👥 Please try again later or adjust your filter settings. 🔄🔍 /setting "
        ]);     
    }

    
    $randomUser = $filteredUsers->random();





// Update $this->user->filter->discover with the ID of the newly selected random user
if($prevId)  $randomUser=User::find($prevId);

if ($randomUser) {
    $newUserId = $randomUser->id;
    $this->user->filter->discover = $this->user->filter->discover ? $this->user->filter->discover . ',' . $newUserId : (string)$newUserId;
    $this->user->filter->save(); 
} else {
    $this->user->filter->discover = null;
    $this->user->filter->save(); 
 return   $this->telegram->sendMessage([
        'chat_id'=>$this->chatId,
        'text'=>"No users found! 
😔👥 Please try again later or adjust your filter settings. 🔄🔍 /setting "
    ]);
}



    
/*
// Update $this->user->filter->discover with the ID of the newly selected random user
if($prevId)  $randomUser=User::find($prevId);

if ($randomUser) {
    $newUserId = $randomUser->id;
    $this->user->filter->discover = $this->user->filter->discover ? $this->user->filter->discover . ',' . $newUserId : (string)$newUserId;
    $this->user->filter->save(); 
} else {
    $this->user->filter->discover = null;
    $this->user->filter->save(); 
 return   $this->discover();
}
*/
        $gender=$this->calculateGenderPercentage($randomUser->id);
        if($gender!==null) $gender=$gender['result']."  Verified[✅]"; //".round($gender['percentage'],1)."%
        else $gender="❓";
        $age=$randomUser->age??"❓";
    
        $location=$randomUser->location??"❓";
        
     $keyboard=  $this->buttonType($this->user, $randomUser);
     
        $button = [["text" => "⬅️Prev", "callback_data" => "prev_".$nextId],  
        ["text" => "➡️Next", "callback_data" => "next_".$randomUser->id] ];
        $keyboard['inline_keyboard'][2]=$button;
        $keyboard['inline_keyboard'][2]=$button;
        $keyboard['inline_keyboard'][2]=$button;
           
               
           $profile=[
            'chat_id'=>$this->chatId,
            'caption'=>"✨🎯 Chat with your new friend and enjoy your time! 🚀🌟

👤 Name: ".$randomUser->name.'
🎂 Age: '.$age.'
⚧️ Gender: '.$gender.'
🌍 Distance: '.$this->calculateDistance($randomUser).' Km
========================
[⚠️]🕵️‍♀️ Gender verification is based on users feedback. 
           ','photo'=>$randomUser->image??"AgACAgQAAxkBAAPKZLLLUe4MqWhVD9TxUOX_2VKmeUQAArG-MRtygpFRhBlk2jx0Ax4BAAMCAANtAAMvBA",
           "reply_markup" => $keyboard];
        
        return $this->telegram->sendPhoto($profile);

    }
  public function commandHandler($message){ /*************************** */
    if(substr($message['text'],0,20)=="/start referal_code="){
        $code=substr($message['text'],20);
        return $this->handleReferralLink($code);

    }else if($message['text']=='/admin'){
        return $this->admin->processIncomingMessage($message,$this->chatId);

    }
    else if($message['text']=='/rules'){
        $text="📜 Rules - 📜
    
1. You can earn points in several ways:
   - Daily Bonus: Get points by checking the bot daily.
   - Sending and Accepting Requests: Earn points by sending and accepting chat requests.
   - Declining Requests: Receive points when you decline other users' chat requests.
   - Referral Bonus: Get bonus points for referring new users to the bot.

2. Acceptance Requirement: For every 4 consecutive requests you receive, you must accept at least one user. Failure to do so will result in automatic acceptance of the 4th user.

3. Withdrawal: You can withdraw your points once your balance reaches 100 Birr.

4. Strategies for More Points: Explore different strategies to earn more points and improve your chatting experience.

5. Minimum Point Requirement: To send chat requests, you need to purchase a minimum number of points.

By following these rules, you can enjoy a rewarding and enjoyable experience on our bot. Have fun chatting and connecting with new friends! 🌟🚀
";
       return $this->telegram->sendMessage([
            'chat_id'=>$this->chatId,
            'text'=>$text
        ]); }
    else if($message['text']=='/aboutus'){
        $text="About Us - ℹ️

This bot was developed by @tiletsolution. We are a team of passionate developers dedicated to creating innovative solutions to enhance your chat experience. Our goal is to provide you with a seamless and enjoyable messaging platform.
        
If you have any questions, suggestions, or need assistance, feel free to contact us at @ask_tiletsolution_bot (https://t.me/ask_tiletsolution_bot). We value your feedback and are here to help you make the most of our services.
        
Thank you for using our bot, and we hope you have a fantastic time chatting and connecting with new friends! 🚀🌟
        ";
       return $this->telegram->sendMessage([
            'chat_id'=>$this->chatId,
            'text'=>$text
        ]);
    }
    else if($message['text']=='/referallink'){
        $referralLink = $this->generateReferralLink();

$text = "🎉 Here is your referral link! 🎉\n\n";
$text .= "Share it with others and get 300 extra points per new user!\n\n";
$text .= "Referal Link: $referralLink";

$text .= "\n🚀 Join now using my referral link and get started with 200 bonus points! 🎁🎉";
$text .= "\n\nMake new friends and get paid for chatting with new users! 💬💰";


$options = [
    "chat_id" => $this->chatId,
    "text" => $text,
];

return $this->telegram->sendMessage($options);
    }
    else if($message['text']=="/chathistory"){
        
       
          return $this->sendHistory();
    }else    if($message['text']=="/help"){
        return $this->helpMsg();
    }else    if($message['text']=="/pay"||$this->query=='buy'){
        return $this->payLink();
    }
    else    if($message['text']=="/search"){
 $msg=[
    'chat_id'=>$this->chatId,
    'text'=>"🔍 Please send the username you want to search in the following format: /_username."
 ];
  return $this->telegram->sendMessage($msg);
    }
    else    if($message['text']=="/stopchat"){
        return $this->stopChat();
    }
    else    if($message['text']=="/changechat"){

    }else    if($message['text']=="/balance"){
  return $this->coin();

    }else if(substr($message['text'],0,2)=="/_"){
        $username=substr($message['text'],2);
      //  return $username;
    return $this->profile($username);

    } else if($message['text']=="/profile"){
         $this->setting();
        return $this->telegram-> editInlineKeyboard([
            'chat_id'=>$this->chatId,
            'message_id'=>$this->telegram->msgId,
            'reply_markup'=>$this->profileSetting
           ]);

} else if($message['text']=="/discover"){
 return $this->discover();
}
else if($message['text']=="/setting"||$message['text']=='/start'){
    return $this->setting();
   }
    return  $this->telegram->sendMessage([
        'chat_id'=>$this->chatId,
        'text'=>'invalid command'
    ]);
  }
  public function profile($username)
  {
   $userProfile=User::where('username',$username)->first();
   if($userProfile){
   if($this->user->username==$userProfile->username)
   return $this->telegram->sendMessage([
   'chat_id'=>$this->chatId,
   'text'=>'🌟✨ This is your username: ✨🌟'
]);
   
   
$replyMarkup=$this->buttonType($this->user, $userProfile);
$age=$userProfile->age??"❓";
$gender=$this->calculateGenderPercentage($userProfile->id);
  if($gender!==null) $gender=$gender['result']."  Verified[✅]"; //if($gender!==null) $gender=$gender['result']." ".$gender['percentage']."%";
else $gender="❓";
$location=$userProfile->location??"❓";

   $profile=[
    'chat_id'=>$this->chatId,
    'caption' => "
📝 User

👤 Name: ".$userProfile->name."
🎂 Age: ".$age."
⚧️ Gender: ".$gender."
🌍 Distance: ".$this->calculateDistance($userProfile)." km
---------------------------
    ",
  'photo'=>$userProfile->image??"AgACAgQAAxkBAAPKZLLLUe4MqWhVD9TxUOX_2VKmeUQAArG-MRtygpFRhBlk2jx0Ax4BAAMCAANtAAMvBA",
   "reply_markup" => $replyMarkup];

return $this->telegram->sendPhoto($profile);
   }
   $this->telegram->sendMessage(['chat_id'=>$this->chatId, 'text'=>"😔 Sorry, no user was found."]); 
  }
  public function helpMsg(){
    $helpMessage = <<<EOM
📚 Available Commands:

/help - ❓ Get a list of available commands and their functionalities.
/start - 🚀 Start the bot and explore its features.
/ameleworq - 🌟 Start chatting with the AmelEworq AI assistant.
/stopameleworq - ⛔️ Deactivate the AmelEworq feature.
/profile - 👤 View your profile information.
/setting - ⚙️ Manage your account settings.
/chathistory - 📚 Access your previous chat history.
/stopchat - 🛑 End the current conversation.
/discover - 🔎 Discover new people and expand your social network.
/search - 🔍 Find potential matches based on your preferences.
/balance - 💰 Check your current coin balance.
/pay - 💳 Make a payment for additional features.
/bonus - 🎁 Get your daily bonus points.
/referallink - 🔗 Get your referral link and earn extra points.
/aboutus - ℹ️ Learn more about us and our mission.
/rules - 📜 View the rules and guidelines.

Feel free to use these commands to navigate and enjoy the bot's features!
EOM;
    $msg=[
        'chat_id'=>$this->chatId,
        'text'=>   $helpMessage
        
    ];
  
   return $this->telegram->sendMessage($msg);
  }
  public function coin(){
$coin=$this->user->coin;

$keyboard_more = [
    [
        ["text" => "Buy More 💰", "callback_data" => "buy"],
        
            ["text" => "Withdraw 💸", "callback_data" => "withdraw"],
    ]
];
$keyboard_buy = [
    [
        ["text" => "Buy 💰", "callback_data" => "buy"],
        
            ["text" => "Withdraw 💸", "callback_data" => "withdraw"],
        
    ]
];
$replyMarkup = [
    "inline_keyboard" => $coin>0 ?$keyboard_more:$keyboard_buy
];



$balance=$this->user->coin;
$balance /=100;

$msg=[
    'chat_id'=>$this->chatId,
    'text'=> "🏦 Your Account Balance:

💬 Points: {$this->user->coin}
🌟 Bonus: {$this->user->bonus}
🔗 Referral: {$this->user->refral}
💰 Balance: {$balance} Birr

📣 Share the link to get extra 300 points! 🎁\n
Referal Link: /referallink

To get your daily bonus, simply type /bonus. 🎁",
    "reply_markup" => $replyMarkup,
    "parse_mode" => "markdown",

];
 return $this->telegram->sendMessage($msg);

  }
   public function payLink(){
     $paymentLink=route('pay', $this->chatId) ;
    
   if($this->user->coin==0||$this->user->paid=="false"){
$msg=[
    "chat_id"=>$this->chatId,
    'text'=>"💰 To proceed with the payment, please click the link below:

    👉 PayLink: {$paymentLink}
    
This link will take you to the payment page where you can complete your transaction."
];
   }else{
    $msg=[
        "chat_id"=>$this->chatId,
        'text'=>"💰 To proceed with the payment, please click the link below:

        👉 PayLink: {$paymentLink}
        
    This link will take you to the payment page where you can complete your transaction."
    ];
   }
   return $this->telegram->sendMessage($msg);
   }
    public function removeHistory(){
       $history=$this->user->history()->get();
        if(count($history)!=0){
         foreach($history as $history) 
           $history->delete();
           $chatHistory="Chat history deleted";
        }else{
            $chatHistory="No history to delete";
        }
return   $this->telegram->sendMessage(['chat_id'=>$this->chatId, 'text'=>$chatHistory]);
    }
   public function createHistory($user, $username){
        if(!$history=History::where('username',$username)->where('user_id',$user->id)->first())
       { $history= new History();
        $history->user_id=$user->id; 
        $history->username=$username; 
        $history->status='0';
        $history->save();
       }
       return 0;
    }
   public  function sendHistory(){
   /* $history= new History();
    $history->username='demoss';
    $history->status='0';
    $this->user->history()->save($history);
       */ 
        $chatHistory="";
        $user=User::find($this->chatId);
        if($user&&$user->status!=null){ 
            $chatHistory="Here is a list of chats:\n\n";
        $histories=$user->history()->get();
        if(count($histories)==0){
            $chatHistory="🔒 No chats\n\n💬 When you start a conversation, your chats will be saved here.";
        }else{
        foreach($histories as $history){
            if($history->status!="-1"){
            $chatHistory.= "👤 /_$history->username\n";
            }
        }
        $chatHistory.="\nClick on a username to start a chat with the user.";
       
  }  }else { return $this->registerMessage();
   
}
$keyboard = [
    [
        ["text" => "Remove History ❌", "callback_data" => "remove_history"]
    ]
];

$replyMarkup = [
    "inline_keyboard" => $keyboard
];
    $msg=[
        'chat_id'=>$this->chatId,
        "text"=>$chatHistory,
        "reply_markup" => count($histories)==0? null:$replyMarkup
        
    ];
  //  $msgId=
   return  $this->telegram->sendMessage($msg);
 
      
    ;

    }
 public function registerMessage(){
    $msg=[
        'chat_id'=>$this->chatId,
        'text'=>"⚠️ Registration Required ⚠️\n\nPlease register first to access this feature. Use the /profile command to create your account."
    ];

    $this->telegram->sendMessage($msg);
 }
  public function setting(){
    $inlineKeyboard = ['inline_keyboard'=>[
        [
            ["text" => "⚙️ Profile", "callback_data" => "profile"],
            ["text" => "🔍 Filters", "callback_data" => "filters"]
        ],
        [
            ["text" => "💰 Balance", "callback_data" => "coin"],
            ["text" => "🔒 Status", "callback_data" => "privacy"]
        ],
        [
            ['text' => 'Discover 🌟', 'callback_data' => 'discover'],
            ['text' => 'Search 🔍', 'callback_data' => 'search'],
        ],
    ]];


    $name=$this->user->name??"❓";
    $age=$this->user->age??"❓";
  //  $gender=$this->user->gender??"❓";
  $gender=$this->calculateGenderPercentage($this->chatId);
        if($gender!==null) $gender=$gender['result']."  Verified[✅]"; //".round($gender['percentage'],1)."%
        else $gender="❓";
    $location=$this->user->location??"❓";
    if($this->user->active=="true"||$this->user->active=="online"){
        $status="🟢 Online";
    }else $status="🔴 ".$this->user->active;
   
    $profile=[
        'chat_id'=>$this->chatId,
        'caption'=>"
        Edit your profile and let's get started! ✏️

Name: ".$name.'
Age: '.$age.'
Gender: '.$gender.'
location: '.$location.'
Status: '.$status.'

       
       ','photo'=>$this->user->image??"AgACAgQAAxkBAAPKZLLLUe4MqWhVD9TxUOX_2VKmeUQAArG-MRtygpFRhBlk2jx0Ax4BAAMCAANtAAMvBA",
       "reply_markup" => $inlineKeyboard];

       return $this->telegram->sendPhoto($profile);
    
  }

    public function paraseMessage($request){
     
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

return $result;
    }
    public function check($chatId, $message){

        $options =[
            'chat_id'=>$chatId,
            'photo'=> $message['photo'],
            'caption'=>"check function"//$message['caption']
         ]; 
        
    
     
      $response= $this->telegram->sendPhoto($options);
    
       Storage::disk('local')->put("dddd.txt", json_encode($response));
       return json_encode($response);
      
    
    }
    function buttonType($user1, $user2, $discover=false)
    {
      
        $acceptChatKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "Like 👍", "callback_data" => "chat_like_".$user2->id],
                    ["text" => "Report 🚫", "callback_data" => "chat_dislike_".$user2->id],
                  
                    ['text' => 'Back 🔙', 'callback_data' => 'back'],
                ],
                [["text" => "💬 Accept Chat", "callback_data" => "message_chat_accept_".$user2->id]],
            ]
        ];
    
        $sendRequestKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "Like 👍", "callback_data" => "chat_like_".$user2->id],
                    ["text" => "Report 🚫", "callback_data" => "chat_dislike_".$user2->id],
                    ['text' => 'Back 🔙', 'callback_data' => 'back'],
                ],
                [["text" => "🗯 Send Request", "callback_data" => "message_chat_request_".$user2->id]],
               
            ]
        ];
     
   
        $stopChatKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" =>"Like 👍", "callback_data" => "chat_like_".$user2->id],
                    ["text" => "Report 🚫", "callback_data" => "chat_dislike_".$user2->id],
                    ['text' => 'Back 🔙', 'callback_data' => 'back'],
                ],
                [["text" => "🛑 Stop Chat", "callback_data" => "message_chat_stop_".$user2->id]],
            ]
        ];
        
      
        $status = $this->checkChatStatus($user1->id, $user2->id); // Call the checkChatStatus function to get the chat status
    
        if ($status['request'] && $status['request'] == $user1->id) {
            return $stopChatKeyboard;
        } else if ($status['request']) {
            return $acceptChatKeyboard;
        } else if ($status['stop']) {
            return $sendRequestKeyboard;
        } else if ($status['onchat']) {
            return $stopChatKeyboard;
        }else if($status['new'])
        return $sendRequestKeyboard;

    
        return null; // Return null if the chat status does not match any defined types
    }
    
function checkChatStatus($user1Id, $user2Id)
{
    $chat = Chat::where(function ($query) use ($user1Id, $user2Id) {
        $query->where('user_id_from', $user1Id)->where('user_id_to', $user2Id);
    })->orWhere(function ($query) use ($user1Id, $user2Id) {
        $query->where('user_id_from', $user2Id)->where('user_id_to', $user1Id);
    })->orderBy('id', 'desc')->first();

    if ($chat) {
        if ($chat->status == 'onchat') {
          $onchat=true;
        } elseif ($chat->status == 'onrequest') {
           $req=$chat->user_id_from;
        } elseif ($chat->status == 'stop') {
          $stop=true;
        }
    }else{
        $new=true;
    }
  
       
 $status=[
    "request"=>$req??false,
    "stop"=>$stop??false,
    "onchat"=>$onchat??false,
    "new"=>$new??false,
 ];
    return $status;
}
    function calculateDistance($user2)
{
    if($this->user->locationData&&$user2->locationData){
        $user1Location = json_decode($this->user->locationData, true);
        $user2Location = json_decode($user2->locationData, true);
    
        $lat1 = $user1Location['latitude'];
        $lon1 = $user1Location['longitude'];
        $lat2 = $user2Location['latitude'];
        $lon2 = $user2Location['longitude'];
    
        $earthRadius = 6371; // Radius of the Earth in kilometers
    
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
    
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
        $distance = $earthRadius * $c;
    
        return round($distance,2);
    }
  return " ♾";
}
function generateRandomUsername() {
    $names = array(
        'Alex', 'Taylor', 'Jordan', 'Casey', 'Jamie', 'Sam',
        'Avery', 'Riley', 'Charlie', 'Hayden', 'Cameron', 'Peyton',
        'Dakota', 'Sydney', 'Jesse', 'Morgan', 'Blake', 'Drew',
        'Bailey', 'Skyler', 'Quinn', 'Reese', 'Emerson', 'Rowan'
    );
    
    $randomName = $names[rand(0, count($names) - 1)];
    $randomNumber = rand(100, 999);
    
    return $randomName . $randomNumber;
}

function addBonusPoints()
{
    $userLastCheckInTime = $this->user->last_check_in_time ?? null;

    // Check if the user has checked in before and 24 hours have elapsed since their last check-in
    if ($userLastCheckInTime === null || time() >= strtotime($userLastCheckInTime) + 86400) {
        // Add 200 bonus points to the user's existing bonus points
        $this->user->bonus += 100;
        $this->user->coin += 100;
        $this->user->last_check_in_time = date('Y-m-d H:i:s');
        $this->user->save();

        // Send a message to the user confirming the bonus points
        $message = "🎉 Congratulations! You have received 100 bonus points for checking in today! 🎁\n\nYour total bonus points now: " . $this->user->bonus . " 💫";
        $options = [
            "chat_id" => $this->chatId,
            "text" => $message,
        ];
        $this->telegram->sendMessage($options);
    } else {
        // User has already checked in within 24 hours, no need to grant bonus points
        // You can display a message to inform the user if needed
        $message = "🤔 You have already checked in today! Come back tomorrow for more bonus points! 💫";
        $options = [
            "chat_id" => $this->chatId,
            "text" => $message,
        ];
        $this->telegram->sendMessage($options);
    }
}
function withdraw()
{
    // Calculate the amount to withdraw in Birr
    $amountInBirr = $this->user->coin / 100;

    // Check if the user has enough coins to withdraw
    if ($amountInBirr >= 100) {
        // Perform the withdrawal process (you can implement the actual withdrawal logic here)

        // Send a message indicating the withdrawal is in progress
        $message = "🏦 Withdrawal in progress! 🏦\n\n";
        $message .= "Your withdrawal request for $amountInBirr Birr is being processed. Please allow some time for the transaction to complete.";

        $options = [
            "chat_id" => $this->chatId,
            "text" => $message,
        ];

        $this->telegram->sendMessage($options);
    } else {
        // Send a message indicating the user needs a minimum of 100 Birr to withdraw
        $message = "⚠️ Withdrawal failed! ⚠️\n\n";
        $message .= "You need a minimum of 100 Birr to withdraw your points. Keep chatting and earning more points to reach the minimum amount.";

        $options = [
            "chat_id" => $this->chatId,
            "text" => $message,
        ];

        $this->telegram->sendMessage($options);
    }
}

function generateCoinMessage()
{
   
    $user=$this->user;
    if (!($user->free =="true"||( $user->paid=="true" && $user->coin !="0"))) {
      /*  $message = "⚠️ Oops!\n It seems you need to purchase coins to access this feature. 💰🔒
Press /balance to buy coins and unlock the feature!";
$link=route('pay',[$this->chatId]);
$message=" 💰 Oops! 😬

To send requests and withdraw, you need to register and buy at least 500 points for just 5 birr! Here's the link 👉 {$link}

Get started now and unlock exciting features with this one-time payment! 🚀💫";
        $this->telegram->sendMessage([
"chat_id"=>$this->chatId,
"text"=>$message,

        ]);
        */
          $botPayment=new BotPaymentController($this->chatId);
          $botPayment->sendInvoice();
        // Add additional formatting or actions as needed
    return false;
    } else {
        return true;
    }

   
}

public  function hasChattedLessThanTenTimes($user1Id, $user2Id)
  {
      $count = DB::table('chats')
                  ->where(function ($query) use ($user1Id, $user2Id) {
                      $query->where('user_id_from', $user1Id)
                            ->where('user_id_to', $user2Id);
                  })
                  ->orWhere(function ($query) use ($user1Id, $user2Id) {
                      $query->where('user_id_from', $user2Id)
                            ->where('user_id_to', $user1Id);
                  })
                  ->count();
  
      if($count <= 5) return true;
      else {
          $this->telegram->sendMessage([
        'chat_id'=>$this->chatId,
        'text'=>"⚠️ You have reached the maximum chat limit with this user. Enjoy your conversations! 🚀🌟"
  
      ]);
    return false;
}
}

function handleChatData($data)
{
    $dataParts = explode('_', $data);

    if (count($dataParts) >= 3) {
        $action = $dataParts[2];
        $userId = $dataParts[3];

        switch ($action) {
            case 'request':
               {
                   if(!$this->hasChattedLessThanTenTimes($this->chatId, $userId)){ return null;}
 $user=User::find($userId);
 
                if($user->decline==3) {

                    $this->user->coin=$this->user->coin + 800;
                    $this->user->save();
                    $user=User::find($userId);
                    $user->decline=$user->decline-3;
                    $user->save();
                return $this->acceptChat($userId);
            }
if(!$this-> generateCoinMessage()){
    break; return 0;
}
                $this->telegram->deleteMessage([
                    'chat_id'=>$this->chatId,
                    'msgId'=>$this->message_id
            
                ]);

                $inlineKeyboard = [
                    "inline_keyboard" => [
                        [
                            ["text" => "Stop ❌", "callback_data" => "message_chat_stop_".$userId],
                        ],
                    ],
                ];
             
            
               
    $message = "💌 I have sent  a request, please wait. \n\nIf you want to stop, simply click on the 'Stop' button.";

            
                $options =  [
                    "chat_id" => $this->chatId,
                    "text" => $message,
                    "reply_markup" => $inlineKeyboard,
                ];

                 $this->telegram->sendMessage($options);
                 $inlineKeyboard = [
                    "inline_keyboard" => [
                        [
                            ["text" => "Accept Chat ✅", "callback_data" => "message_chat_accept_".$this->chatId],
                            ["text" => "Decline ❌", "callback_data" => "message_chat_decline_".$this->chatId],
                        ],
                    ],
                ];
                $message = "You have received a chat request. from user /_{$this->username}
Do you want to accept?";
            
                $options =  [
                    "chat_id" => $userId,
                    "text" => $message,
                    "reply_markup" => $inlineKeyboard,
                ];



                $chat=new Chat();  	
                $chat->user_id_from=$this->chatId;
                $chat->user_id_to=$userId;
                $chat->status="onrequest";
                $chat->save();
                $this->user->coin=$this->user->coin-50;
                $this->user->save();
                return $this->telegram->sendMessage($options);
               }
                break;

            case 'accept':
               
                {
                    $this->telegram->deleteMessage([
                        'chat_id'=>$this->chatId,
                        'msgId'=>$this->message_id
                
                    ]);
                    $status = $this->checkChatStatus($this->chatId, $userId);
                    if($status['stop']) {
                        $message = "🛑 You have not received a chat request from this user. 🛑 ";
                          $options = [
                              "chat_id" => $this->chatId,
                              "text" => $message,
                          ];
                          $this->telegram->sendMessage($options);
                          return 0;
                      }
        
                  
                    $this->user->decline=$this->user->decline-3;
                    $this->user->save();
                    $user=User::find($userId);
                    $user->coin=$user->coin+800;
                    $user->save();
                    return $this->acceptChat($userId);
                break;
                }
                   case 'decline':{
if($this->user->decline==3) return $this->acceptChat($userId);
                   return $this->stopChats($userId,true);

                    break;
                   }
            case 'stop':
                {
                  return $this->stopChats($userId);
            break;}
                
            default:
                // Invalid action
                // Handle the case when an invalid action is encountered
                break;
        }
    }
}
public function stopChats($userId,$decline=false){
    $status = $this->checkChatStatus($this->chatId, $userId); // Call the checkChatStatus function to get the chat status
    if($status['stop']) {
  $message = "🛑 You are not on chat with this user. 🛑 ";
    $options = [
        "chat_id" => $this->chatId,
        "text" => $message,
    ];
    $this->telegram->sendMessage($options);
    $this->telegram->deleteMessage([
        'chat_id'=>$this->chatId,
        'msgId'=>$this->message_id

    ]);

        return 0;
    }else if($status['onchat']){
        $options=[

        ];

    }
    if($decline){
        $this->user->decline+=1;
        $this->user->coin+=200;
        $this->user->save();
        $declines=($this->user->decline);
        $this->user->text="🚫 You have declined the chat request. \n\nYou have {$declines} declines.
\n\nIf you decline 3 times, the fourth request will be accepted automatically.";
    $responseText= "Oops! The user has declined your request. 🙅‍♂️";
$discoverKeyboard=null;
$reportKeyboard=null;
}else {
    $this->user->text=null;
}
 
    $discoverKeyboard = [
        "inline_keyboard" => [
            [
               
                ["text" => "Guess Male 👱", "callback_data" => "guess_male_".$userId],
                ["text" => "Guess Female 👩", "callback_data" => "guess_female_".$userId],

            ],
            [
                ["text" => "Discover 🔍", "callback_data" => "discover"],
            ]
        ],
    ];
    
    // Send the message with the inline keyboard
    $response = [
        "chat_id" => $this->chatId,
        "text" => $this->user->text?? "Chat stopped. Continue discovering new chats. 
Please help us with gender verification 🤔👤 What do you think the user's gender is? 🧐👩👨",
        "reply_markup" => $discoverKeyboard??null,
    ];
    $this->telegram->sendMessage($response);
    
    $this->telegram->deleteMessage([
        'chat_id'=>$this->chatId,
        'msgId'=>$this->message_id

    ]);



    $reportKeyboard = [
        "inline_keyboard" => [
            [
                ["text" => "Report 🚩", "callback_data" => "chat_report_".$this->chatId],
            ],
        ],
    ];
    
    // Send the message with the inline keyboard
    $options = [
        "chat_id" => $userId,
        "text" => $responseText?? "Oops! The user has left. 🙅‍♂️\n\nIf you want to report this message or the user, please click the 'Report' button below. Your feedback is important to us. 💬",
        "reply_markup" => $reportKeyboard??null,
    ];
    $this->telegram->sendMessage($options);
    $chat = DB::table('chats')
->where(function ($query) use ($userId) {
$query->where('user_id_from', $this->chatId)
->where('user_id_to', $userId);
})
->orWhere(function ($query) use ($userId) {
$query->where('user_id_from', $userId)
->where('user_id_to', $this->chatId);
})->orderBy('id', 'desc')
->first();

if ($chat) {
// Update the status to "stop" in the chats table
DB::table('chats')
->where('id', $chat->id)
->update(['status' => 'stop']);
}

$user=User::find($userId);
$this-> createHistory($this->user, $user->username);
$this-> createHistory($user, $this->user->username);
}
public function acceptChat($userId){
    $this->telegram->deleteMessage([
        'chat_id'=>$this->chatId,
        'msgId'=>$this->message_id

    ]);
    $this-> updateChatStatusToStop($userId);
    $this-> updateChatStatusToStop($this->chatId);   
    $chat = DB::table('chats')
    ->where(function ($query) use ($userId) {
        $query->where('user_id_from', $this->chatId)
            ->where('user_id_to', $userId);
    })
    ->orWhere(function ($query) use ($userId) {
        $query->where('user_id_from', $userId)
            ->where('user_id_to', $this->chatId);
    })->orderBy('id', 'desc')
    ->first();

if ($chat) {
    // Update the status to "stop" in the chats table
    DB::table('chats')
        ->where('id', $chat->id)
        ->update(['status' => 'onchat']);
            }

    $inlineKeyboard = [
        "inline_keyboard" => [
            [
                
                ["text" => "Stop ❌", "callback_data" => "message_chat_stop_".$userId],
            ],
        ],
    ];
    $inlineKeyboard_user = [
        "inline_keyboard" => [
            [
                ["text" => "Stop ❌", "callback_data" => "message_chat_stop_".$this->chatId],
            ],
        ],
    ];
    $response = "✨ Chat created! ✨\n\nFrom now on, anything you write will be forwarded.\n\nSay hi! 👋";
    $options =  [
        "chat_id" => $this->chatId,
        "text" => $response,
        "reply_markup" => $inlineKeyboard,
    ];
  
    $this->telegram->sendMessage($options);
    $response_userId = "🎉 Great news! User /_{$this->username} has accepted your request.\n\nFrom now on, anything you write will be forwarded.\n\nSay hi! 👋";
    $options =  [
        "chat_id" => $userId,
        "text" => $response_userId,
        "reply_markup" => $inlineKeyboard_user,
    ];
    $this->telegram->sendMessage($options);

   
}
public function calculateGenderPercentage($userId)
{
    $user = User::find($userId);
    if(!$user) return null;
    if(! $user->gender) return null;
    $genders = explode(',',  strtolower($user->gender));
    $totalCount = count($genders);
    $maleCount = 0;
    $femaleCount = 0;

    // Handle the first gender (user input) separately with more weight
    if ($totalCount > 0) {
        if ($genders[0] === 'male') {
            $maleCount += 2; // Increase weight for user input
        } elseif ($genders[0] === 'female') {
            $femaleCount += 2; // Increase weight for user input
        }
    }

    // Handle the subsequent guessed genders
    for ($i = 1; $i < $totalCount; $i++) {
        if ($genders[$i] === 'male') {
            $maleCount++;
        } elseif ($genders[$i] === 'female') {
            $femaleCount++;
        }
    }

    if ($totalCount == 0) {
        return null;
    }

    $malePercentage = ($maleCount / ($totalCount + 1)) * 100; // Add 2 to account for user input weight
    $femalePercentage = ($femaleCount / ($totalCount + 1)) * 100; // Add 2 to account for user input weight

    $result = ($malePercentage > $femalePercentage) ? 'Male' : 'Female';
    $percentage = max($malePercentage, $femalePercentage);

    return[
        'result' => $result,
        'percentage' => $percentage*0.98
    ];
}

function isUserOnChat()
{
    $userId=$this->chatId;
    // Check if the user is involved in any chat with status "onchat"
    $onChat = Chat::where(function ($query) use ($userId) {
            $query->where('user_id_from', $userId)
                ->where('status', 'onchat');
        })
        ->orWhere(function ($query) use ($userId) {
            $query->where('user_id_to', $userId)
                ->where('status', 'onchat');
        })  ->value('id');
      //  ->exists();

    return $onChat;
}

function stopChat()
{
    $chatId=$this->chatId;
    // Find the chat where the user is involved with status "onchat"
    $chat = Chat::where('status', 'onchat')
        ->where(function ($query) use ($chatId) {
            $query->where('user_id_from', $chatId)
                ->orWhere('user_id_to', $chatId);
        })
        ->first();

    if ($chat) {
        // Determine the ID of the other user in the chat
        $otherUserId = ($chat->user_id_from == $chatId) ? $chat->user_id_to : $chat->user_id_from;

        // Update the chat status to "stop"
        $chat->status = 'stop';
        $chat->save();

        // Send a message to the other user indicating that the chat has been stopped
        $discoverKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "Discover 🔍", "callback_data" => "discover"],
                ],
            ],
        ];
        
        // Send the message with the inline keyboard
        $response = [
            "chat_id" => $this->chatId,
            "text" => "Chat stopped. Continue discovering new chats. ✋",
            "reply_markup" => $discoverKeyboard,
        ];
        $this->telegram->sendMessage($response);

        $reportKeyboard = [
            "inline_keyboard" => [
                [
                    ["text" => "Report 🚩", "callback_data" => "chat_report_".$this->chatId],
                ],
            ],
        ];
        
        // Send the message with the inline keyboard
        $options = [
            "chat_id" => $otherUserId,
            "text" => "Oops! The user has left. 🙅‍♂️\n\nIf you want to report this message or the user, please click the 'Report' button below. Your feedback is important to us. 💬",
            "reply_markup" => $reportKeyboard,
        ];
        $this->telegram->sendMessage($options);
        $user=User::find($this->getOtherChatIdIfOnChat());
       if($user){
        $this-> createHistory($this->user, $user->username);
        $this-> createHistory($user, $this->user->username);
       } 
      
    }else{
        $options = [
            "chat_id" => $this->chatId,
            "text" => "⚠️ Oops! You are currently not connected to any user. 😔
Don't worry! You can use the /discover command to find new connections. 🚀"
            
           
        ];
        $this->telegram->sendMessage($options);

    }

}
function updateChatStatusToStop($userId)
{
    // Find the chats where the user is involved and the status is not "stop"
    $chats = Chat::where(function ($query) use ($userId) {
            $query->where('user_id_from', $userId)
                ->where('status', 'onchat');
        })
        ->orWhere(function ($query) use ($userId) {
            $query->where('user_id_to', $userId)
                ->where('status',  'onchat');
        })
        ->get();

    // Update the chat status to "stop" and retrieve the other user IDs
    $otherUserIds = 0;
    if(count($chats)>0){
    foreach ($chats as $chat) {
        if ($chat->user_id_from == $userId) {
            $otherUserIds = $chat->user_id_to;
        } else {
            $otherUserIds = $chat->user_id_from;
        }
        $chat->status = 'stop';
        $chat->save();
    }
    $reportKeyboard = [
        "inline_keyboard" => [
            [
                ["text" => "Report 🚩", "callback_data" => "chat_report_".$this->chatId],
            ],
        ],
    ];
    
    // Send the message with the inline keyboard

    $options = [
        "chat_id" => $otherUserIds,
        "text" => "Oops! The user has left. 🙅‍♂️\n\nIf you want to report this message or the user, please click the 'Report' button below. ",
        "reply_markup" => $reportKeyboard,
    ];
    $this->telegram->sendMessage($options);
}
}

function getOtherChatIdIfOnChat()
{
    $userId=$this->chatId;
    // Find the chat where the user is involved with status "onchat"
    $chat = Chat::where(function ($query) use ($userId) {
            $query->where('user_id_from', $userId)
                ->where('status', 'onchat');
        })
        ->orWhere(function ($query) use ($userId) {
            $query->where('user_id_to', $userId)
                ->where('status', 'onchat');
        })
        ->first();

    if ($chat) {
        // Determine the ID of the other user in the chat
        if ($chat->user_id_from == $userId) {
            $otherUserId = $chat->user_id_to;
        } else {
            $otherUserId = $chat->user_id_from;
        }

        // Retrieve the chat ID of the other user
        $otherChatId = $otherUserId; // Assuming the chat ID is the same as the user ID
        // If the chat ID is different, adjust the code accordingly

        return $otherChatId;
    }

    return null; // User is not on chat or no active chat found
}
function sendMessage($options)
{
    $inlineKeyboard = [
        "inline_keyboard" => [
            [
                
                ["text" => "Stop ❌", "callback_data" => "message_chat_stop_".$this->chatId],
            ],
        ],
    ];
    $message = [
        "chat_id"=>$this->getOtherChatIdIfOnChat(),
        'text' => "🤖  /_{$this->username}  : 
".$options['text'] ?? null,
        'animation' =>$options['animation']?? null,
        'photo' => $options['photo']?? null,
        'caption' => $options['caption'] ?? null,
        'sticker' => $options['sticker']?? null,
       'reply_markup' => $inlineKeyboard,
    ];

     if (!empty($message['animation'])) {
        $this->telegram->sendAnimation($message);


        // Handle the response as needed
    } elseif (!empty($message['photo'])) {
        $this->telegram->sendPhoto($message);

        // Handle the response as needed
    } elseif (!empty($message['sticker'])) {
        $this->telegram->sendSticker($message);
    }else if (!empty($message['text'])) {
        $this->telegram->sendMessage($message);
 
 
 
         // Handle the response as needed
     }
}
function sendMessageToAllUsers()
{
    $message = "🚧 We are currently experiencing some technical issues on our server. We are working hard to fix them and get everything back to normal. 🛠️

We apologize for any inconvenience caused. Once the issue is resolved, we will send you a notification.
    
 Thank you for your patience and understanding. 🙏";
    $userIds = DB::table('users')->pluck('id');

    foreach ($userIds as $userId) {
        $options = [
            'chat_id' => $userId,
            'text' => $message,
        ];
$this->telegram->sendMessage($options);

        // Handle the response if needed
    }
    return "done";
}


function generateReferralCode()
{
    // Check if the user already has a referral code
    if ($this->user->referral_code === null) {
        do {
            // Generate a random 8-character referral code
            $referralCode = Str::random(8);
            // Check if the referral code already exists in the database
            $existingCode = User::where('referral_code', $referralCode)->exists();
        } while ($existingCode);

        // Save the generated referral code to the user's attribute
        $this->user->referral_code = $referralCode;
        $this->user->save();

        return $referralCode;
    }

    // Return the existing referral code if the user already has one
    return $this->user->referral_code;
}
function extractReferralCode($message)
{
    $text=$message['text'];
    $referralCodePattern = '/start=referal_code=([a-zA-Z0-9]+)/';

    if (preg_match($referralCodePattern, $text, $matches)) {
        return $matches[1]; // Extracted referral code
    }

    return null; // Referral code not found
}

function generateReferralLink()
{
    $referralCode = $this->generateReferralCode();
    return "http://t.me/achawach_beta_bot?start=referal_code=" . $referralCode;
}
function handleReferralLink($referralCode)
{ 
    $userId=$this->chatId;
    $user = User::find($userId);
    if ($user->referral_code === $referralCode) {
        // User clicked their own referral link
        $responseMessage = "⚠️ Oops! You clicked your own referral link. Please share this link with your friends and family to earn referral points! 💬";
       return $this->telegram->   sendMessage([
            "chat_id"=>$this->chatId,
            "text"=>$responseMessage
        ]);
    } else {
        $referrer = User::where('referral_code', $referralCode)->first();

        if ($referrer) {
            if ($user->referred_by) {
                // User has already been referred
                $responseMessage = "⏳ Sorry, the referral code has already been used.💬";
                $this->setting();
      return  $this-> telegram->  sendMessage([
                "chat_id"=>$this->chatId,
                "text"=>$responseMessage
            ]);
            } else {
                // Mark the new user as referred
                $user->referred_by = $referrer->id;
                $user->save();

                // Add bonus points to the referrer's referral points
                $referrer->refral += 300;
                $referrer->coin += 300;
                $referrer->save();
              //  $this->setting();
              $message = "🚀 Join @tiletsolution now to discover how you can use the bot and 💰make money! 🤑";

              // Inline keyboard buttons
              $joinUrl = 't.me/tiletsolution';
              $keyboard = [
                  'inline_keyboard' => [
                      [
                          ['text' => 'Join', 'url' => $joinUrl]
                      ],
                      [
                          ['text' => 'I have joined', 'callback_data' => 'return']
                      ]
                  ]
              ];
$options=[
    "chat_id"=>$this->user->id,
    "text"=>$message,
    "reply_markup"=>$keyboard
];

$this->telegram->sendMessage($options);
                // Send a response message to the new user who was referred
                $responseMessage = "🎉 Congratulations! You've earned 300 referral points! 💰 Thank you for referring your friend.";
                return  $this-> telegram->  sendMessage([
                    "chat_id"=>$user->referred_by,
                    "text"=>$responseMessage
                ]);
            }
        } else {
            // Invalid referral code
            $responseMessage = "⚠️ Oops! The referral code is not valid. Please make sure you use the correct referral link.";
         return   $this-> telegram->  sendMessage([
                "chat_id"=>$this->chatId,
                "text"=>$responseMessage
            ]);
        }
    }

  
}
}