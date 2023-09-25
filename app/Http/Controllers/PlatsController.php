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
// https://cnou.jobs-conseil.host/plats/create?titre=banane2004&image=images123&menu_id=2
// https://cnou.jobs-conseil.host/palts/create?titre=menu&image=img&menu_id=1