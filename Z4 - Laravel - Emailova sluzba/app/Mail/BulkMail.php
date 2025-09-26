<?php

namespace App\Mail;

use App\Models\Template;
use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BulkMail extends Mailable
{
   use Queueable, SerializesModels;

   public $template;
   public $contact;
   protected $body; // zpracovaný HTML/text

   /**
    * Create a new message instance.
    */
   public function __construct(Template $template, Contact $contact)
   {
      $this->template = $template;
      $this->contact  = $contact;

      // Nahraď placeholdery ve šabloně
      $raw = $template->is_html
         ? $template->body_html
         : $template->body_text;

      $this->body = str_replace(
         ['{{ $contact->name }}', '{{ $contact->email }}'],
         [$contact->name, $contact->email],
         $raw
      );
   }

   /**
    * Build the message.
    */
   public function build()
   {
      $m = $this->subject($this->template->subject);

      if ($this->template->is_html) {
         // HTML mail
         $m->html($this->body);
      } else {
         // Plain-text mail
         $m->text('emails.plain', ['body' => $this->body]);
      }

      return $m;
   }
}