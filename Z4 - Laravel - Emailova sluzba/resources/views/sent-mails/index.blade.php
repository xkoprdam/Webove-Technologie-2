<x-app-layout>
    <x-slot name="header">
        <h2>{{ __('Sent Mail History') }}</h2>
    </x-slot>

    <div class="p-4">
        {{-- Filtr podle stavu nebo šablony --}}
        <form method="GET" class="mb-4 flex space-x-2">
            <select name="status" class="border rounded p-1">
                <option value="">{{ __('Any status') }}</option>
                <option value="sent"    {{ request('status')=='sent'?'selected':'' }}>{{ __('Sent') }}</option>
                <option value="failed"  {{ request('status')=='failed'?'selected':'' }}>{{ __('Failed') }}</option>
            </select>
            <select name="template_id" class="border rounded p-1">
                <option value="">{{ __('Any template') }}</option>
                @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}"
                            {{ request('template_id')==$tpl->id?'selected':'' }}>
                        {{ $tpl->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded">{{ __('Filter') }}</button>
            <a href="{{ route('sent-mails.index') }}"
               class="px-3 py-1 bg-gray-200 rounded">{{ __('Clear') }}</a>
        </form>

        @if($sentMails->isEmpty())
            <p>{{ __('No sent mails yet.') }}</p>
        @else
            <table class="min-w-full bg-white shadow rounded">
                <thead>
                <tr>
                    <th class="px-4 py-2">{{ __('Date') }}</th>
                    <th class="px-4 py-2">{{ __('Template') }}</th>
                    <th class="px-4 py-2">{{ __('Recipient Email') }}</th>
                    <th class="px-4 py-2">{{ __('Actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($sentMails as $mail)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $mail->sent_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2">{{ $mail->template->name }}</td>
                        <td class="px-4 py-2">
                            @php
                                $recipientIds = json_decode($mail->recipients, true);
                                $recipientEmail = \App\Models\Contact::find($recipientIds[0])->email ?? '';
                            @endphp
                            {{ $recipientEmail }}
                        </td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('sent-mails.show', $mail) }}" class="text-blue-600">{{ __('View') }}</a>
                            <form action="{{ route('sent-mails.reschedule', $mail) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600">{{ __('Copy as Scheduled') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>