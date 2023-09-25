<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plats extends Model
{
    use HasFactory;
    protected $fillable = ["titre","image","menu_id"];

    public function menus() {
        return $this->belongsTo(Menu::class);
    }
}
