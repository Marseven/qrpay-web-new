<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Plats;
use Illuminate\Http\Request;
use Stripe\Plan;

class MenuController extends Controller
{
    //
    function getMenu(){
        $menu = new Menu();
        $plats = new Plats();
        $menus = Menu::find(1);
        dd($menus->plats[0]->titre);
        return view("user.sections.menu.index",["menus" => $menu->all(),"plats" => $plats->all()]);
    }
    public function create(Request $req){
        $menu = Menu::create([ 
            "titre" => $req->input("titre"),
            "disponible" => $req->input("disponible"),
            "note" => $req->input("note"),
            "debut" => $req->input("debut"),
            "fin" => $req->input("fin"),
            "restaurant" => $req->input("restaurant")
        ]);
        return $menu->all();
    }

}
