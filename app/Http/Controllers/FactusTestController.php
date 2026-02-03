<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FactusService;

class FactusTestController extends Controller
{
    public function token(FactusService $factus)
    {
        $response = $factus->getToken();

        return $response->json();
    }
}
