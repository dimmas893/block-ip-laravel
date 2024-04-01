<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IpController extends Controller
{
    public function cekip(Request $request){
        $ip = $request->ip();
        return $ip;
    }
}
