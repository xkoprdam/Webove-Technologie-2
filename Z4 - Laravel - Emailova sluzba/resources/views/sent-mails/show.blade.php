<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Sent Mail Details') }}</h2>
    </x-slot>

    <div class="p-4 space-y-4">
        <p><strong>{{ __('Date:') }}</strong> {{ $sentMail->sent_at }}</p>
        <p><strong>{{ __('Template:') }}</strong> {{ $sentMail->template->name }}</p>
        <p><strong>{{ __('Recipients:') }}</strong></p>
        @php
            $recipientIds = is_array($sentMail->recipients)
                ? $sentMail->recipients
                : (json_decode($sentMail->recipients, true) ?? []);
        @endphp
        <ul class="list-disc list-inside">
            @foreach($recipientIds as $contactId)
                @php $c = \App\Models\Contact::find($contactId) @endphp
                <li>{{ $c?->email }}</li>
            @endforeach
        </ul>
        <p><strong>{{ __('Body:') }}</strong></p>
        <pre>{{ $sentMail->status }}</pre>
        <div class="pt-4 border-t">
            <form action="{{ route('sent-mails.reschedule', $sentMail) }}" method="POST">
                @csrf
                <x-primary-button>{{ __('Copy as New Scheduled') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>