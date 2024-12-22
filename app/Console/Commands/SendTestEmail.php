<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'email:test';
    protected $description = 'Send a test email to verify SMTP settings';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Email thử nghiệm
        $to = 'youngbuffalok2@gmail.com'; // Địa chỉ email để nhận thử nghiệm
        // $subject = 'Test Email';
        // $body = 'This is a test email sent from Laravel using Artisan Command.';

        // // Gửi email
        // Mail::raw($body, function ($message) use ($to, $subject) {
        //     $message->to($to)->subject($subject);
        // });

        $this->info('Test email sent to ' . $to);
        
    }
}
