<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SentMail extends Model
{
   use HasFactory;

   protected $fillable = [
      'user_id',
      'template_id',
      'recipients',
      'sent_at',
      'status',
   ];

   /** JSON → array */
   protected $casts = [
      'recipients' => 'array',
      'sent_at'    => 'datetime',
   ];

   /** Vlastník (user) */
   public function user()
   {
      return $this->belongsTo(User::class);
   }

   /** Použitá šablona */
   public function template()
   {
      return $this->belongsTo(Template::class);
   }
}