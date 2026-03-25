<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterOtpMail extends Mailable
{
    use SerializesModels;

    public $otp , $user_name;

    public function __construct($otp , $user_name)
    {
       $this->otp = $otp;
       $this->user_name = $user_name;
    }

    public function build()
    {
        return $this->subject('Your OTP for Email Verification')
                    ->view('mail.verifyEmail')
                    ->with(['otp' => $this->otp , 'user_name' => $this->user_name]);
    }
}
