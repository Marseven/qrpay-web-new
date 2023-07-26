<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardApi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'admin_id' => 'integer',
        'secret_key' => 'string',
        'secret_hash' => 'string',
        'url' => 'string',
        'card_details' => 'string',
    ];
}
