<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    protected $fillable = ['title','image','status'];


    protected $hidden = ['created_at' , 'image', 'updated_at' , 'status'];

    
}
