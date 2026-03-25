<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobSearch extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'query'];
}
