<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User\User;
class Filter extends Model
{
    use HasFactory;

    public $table='filters';

    public function user(){

        return $this->belongsTo(User::class,'user_id');
    }
}
