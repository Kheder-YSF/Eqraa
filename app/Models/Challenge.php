<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;
    protected $fillable = ['name','end_date','published'];
    protected $casts = [
        'end_date'=>'datetime',
    ];
    public function users() {
        return $this->belongsToMany(User::class);
    }
    public function badges() {
        return $this->hasMany(Badge::class);
    }
    public function books() {
        return $this->belongsToMany(Book::class);
    }
}
