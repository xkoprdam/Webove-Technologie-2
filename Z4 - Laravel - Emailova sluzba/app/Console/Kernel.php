<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\ScheduledMail;
use App\Mail\BulkMail;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;

class Kernel extends ConsoleKernel
{
   protected function schedule(Schedule $schedule)
   {
      $schedule->call(function () {
         $jobs = ScheduledMail::where('status','pending')
            ->where('send_at','<=', now())
            ->get();

         foreach ($jobs as $job) {
            $recipientIds = json_decode($job->recipients, true);
            foreach ($recipientIds as $id) {
               if ($contact = Contact::find($id)) {
                  Mail::to($contact->email)
                     ->queue(new BulkMail($job->template, $contact));
               }
            }
            $job->update(['status'=>'sent']);
         }
      })->everyMinute();
   }

   protected function commands()
   {
      $this->load(__DIR__.'/Commands');
   }
}