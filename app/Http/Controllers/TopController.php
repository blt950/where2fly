<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Airport;

class TopController extends Controller
{
    
    public function index(){
        $airports = Airport::orderBy('total_score', 'DESC')->limit(20)->get();
        return view('top', compact('airports'));
    }

}
