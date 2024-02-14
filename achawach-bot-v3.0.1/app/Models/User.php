<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

 
    public $table='users';


    public function filter(){

        return $this->HasOne(Filter::class,'user_id');
    }

    public function rate(){
        return $this->hasMany(Rate::class,'rater_id'); //rater_id
    }
    public function myRate(){
        return $this->hasMany(Rate::class,'user_id'); //
    }
public function history(){
        return $this->hasMany(History::class,'user_id');
}
public function payment(){
    return $this->hasMany(Payment::class, 'user_id');
}
    public function toId($userid){

        return   Chat::where('user_id_from',$userid)->where('status','1')->first()->user_id_to?? 
                 Chat::where('user_id_to',$userid)->where('status','1')->first()->user_id_from;
       }
       function addHistory($username, $status){
        $history=new History;
        $this->associate($history);
        $history->username=$username;
        $history->status=$status;
        $history->save();

       }
       function message(){
        return $this->hasMany(Message::class,'user_id');
       }
}
