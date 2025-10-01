<?php

namespace App\Http\Controllers;

use App\Models\ScheduledMail;
use App\Models\Template;
use App\Models\Contact;
use App\Models\SentMail;
use App\Mail\BulkMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ScheduledMailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   public function index(Request $request)
   {
       $query = ScheduledMail::where('user_id', Auth::id());

       // Optional filters
       if ($request->filled('template_id')) {
          $query->where('template_id', $request->input('template_id'));
       }
       if ($request->filled('status')) {
          $query->where('status', $request->input('status'));
       }

       $scheduled = $query->orderBy('send_at')->get();
       return view('scheduled-mails.index', compact('scheduled'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       $templates = Template::where('user_id', Auth::id())->get();
       $contacts = Auth::user()->contacts;
       return view('scheduled-mails.create', compact('templates', 'contacts'));
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request)
   {
      $data = $request->validate([
         'template_id' => 'required|exists:templates,id',
         'send_at'     => 'required|date',
         'recipients'  => 'required|array',
         'recipients.*'=> 'exists:contacts,id',
      ]);

      $data['user_id']    = Auth::id();
      // recipients ako JSON pole contact_id
      $data['recipients'] = json_encode($data['recipients']);
      // nastavenie počiatočného stavu
      $data['status']     = 'pending';

      ScheduledMail::create($data);

      return redirect()
         ->route('scheduled-mails.index')
         ->with('success','Scheduled mail created.');
   }

    /**
     * Display the specified resource.
     */
    public function show(ScheduledMail $scheduledMail)
    {
       abort_unless($scheduledMail->user_id === Auth::id(), 403);
       return view('scheduled-mails.show', compact('scheduledMail'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ScheduledMail $scheduledMail)
    {
       abort_unless($scheduledMail->user_id === Auth::id(), 403);
       $templates = Template::where('user_id', Auth::id())->get();
       $contacts = Auth::user()->contacts;
       $scheduledMail->recipients = json_decode($scheduledMail->recipients, true);
       return view('scheduled-mails.edit', compact('scheduledMail','templates','contacts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ScheduledMail $scheduledMail)
    {
       abort_unless($scheduledMail->user_id === Auth::id(), 403);

       $data = $request->validate([
          'template_id' => 'required|exists:templates,id',
          'send_at'     => 'required|date',
          'recipients'  => 'required|array',
          'recipients.*'=> 'exists:contacts,id',
       ]);

       $data['recipients'] = json_encode($data['recipients']);
       $scheduledMail->update($data);

       return redirect()->route('scheduled-mails.index')
          ->with('success', 'Scheduled mail updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ScheduledMail $scheduledMail)
    {
       abort_unless($scheduledMail->user_id === Auth::id(), 403);
       $scheduledMail->delete();
       return redirect()->route('scheduled-mails.index')
          ->with('success', 'Scheduled mail deleted.');
    }


   public function sendNow(ScheduledMail $scheduledMail)
   {
      abort_unless($scheduledMail->user_id === Auth::id(), 403);

      $recipientIds = json_decode($scheduledMail->recipients, true);
      foreach ($recipientIds as $id) {
         if ($contact = Contact::find($id)) {
            Mail::to($contact->email)
               ->queue(new BulkMail($scheduledMail->template, $contact));

            $body = str_replace(
               ['{{ $contact->name }}', '{{ $contact->email }}'],
               [$contact->name, $contact->email],
               $scheduledMail->template->is_html
                  ? $scheduledMail->template->body_html
                  : $scheduledMail->template->body_text
            );

            SentMail::create([
               'user_id'     => Auth::id(),
               'template_id' => $scheduledMail->template_id,
               'recipients'  => json_encode([$contact->id]),
               'sent_at'     => now(),
               'status'      => $body,
            ]);
         }
      }

      return redirect()->route('scheduled-mails.index')
         ->with('success', 'Scheduled mail sent.');
   }

    /**
     * Duplicate a scheduled mail and set its status to pending.
     */
    public function copy(ScheduledMail $scheduledMail)
    {
        abort_unless($scheduledMail->user_id === Auth::id(), 403);

        $new = $scheduledMail->replicate(['status', 'send_at', 'created_at', 'updated_at']);
        $new->status = 'pending';
        $new->send_at = now()->addHour();
        $new->save();

        return redirect()->route('scheduled-mails.edit', $new)
            ->with('success', 'Scheduled mail copied! Please set the send time and recipients.');
    }

}
