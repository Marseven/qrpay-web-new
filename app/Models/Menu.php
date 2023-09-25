<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $fillable = ["titre","disponible","note","debut","fin","restaurant"];

    public function plats() {
        return $this->hasMany(Plats::class);
    }
}
