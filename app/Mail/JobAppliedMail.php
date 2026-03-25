<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobAppliedMail extends Mailable
{
use Queueable, SerializesModels;

public $applicant;
public $job;
public $company;

public function __construct($applicant, $job, $company)
{
$this->applicant = $applicant;
$this->job = $job;
$this->company = $company;
}

public function build()
{
return $this->subject('New Job Application Received')
->view('emails.job_applied');
}
}