<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Mail\BulkMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Template::where('user_id', Auth::id());
        if ($request->filled('subject')) {
            $query->where('subject', 'like', '%' . $request->input('subject') . '%');
        }
        $templates = $query->get();
        return view('templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'subject'   => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'is_html'   => 'boolean',
            'attachments' => 'nullable|array',
        ]);
        // Ensure is_html is cast correctly from the checkbox
        $data['is_html'] = $request->has('is_html');
        $data['user_id'] = Auth::id();
        Template::create($data);
        return redirect()->route('templates.index')->with('success', 'Template created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Template $template)
    {
        return view('templates.show', compact('template'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Template $template)
    {
        return view('templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Template $template)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'subject'   => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'is_html'   => 'boolean',
            'attachments' => 'nullable|array',
        ]);
        $data['is_html'] = $request->has('is_html');
        $template->update($data);
        return redirect()->route('templates.index')->with('success', 'Template updated.');
    }


   public function sendNow(Template $template)
   {
      $contacts = auth()->user()->contacts;

      foreach ($contacts as $contact) {
         Mail::to($contact->email)
            ->queue(new BulkMail($template, $contact));
      }

      return redirect()->route('templates.index')
         ->with('success', 'Maily odeslány: ' . $contacts->count());
   }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Template $template)
    {
        $template->delete();
        return redirect()->route('templates.index')->with('success', 'Template deleted.');
    }

    /**
     * Copy a template and redirect to edit.
     */
    public function copy(Template $template)
    {
        abort_unless($template->user_id === Auth::id(), 403);

        $new = $template->replicate(['created_at', 'updated_at']);
        $new->name = $template->name . ' (Copy)';
        $new->save();

        return redirect()->route('templates.edit', $new)
            ->with('success', 'Template copied! You can now edit it.');
    }
}
