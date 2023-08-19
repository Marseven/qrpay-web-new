<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $table = "tickets";
    protected $guarded = ['id'];
    protected $casts = [
        'admin_id' => 'integer',
        'label' => 'string',
        'description' => 'text',
        'price' => 'double',
        'status' => 'integer',
    ];

    protected $appends = [
        'editData',
    ];
    public function getEditDataAttribute()
    {

        $data = [
            'id'      => $this->id,
            'label'      => $this->label,
            'description'  => $this->description,
            'price'      => $this->price,
            'status'      => $this->status,
        ];

        return json_encode($data);
    }
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', false);
    }

    public function scopeSearch($query, $text)
    {
        $query->Where("label", "like", "%" . $text . "%");
    }
}
