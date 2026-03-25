<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSave extends Model
{
    protected $fillable = ['employee_id','job_id'];
    
    protected $hidden = ['created_at','updated_at'];
    
    public function job()
    {
        return $this->belongsTo(CompanyJob::class, 'job_id');
    }

  
}
