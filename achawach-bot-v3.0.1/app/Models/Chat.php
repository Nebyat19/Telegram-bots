<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Chat extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    public $incrementing = false; // This disables auto-incrementing for the primary key
    protected $keyType = 'string';


    public $table='chats';

    public function user_from(){
      return  $this->belongsTo(User::class,'user_id_from');

    }
    public function user_to(){
        return  $this->belongsTo(User::class,'user_id_to');
      }

      
     public function stopChat($userid,$status){
      $chat = $this->activeChat($userid);
    $chat->status = $status;
    $chat->save();

    $userFrom = User::find($chat->user_id_from);
    $userTo = User::find($chat->user_id_to);

    // Creating history record for userFrom
    $historyFrom = new History();
    $historyFrom->username = $userFrom->username;
    $historyFrom->status = $status;
    $userFrom->history()->associate($historyFrom);
    $historyFrom->save();

    // Creating history record for userTo
    $historyTo = new History();
    $historyTo->username = $userTo->username;
    $historyTo->status = $status;
    $userTo->history()->associate($historyTo);
    $userTo->save();

     }
     public function activeChat($userid){
      return   Chat::where('user_id_from',$userid)->where('status','1')->first()?? 
      Chat::where('user_id_to',$userid)->where('status','1')->first();
  }
}
