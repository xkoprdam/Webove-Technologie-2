<?php

use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ScheduledMailController;
use App\Http\Controllers\SentMailController;


use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [ContactController::class, 'index'])
   ->middleware(['auth', 'verified'])
   ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
   Route::resource('contacts', ContactController::class)
      ->only(['index', 'store', 'edit', 'update', 'destroy']);

   Route::resource('templates', TemplateController::class);
//   Route::post('templates/{template}/send-now', [TemplateController::class, 'sendNow'])
//      ->name('templates.sendNow');
   Route::post('templates/{template}/copy', [TemplateController::class, 'copy'])
      ->name('templates.copy');

   Route::resource('scheduled-mails', ScheduledMailController::class);
   Route::post('scheduled-mails/{scheduled_mail}/send-now', [ScheduledMailController::class,'sendNow'])
      ->name('scheduled-mails.sendNow');
   Route::post('scheduled-mails/{scheduled_mail}/copy', [ScheduledMailController::class, 'copy'])
      ->name('scheduled-mails.copy');

   // Historie odeslaných mailů
   Route::resource('sent-mails', SentMailController::class)
      ->only(['index','show']);

   // Kopírovat jako nový naplánovaný
   Route::post('sent-mails/{sent_mail}/reschedule',
      [SentMailController::class,'reschedule'])
      ->name('sent-mails.reschedule');
});

require __DIR__.'/auth.php';
