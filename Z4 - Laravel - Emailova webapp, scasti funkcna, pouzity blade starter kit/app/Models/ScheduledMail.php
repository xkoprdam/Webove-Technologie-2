<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Template;

class ScheduledMail extends Model
{
   use HasFactory;

   protected $fillable = [
      'user_id',
      'template_id',
      'recipients',
      'send_at',
      'status',
   ];

   protected $casts = [
      'recipients' => 'array',
      'send_at'    => 'datetime',
   ];

   // Relation to the owning user
   public function user()
   {
      return $this->belongsTo(User::class);
   }

   // Relation to the template
   public function template()
   {
      return $this->belongsTo(Template::class);
   }
}
