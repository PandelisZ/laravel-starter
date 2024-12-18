<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Carbon\Carbon;

class TimeController extends Controller
{
    public function getTime()
    {
        return response()->json(['time' => Carbon::now()]);
    }
}
