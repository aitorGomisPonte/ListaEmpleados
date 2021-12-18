<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaPassword extends Mailable
{
    use Queueable, SerializesModels;
    public $asunto;
    public $dato;
    public $titulo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($asunto, $titulo, $dato)
    {
        $this->asunto = $asunto;
        $this->titulo = $titulo;
        $this->dato = $dato;
       

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email');
    }
}
