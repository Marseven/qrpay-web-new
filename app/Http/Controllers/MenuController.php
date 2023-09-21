<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MenuController extends Controller
{
    //
    function getMenu(){
        return view("user.sections.menu.index",["page_title" => "menu du jour"]);
    }
}
