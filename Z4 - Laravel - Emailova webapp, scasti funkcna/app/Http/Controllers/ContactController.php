<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
//    public function index()
//    {
//       $contacts = auth()->user()->contacts()->get();
//       return view('dashboard', compact('contacts'));
//    }

   public function index(Request $request)
   {
      $query = auth()->user()->contacts();
      if ($request->filled('name')) {
         $query->where('name', 'like', '%' . $request->input('name') . '%');
      }
      $contacts = $query->get();
      return view('dashboard', compact('contacts'));
   }

//    /**
//     * Show the form for creating a new resource.
//     */
//    public function create()
//    {
//       return view('contacts.create');
//    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
       $data = $request->validate([
          'name'  => 'required|string|max:255',
          'email' => 'required|email|max:255',
       ]);
       // Handle the friend flag from checkbox
       $data['is_friend'] = $request->has('is_friend');
       $request->user()->contacts()->create($data);
       return redirect()->route('dashboard')->with('success', 'Contact added.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact)
    {
       return view('contacts.show', compact('contact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact)
    {
       return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact)
    {
       abort_unless($contact->user_id === $request->user()->id, 403);
       $data = $request->validate([
          'name'  => 'required|string|max:255',
          'email' => 'required|email|max:255',
       ]);
       // Handle the friend flag from checkbox
       $data['is_friend'] = $request->has('is_friend');
       $contact->update($data);
       return redirect()->route('dashboard')->with('success', 'Contact updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact)
    {
       abort_unless($contact->user_id === auth()->id(), 403);
       $contact->delete();
       return redirect()->route('dashboard')->with('success', 'Contact deleted.');
    }
}
