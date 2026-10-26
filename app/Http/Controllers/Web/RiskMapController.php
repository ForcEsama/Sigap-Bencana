<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RiskMapController extends Controller
{
    public function index(): View
    {
        return view('risk-map.index');
    }
}
