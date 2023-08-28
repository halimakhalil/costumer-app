<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class error extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable = [

        'errors',
        'customer_id',
        'User_id',

    ];
}
