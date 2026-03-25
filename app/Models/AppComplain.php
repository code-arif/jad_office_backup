<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppComplain extends Model
{
    protected $fillable = ['user_id','subjets','comments'];
}
