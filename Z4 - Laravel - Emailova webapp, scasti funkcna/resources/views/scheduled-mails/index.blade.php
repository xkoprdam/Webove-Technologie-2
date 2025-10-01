{{-- resources/views/scheduled-mails/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Scheduled Mails') }}
        </h2>
    </x-slot>

    <div class="p-4 space-y-6">
        <div class="flex justify-end mb-4">
            <a href="{{ route('scheduled-mails.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                {{ __('Create New Scheduled Mail') }}
            </a>
        </div>
        {{-- Filters --}}
        <form method="GET" class="flex space-x-2 mb-4">
{{--            <label for="template_id" value="Template" />--}}
            <select id="template_id" name="template_id" class="border rounded p-1">
                <option value="">{{ __('All') }}</option>
                @foreach(Auth::user()->templates as $tpl)
                    <option value="{{ $tpl->id }}" {{ request('template_id')==$tpl->id ? 'selected' : '' }}>
                        {{ $tpl->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="border rounded p-1">
                <option value="">{{ __('All') }}</option>
                <option value="pending" {{ request('status')=='pending'?'selected':'' }}>{{ __('Pending') }}</option>
                <option value="sent"    {{ request('status')=='sent'   ?'selected':'' }}>{{ __('Sent') }}</option>
            </select>
            <x-primary-button>{{ __('Filter') }}</x-primary-button>
            <a href="{{ route('scheduled-mails.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                {{ __('Clear') }}
            </a>
        </form>

        @if($scheduled->isEmpty())
            <p class="text-gray-600">{{ __('No scheduled mails found.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white shadow rounded">
                    <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2">{{ __('Send At') }}</th>
                        <th class="px-4 py-2">{{ __('Template') }}</th>
                        <th class="px-4 py-2">{{ __('Recipients') }}</th>
                        <th class="px-4 py-2">{{ __('Status') }}</th>
                        <th class="px-4 py-2 text-right">{{ __('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($scheduled as $job)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $job->send_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2">{{ $job->template->name }}</td>
                            <td class="px-4 py-2">{{ count(is_array($job->recipients) ? $job->recipients : json_decode($job->recipients, true) ?? []) }}</td>                            <td class="px-4 py-2">{{ ucfirst($job->status) }}</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <a href="{{ route('scheduled-mails.show', $job) }}"
                                   class="text-blue-600 hover:underline">{{ __('View') }}</a>
                                <a href="{{ route('scheduled-mails.edit', $job) }}"
                                   class="text-yellow-600 hover:underline">{{ __('Edit') }}</a>
                                <form action="{{ route('scheduled-mails.sendNow', $job) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="text-green-600 hover:underline"
                                            onclick="return confirm('{{ __('Send now?') }}')">
                                        {{ __('Send Now') }}
                                    </button>
                                </form>
                                <form action="{{ route('scheduled-mails.copy', $job) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="text-blue-700 hover:underline"
                                            onclick="return confirm('{{ __('Copy this schedule?') }}')">
                                        {{ __('Copy') }}
                                    </button>
                                </form>
                                <form action="{{ route('scheduled-mails.destroy', $job) }}"
                                      method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:underline"
                                            onclick="return confirm('{{ __('Delete this schedule?') }}')">
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>