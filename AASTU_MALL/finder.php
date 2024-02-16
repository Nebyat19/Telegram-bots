<?php 

$path = "https://api.telegram.org/bot5559362127:AAErs65jgSVyGJZJ0Uiu4-Nz_jgFTk21xDw";
$update = json_decode(file_get_contents("php://input"), TRUE);
$chatId = $update["message"]["chat"]["id"];
$message =  $update["message"]["text"];
$first = $update["message"]["from"]["first_name"];
$last = $update["message"]["from"]["last_name"];
$callback_query = $update['callback_query'];
$msgid=$update["message"]["message_id"];
$id=$update['callback_query']['from']['id'];
$file_id=$update['message']['photo'][1]['file_id'];
if(!isset($msgid))$msgid=$update['callback_query']["message"]["message_id"];
if(!isset($chatId))$chatId=$update['callback_query']['from']['id'];

$db=new database($chatId);
$posts=$db->lost();
$data =  $callback_query['data'];  if(!$data) $data = $update['message']['text'];
$code = substr($data, strpos($data, "_") + 1,3);  

$content = substr($data, strpos($data, "-") + 1); 
$join = json_encode([ 'inline_keyboard' => [ [  ['text' => 'Join', 'url'=>'https://t.me/tiletdemo'],]]]);
$replay = json_encode([
    'inline_keyboard' => [
        [
            ['text' => '📭 Replay', 'callback_data' => '_rly/'.$chatId.'-'.$msgid], 
        ]
    ]
]);
$cat=json_encode([ 'inline_keyboard' => [ 
    [  ['text' => '🔌 Electronics', 'callback_data'=>'_cat-🔌 Electronics'],],
    [  ['text' => '👚 Clothes', 'callback_data'=>'_cat-👚 Clothes'],],
    [  ['text' => '🎒 Bag', 'callback_data'=>'_cat-🎒 Bag'],],
    [  ['text' => '📄 Document', 'callback_data'=>'_cat-📄 Document'],],
    [  ['text' => '🧧 Id', 'callback_data'=>'_cat-🧧 Id'],],
    [  ['text' => '🛠 Other ', 'callback_data'=>'_cat-🛠 Other'],],

]]);
$admi=''; if($chatId==342413584||$chatId==594090978)$admi='💬 Message';
$main_keyboard=json_encode(array('keyboard'=>[
    ['🔍 Search ','💎 Found'], 
    ['💬 Feedback','📌 Help'], 
    ['📮 Contact us ','👥 About us'], 
    ['♻️ Check(ID)'],
    [$admi]
],
'resize_keyboard'=>true,
));
$null=json_encode(array('keyboard'=>[['🔙MENU']],'resize_keyboard'=>true,));
$null_skip=json_encode(array('keyboard'=>[['🔙MENU','➖SKIP']],'resize_keyboard'=>true,));
//catagoriy electroni, clothes, bag, document, 
$remove=[
    'remove_keyboard'=>True
];
 
$user=$db->user_state();
switch($user['state']){
    case 'mess':{
        $db-> update_state('state','0');
        if($message!='/start'&$message!='🔙MENU'){
           
$sub=$db->subscriber();
$maxsub=$db->maxsub();
$post=array(
    'chat_id'=>$chatId,
    
    'text'=>'<i>Message delivered 📩 </i>',
    'parse_mode'=>'HTML',
    'reply_markup'=>$main_keyboard
);
    send('sendMessage',$post); 
for($i=0;$i<$maxsub;$i++){
    if($sub[$i]['userid']!=$chatId){
        $post=array(
            'chat_id'=>$sub[$i]['userid'],
            
            'text'=>$message,
            'parse_mode'=>'HTML',
          
        );
            send('sendMessage',$post);
   }

}
 
   $db-> update_state('state','0');
        }else{ 
             $db->update_state('state','0');
            $post=array(
                'chat_id'=>$chatId,
                
                'text'=>'<i>Canceled</i>',
                'parse_mode'=>'HTML',
                'reply_markup'=>$main_keyboard
            );
                send('sendMessage',$post);  
        }
        break;
    }


    case 'w':{
        if($message!=='/start'&&$message!='🔙MENU'){

        $Feedback=array(
            'chat_id'=>'594090978',
            
            'text'=>'
            💬#_Feedback
-------------------------------------------
FROM: #_'.$chatId.'
MESSAGE: '.$message,
            'parse_mode'=>'HTML',
        'reply_markup'=>$replay
        
        );
            send('sendMessage',$Feedback);
            $Feedback=array(
                'chat_id'=>$chatId,
                
                'text'=>'Thank you for your feedback!
              ',
                'parse_mode'=>'HTML',
            'reply_markup'=>$main_keyboard
            
            );
                send('sendMessage',$Feedback);
            $db->update_state('state','0');}
            else{
                $db->update_state('state','0');
            }
        break;
    }

    case 's':{
        if($message!=='/start'&&$message!='🔙MENU'){

        $rply=array(
            'chat_id'=>$user['index1'],
            
            'text'=>'#NEW_MESSAGE 
'.$message,
            'parse_mode'=>'HTML',
            'reply_to_message_id'=>$user['index2'],
            'reply_markup'=>$replay
        
        );
            send('sendMessage',$rply);
            $rply=array(
                'chat_id'=>$chatId, 
                
                'text'=>'Reply successfully sent',
                'parse_mode'=>'HTML',
                'reply_markup'=>$main_keyboard
            
            );
                send('sendMessage',$rply);
            $db->update_state('state','0');
          
    }else{
        $db->update_state('state','0');
    }
    break;
}

}
switch($posts['index1']){
    case'desc':{
        $db->update_post('descri',$message); 
        $db->update_post('index1','cont'); 
        $default=[
             'chat_id'=>$chatId,
             'text'=>'<strong>How can they contact you? </strong>
<i>infact your preferable contact informations</i>
    ',
    'parse_mode'=>'HTML',
'reply_markup'=>$null
        ];
        send('sendMessage',$default); 
        break;
    }
        case 'cont':{
            $db->update_post('cont',$message); 
           $db->update_post('index1','edi'); 
            $db->update_post('masg_id',$msgid+1); 
          //  $db->crea();
          //

          create();
break;

        }
      
    }
switch($code){
    case 'cat':{
        $db->update_post('index1','pho');
       $db->update_post('p_cat',$content);
        deletep($msgid);
        $default=array(
            'chat_id'=>$chatId,  
            'text'=>'<strong>Do You Have The Photo? </strong>
Attach it, <i>Perhaps you can skip it </i>
            ',
            'parse_mode'=>'HTML',
      'reply_markup'=>$null_skip
        
        );
            send('sendMessage',$default);
            break;
    }
    case 'pos':{
        $db->update_post('index1','pos');
      
       deletep($msgid);
       create();
       $default=array(
        'chat_id'=>$chatId,  
        'text'=>'<strong>➖UPLOADED➖ </strong>
<i>see the channel </i>
        ',
        'parse_mode'=>'HTML',
  'reply_markup'=>$main_keyboard
    
    );
        send('sendMessage',$default);
        $db->update_post('status','posted');
        break;
    }
    case 'can':{
        $db->update_post('index1','post');
        deletep($msgid);
        $default=array(
            'chat_id'=>$chatId,  
            'text'=>'<strong>MAYBE later </strong>
 <i>You  can post anytime </i>
            ',
            'parse_mode'=>'HTML',
      'reply_markup'=>$main_keyboard
        
        );
            send('sendMessage',$default);
        break;
    }

    case 'rly':{
        $rid= get_string_between($data, '/', '-');
        $msgids = substr($data, strpos($data, "-") + 1); 
   $db-> update_state('index1',$rid);
    $db->update_state('index2',$msgids);
    
    $rply=array(
        'chat_id'=>$chatId,
        
        'text'=>'Write Your Reply: ',
        'parse_mode'=>'HTML',
        'reply_markup'=>$null
    
    
    );
        send('sendMessage',$rply);
        $db->update_state('state','s');
        break;
    }
 case 'get':{

   
        $newdb=new database($content);
        $post=$newdb->lost();
            if($chatId==$post['p_posterid']){
                $msgforward=array(
                    'chat_id'=>$chatId,
                    'text'=>'<strong> This is Your Post </strong>
<i>lets hope, someone will contact u📲</i>',
                    'parse_mode'=>'HTML',
                   
                   
                   
                   );
                
                   send('sendMessage',$msgforward);
            }else {
             $msgid=substr($data, strpos($data, "-") + 1);
             $msgforward=array(
             'chat_id'=>$chatId,
             
             'from_chat_id'=>'@finder_aastu',
             'message_id'=>$post['cmsgid'],
             'parse_mode'=>'HTML',
             'reply_markup'=>null
            
            );
            
            send('forwardMessage',$msgforward);
         

        $msgforward=array(
            'chat_id'=>$chatId,
            'text'=>'<strong>Good news,</strong> I have sent message to the person 
<i>soon there will be replay, just wait</i>',
            'parse_mode'=>'HTML',
           
           );
        
           send('sendMessage',$msgforward);
           $tovendor=array(
            'chat_id'=>$post['p_posterid'],
            
            'from_chat_id'=>'@finder_aastu',
            'message_id'=>$post['cmsgid'],
            'parse_mode'=>'HTML',
           
           
           );
           
           send('forwardMessage',$tovendor);
        $tovendor=[
            'chat_id'=>$post['p_posterid'],
         'text'=>'<strong>Some one is asking for this, </strong>
<i>lets say something</i>',
         'parse_mode'=>'HTML',
         'reply_markup'=>$replay
        ];
        send('sendMessage',$tovendor);
        
    }
    break;
 }
}

switch($message){
    case '/start':{

        $db->deletep();
        if(check($path, $chatId)){
            $default=array(
                'chat_id'=>$chatId,
                
                'text'=>'🔰<Strong>HELLO </strong>'.$first.' 👋
        ➖➖➖➖➖➖➖➖➖
        <i>Welcome! to @finder_aastu Bot</i>',
                'parse_mode'=>'HTML',
          'reply_markup'=>$main_keyboard
            
            );
                send('sendMessage',$default);
        }
        else{
            $default=array(
                'chat_id'=>$chatId,
                
                'text'=>'🔰<Strong>HELLO </strong>'.$first.' 👋
        ➖➖➖➖➖➖➖➖➖
        <i>Please Join our Channel with the button below to continue using 
        <strong>this bot</strong></i>',
                'parse_mode'=>'HTML',
            'reply_markup'=>$join
            
            );
                send('sendMessage',$default);
        }
            break;
        }
        case '🔙MENU':{
            $db->deletep();
            deletep($msgid-1);
            $default=array(
                'chat_id'=>$chatId,
                
                'text'=>'<strong>👋 Hey! </strong>
<i>Have You Lost Something?</i>
                ',
                'parse_mode'=>'HTML',
          'reply_markup'=>$main_keyboard
            
            );
                send('sendMessage',$default);
            break;
        }
      case '➖SKIP':{
        $db->update_post('index1','desc');
        $default=array(
            'chat_id'=>$chatId,
            
            'text'=>'<strong>Give me the description: </strong>
<i>something like what it looks like</i>
            ',
            'parse_mode'=>'HTML',
      'reply_markup'=>$null
        
        );
            send('sendMessage',$default);
        break;}
        case'💬 Message':{
            $cancel=array(
                'chat_id'=>$chatId,
                
                'text'=>'Write Your message to subscribers',
                'parse_mode'=>'HTML',
                'reply_markup'=>$null
            
            );
                send('sendMessage',$cancel);
           $db-> update_state('state','mess');
    
            break;
        }
        case '💎 Found':{
            //
            $db->deletep();
            $db->create_post('I_FOUND_THIS');
            $default=array(
                'chat_id'=>$chatId,
                
                'text'=>'<strong>Have YOU GOT Something?</strong>
<i>Lets find  the owner</i>
                ',
                'parse_mode'=>'HTML',
          'reply_markup'=>$null
            
            );
                send('sendMessage',$default);
                send('ReplyKeyboardRemove',$remove);
                $default=array(
                    'chat_id'=>$chatId,
                    
                    'text'=>'What is the category? 
                    ',
                    'parse_mode'=>'HTML',
              'reply_markup'=>$cat
                
                );
                    send('sendMessage',$default);

            break;
        }
        case '💬 Feedback':{
            $db->update_state('state','f');
            $Feedback=array(
                'chat_id'=>$chatId,
                
                'text'=>'📨 Write your FeedBack: ',
                'parse_mode'=>'HTML',
            'reply_markup'=>$null
            
            );
                send('sendMessage',$Feedback);
        
        
     $db->update_state('state','w');
            break;
        }
        case '🔍 Search':{
            $db->deletep();
          $db->create_post('I_Lost_this');
            $default=array(
                'chat_id'=>$chatId,
                
                'text'=>'<strong>Have You Lost Something?</strong>
<i>I can Help You, Lets find it🔍 </i>
                ',
                'parse_mode'=>'HTML',
          'reply_markup'=>$null
            
            );
                send('sendMessage',$default);
                send('ReplyKeyboardRemove',$remove);
                $default=array(
                    'chat_id'=>$chatId,
                    
                    'text'=>'What is the category? 
                    ',
                    'parse_mode'=>'HTML',
              'reply_markup'=>$cat
                
                );
                    send('sendMessage',$default);
                break;
        }
    case '📌 Help':{
    $Help=array(
        'chat_id'=>$chatId,
        
        'text'=>'<strong> DONT LOSE ANYTHING </strong>
➖➖➖➖➖➖➖➖➖➖➖➖➖➖➖
Have ever lost something and confused where to look for, or have you found something, and want to return but have no idea how. <strong>Here Is The Place</strong> .  
First look through the channel and if you find it simply contact the person or just press "☎️contact" button, i will forward it. 
▫️▫️▫️▫️▫️▫️▫️<strong>If Not</strong>▫️▫️▫️▫️▫️▫️▫️▫️
◾️If you lost something use "🔍 Search " button and insert the description plus  your contact information then it will be posted to the channel.
◾️If you find something use "💎 Found" button and insert description with your contact information separately.

<i> :You can use this bot to help u find what you lost <strong>free</strong> of any charge😎</i>
________________________________
JOIN: @tiletsolution
#TILETSOLUTION
',
        'parse_mode'=>'HTML',
    'reply_markup'=>$main_keyboard
    
    );
        send('sendMessage',$Help);
    break;
}
case '👥 About us':{
    $About=array(
        'chat_id'=>$chatId,
        
        'text'=>'This platform was developed by #tiletsolution'./*
<strong>who are we? </strong>
we are team of businessmans who provide solutions for problems.
we focus on solving small problems for free.
*/'
    <strong>join our channel for more </strong>
________________________________
JOIN: @tiletsolution
JOIN: @tiletsolution
#TILETSOLUTION
         ',
        'parse_mode'=>'HTML',
    'reply_markup'=>$main_keyboard
    
    );
        send('sendMessage',$About);
    break;
}
case '📮 Contact us':{
    $Contact=array(
        'chat_id'=>$chatId,
        
        'text'=>'📥 Contact Us
        ✍️ Support: @ask_tiletsolution_bot 
🔰 Join 
@tiletsolution
@tiletsolution
▬ ▬ ▬ ▬ ▬ ▬ ▬ ▬ ▬ ▬ ▬',
        'parse_mode'=>'HTML',
    'reply_markup'=>$main_keyboard
    
    );
        send('sendMessage',$Contact);
    break;
}
case '♻️ Check(ID)':{
    $default=array(
        'chat_id'=>$chatId,
        'text'=>'<strong>I can Search Your Id if its Posted already </strong>
    <i>something like what it looks like</i>
        ',
        'parse_mode'=>'HTML',
    'reply_markup'=>$null
    
    ); send('sendMessage',$default);
    break;
}
}


if(isset($file_id)&$posts['index1']=='pho') { 
$db->update_post('index1','desc'); 
$db->update_post('photo',$file_id); $default=array(
    'chat_id'=>$chatId,
    'text'=>'<strong>Give me the description: </strong>
<i>something like what it looks like</i>
    ',
    'parse_mode'=>'HTML',
'reply_markup'=>$null

); send('sendMessage',$default);
 }


function check($path, $chat_id){
    $check=$path.'/getChatMember?chat_id=@tiletdemo&user_id='.$chat_id; 
    $result=json_decode(file_get_contents($check), true);
   // file_put_contents('json.txt',json_encode( $result));
    if($result['ok']==true&$result['result']['status']!='left')
    return true; 
 else return false;
    }
function send($method, $data)
{  
    $url = "https://api.telegram.org/bot5559362127:AAErs65jgSVyGJZJ0Uiu4-Nz_jgFTk21xDw/". $method;

    if (!$curld = curl_init()) {
        exit;
    }
    curl_setopt($curld, CURLOPT_POST, true);
    curl_setopt($curld, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curld, CURLOPT_URL, $url);
    curl_setopt($curld, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($curld);
    $output=json_decode( $output,true);
 
    if(isset($output['result']['message_id'])){
        return $output['result']['message_id'];
    }else 
    {return $output['result'][0]['message_id'];
    }

    curl_close($curld);
    }


    class database{

public  $db ;
public $chatId;
function __construct($chatID){
    $this->db=mysqli_connect('localhost','root','','tiletsol_');
    $this->chatId=$chatID;
}
  function update_post($col,$message){
    $chatId=$this->chatId;
    $db=$this->db;
    $p_codeq="SELECT p_code FROM lost WHERE p_posterid='$chatId'AND status='0' LIMIT 1";
    $result= mysqli_query($db,$p_codeq);
    $p_code=mysqli_fetch_assoc($result);
    $p_code=$p_code['p_code'];

    $quesry="UPDATE lost SET $col='$message' WHERE p_code='$p_code' or p_code='$chatId'";
    mysqli_query($db,$quesry);
}
function create_post($typ){
    $chatId=$this->chatId;
    $db=$this->db;
    $q="insert into lost(p_posterid,p_type,p_cat,status) values('$chatId','$typ','0','0')";
    mysqli_query($db,$q);
     
}
function deletep(){
    $chatId=$this->chatId;
    $db=$this->db;
    $q="DELETE FROM lost WHERE (p_posterid='$chatId' AND status='0') or p_code='$chatId'";
        mysqli_query($db,$q);
    }
 function update_state($col,$val){
    $chatId=$this->chatId;
    $db=$this->db;
        $quesry="UPDATE lost_state SET $col='$val' WHERE userid='$chatId'";
         mysqli_query($db,$quesry);
     
     }
function user_state(){
    $chatId=$this->chatId;
    $db=$this->db;
    $quesry="SELECT * FROM lost_state WHERE userid='$chatId'";
    $result=mysqli_query($db,$quesry);
    $user_state=mysqli_fetch_assoc($result);
 if($user_state){
     return $user_state;
 }else{
    $q="insert into lost_state(userid,state) values('$chatId','0')";
    mysqli_query($db,$q);
     return NULL;
 }
}
 function subscriber(){
    $db=$this->db;
    $query = "SELECT userid FROM state where userid!='}'and userid!=0 and userid!=''";
    $result =   mysqli_query($db,$query);
    while(mysqli_fetch_assoc($result)){  $rows[] = mysqli_fetch_assoc($result);}
   

    return $rows;
}
function maxsub(){
    $db=$this->db;
    $max="SELECT COUNT(userid) FROM state where userid!='}'and userid!=0 and userid!='' ";
    $result= mysqli_query($db,$max);
    $max=mysqli_fetch_assoc($result);
   

    return $max['COUNT(userid)'];
}


function lost(){
    $chatId=$this->chatId;
    $db=$this->db;
    $quesry="SELECT * FROM lost WHERE ( status='0' and p_posterid='$chatId') or (p_code='$chatId') ";
    $result=mysqli_query(  $db,$quesry);
    $post_state=mysqli_fetch_assoc($result);
 if($post_state){
     return $post_state;
 }else{
   
     return NULL;
 }

}


function caption(){

    $post=$this->lost();
    $caption=   '
    <strong>#'.$post['p_type'].'</strong> 
'.$post['p_cat'].'            
'.$post['descri'].'  
              
<strong>Contact</strong>: '.$post['cont'].'
________________________________
BY: @finder_aastu
#TILETSOLUTION
@tiletsolution
                                  
                                   ';
                                   return $caption;
}

    }
    
    function create(){
        
         $Id='';
         $btn='';
        $flag=false;
        $db=$GLOBALS['db'];
       
        $lost=$db->lost();
        $user_state=$db->user_state();
        $caption=$db->caption();
        
        $post_edit = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '❌ Cancel', 'callback_data' => '_can'],
                    ['text' => '✅ Post', 'callback_data' => '_pos'],
       
                ]
            ]
        ]);
        $post_sub = json_encode([
            'inline_keyboard' => [
                [ 
                    ['text' => '☎️ Connect', 'callback_data' => '_get-'.$lost['p_code'],'url' => 'https://t.me/finder_aastu_bot?start=_get-'.$lost['p_code']],
                ]
            ]
        ]);
       
switch($lost['index1']){
case 'edi':{
$Id=$lost['p_posterid'] ; 
$btn=$post_edit;
    break;
}

case 'pos':{
   $db-> update_post('index1','posted');
    $flag=true;
$Id='@tiletdemo';
$btn=$post_sub;
    break;
}
default:{
    $Id=null;
    $btn=null;
    break;
}

        }
     

        if(!$lost['photo']) $lost['photo']='AgACAgQAAxkBAAN8Y26LpNSVYdbqIvwLycoZLMitez8AAgW9MRs7knlTj5kAAe5ZuGNzAQADAgADbQADKwQ';
        $msgforward=array(
            'chat_id'=>$Id,
          'reply_markup'=>$btn,
            'photo' => $lost['photo'],
            'caption'=>$caption,
            'parse_mode'=>'HTML'
           );
          
           $msgid=  send('sendPhoto',$msgforward);
           if($msgid!=null) if($flag) $db->update_post('cmsgid',$msgid);

    }
    function deletep($msgid){

        $delte_post=[
          'chat_id'=>$GLOBALS['chatId'] ,
          'message_id'=> $msgid
      ];
      send('deleteMessage',$delte_post);
    
  
  
  
    }

    function get_string_between($string, $start, $end){
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
?>