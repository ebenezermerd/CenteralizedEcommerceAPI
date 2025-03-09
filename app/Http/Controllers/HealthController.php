<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class HealthController extends Controller
{
    public function check()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => Carbon::now(),
            'environment' => config('app.env'),
            'service' => config('app.name')
        ]);
    }
}
