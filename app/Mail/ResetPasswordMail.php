<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $username;

    public function __construct($token, $username)
    {
        $this->token = $token;
        $this->username = $username;
    }

    public function build()
    {
        return $this->view('emails.password')
                    ->subject('Đặt lại mật khẩu')
                    ->with(['token' => $this->token, 'username', $this->username]);
    }
}
