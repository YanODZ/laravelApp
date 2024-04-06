<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExampleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $code;

    public function __construct($name, $code)
    {
        $this->name = $name;
        $this->code = $code;
    }

    public function build()
    {
        return $this->from('amlovers@amlovers.online.mx')
                    ->view('emails.example')
                    ->with([
                        'name' => $this->name,
                        'code' => $this->code,
                    ]);
    }
}
