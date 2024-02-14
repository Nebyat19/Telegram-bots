<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Routing\Middleware\ThrottleRequests;

class CustomThrottleRequests extends ThrottleRequests
{
    public function  handle($request, Closure $next, $maxAttempts = 4000, $decayMinutes = 1, $prefix = '')
    {
        // Your custom rate limiting logic here

        // To allow 1000 requests per minute for each client:
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = 4000;
        $decayMinutes = 1;

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
  
}
