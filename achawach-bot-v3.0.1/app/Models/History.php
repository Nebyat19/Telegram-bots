<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class History extends Model
{
    use HasFactory;
   public $table='history';
    public function user(){

        return $this->belongsTo(User::class,'user_id');
    }

   /* public function chat(){
  return $this->belongsTo(Chat::class,)
    }

    */
}
