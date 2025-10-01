<?php

namespace App\Http\Controllers;

use App\Models\SentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SentMailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
   {
      $query = SentMail::where('user_id', Auth::id());

      // volitelné filtrování podle template nebo status
      if ($request->filled('template_id')) {
         $query->where('template_id', $request->input('template_id'));
      }
      if ($request->filled('status')) {
         $query->where('status', $request->input('status'));
      }

      $sentMails = $query->orderBy('sent_at','desc')->get();

      $templates = Auth::user()->templates;

      return view('sent-mails.index', compact('sentMails','templates'));
   }

   public function show(SentMail $sentMail)
   {
      // Ověření vlastnictví
      abort_unless($sentMail->user_id === Auth::id(), 403);
      return view('sent-mails.show', compact('sentMail'));
   }

   /**
    * Volitelná metoda pro „copy as new scheduled“
    */
   public function reschedule(SentMail $sentMail)
   {
      abort_unless($sentMail->user_id === Auth::id(), 403);

      // Předvyplníme scheduled-mail form
      $recipients = $sentMail->recipients;    // už to je array díky $casts
      return redirect()
         ->route('scheduled-mails.create', [
            'template_id' => $sentMail->template_id,
            'recipients'  => $recipients,
         ]);
   }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SentMail $sentMail)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SentMail $sentMail)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SentMail $sentMail)
    {
        //
    }
}
