<?php

namespace App\Http\Controllers;

use App\Models\Plats;
use Illuminate\Http\Request;

class PlatsController extends Controller
{
    //
    public function create(Request $req){
        $plats = Plats::create([
            "titre" => $req->input("titre"),
            "image" => $req->input("image"),
            "menu_id" => $req->input("menu_id")
        ]);
        return $plats->all();
    }
}
