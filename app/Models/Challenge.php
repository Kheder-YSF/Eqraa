<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;
    protected $fillable = ['name','end_date','black_list','books'];
    protected $casts = [
        'end_date'=>'datetime',
        'black_list'=>'array',
        'books'=>'array'
    ];
    public function users() {
        return $this->belongsToMany(User::class);
    }
}
