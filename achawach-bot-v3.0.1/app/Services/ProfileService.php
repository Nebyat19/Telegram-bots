<?php

namespace App\Services;

use Illuminate\Http\Request;

class ProfileService{

    protected $userid;
function __construct(Request $request, $userid)
{
    $this->userid=$userid;
}

public function register(){
    
}



}