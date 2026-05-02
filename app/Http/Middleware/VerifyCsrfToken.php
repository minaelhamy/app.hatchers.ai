<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        '*/service/timeslot',
        '*/service/slotlimit',
        '*/service/stafflimit',
        '*/service/booking',
    ];
}
