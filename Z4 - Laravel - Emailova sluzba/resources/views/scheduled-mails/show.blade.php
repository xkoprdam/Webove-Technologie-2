{{-- resources/views/scheduled-mails/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Scheduled Mail Details') }}
        </h2>
    </x-slot>

    <div class="p-4 space-y-4">
        <p><strong>{{ __('Template:') }}</strong> {{ $scheduledMail->template->name }}</p>
        <p><strong>{{ __('Send At:') }}</strong> {{ $scheduledMail->send_at }}</p>
        <p><strong>{{ __('Status:') }}</strong> {{ ucfirst($scheduledMail->status) }}</p>
        <p><strong>{{ __('Recipients:') }}</strong></p>
        <ul class="list-disc list-inside">
            @php
                $recipients = is_array($scheduledMail->recipients)
                    ? $scheduledMail->recipients
                    : (json_decode($scheduledMail->recipients, true) ?? []);
            @endphp
            @foreach($recipients as $id)
                @php $c = \App\Models\Contact::find($id) @endphp
                <li>{{ $c?->name }} &lt;{{ $c?->email }}&gt;</li>
            @endforeach
        </ul>

        <div class="pt-4 border-t flex space-x-4">
            <form action="{{ route('scheduled-mails.sendNow', $scheduledMail) }}" method="POST">
                @csrf
                <x-primary-button>{{ __('Send Now') }}</x-primary-button>
            </form>
            <a href="{{ route('scheduled-mails.edit', $scheduledMail) }}"
               class="text-yellow-600 hover:underline">{{ __('Edit') }}</a>
            <form action="{{ route('scheduled-mails.destroy', $scheduledMail) }}"
                  method="POST">
                @csrf @method('DELETE')
                <button type="submit"
                        class="text-red-600 hover:underline"
                        onclick="return confirm('{{ __('Are you sure?') }}')">
                    {{ __('Delete') }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>