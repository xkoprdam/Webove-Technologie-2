{{-- resources/views/templates/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Templates') }}
        </h2>
    </x-slot>
    <div class="container mx-auto p-4">
        <header class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold">Templates</h1>
            <a href="{{ route('templates.create') }}"
               class="px-4 py-2 bg-green-600 text-gray-800 dark:text-gray-200 rounded hover:bg-green-700">
                Add Template
            </a>
        </header>

        {{-- Filter podľa subjectu --}}
        <form method="GET" action="{{ route('templates.index') }}" class="mb-4">
            <div class="flex items-end space-x-2">
                <div class="flex-1">
                    <x-input-label for="subject" :value="__('Filter by subject')" />
                    <x-text-input id="subject"
                                  class="mt-1 w-full"
                                  type="text"
                                  name="subject"
                                  :value="request('subject')"
                                  placeholder="Enter subject…" />
                </div>
                <div class="mt-6 flex space-x-2">
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('templates.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                        {{ __('Clear') }}
                    </a>
                </div>
            </div>
        </form>

        @if($templates->isEmpty())
            <p class="text-gray-600">No templates found.</p>
        @else
            <table class="min-w-full bg-white shadow rounded">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Subject</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2 text-right">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($templates as $tpl)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $tpl->name }}</td>
                        <td class="px-4 py-2">{{ $tpl->subject }}</td>
                        <td class="px-4 py-2">{{ $tpl->is_html ? 'HTML' : 'Plain text' }}</td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('templates.show', $tpl) }}" class="text-blue-600">Preview</a>
                            <form action="{{ route('templates.copy', $tpl) }}"
                                  method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="text-blue-700 hover:underline"
                                        onclick="return confirm('{{ __('Copy this template?') }}')">
                                    {{ __('Copy') }}
                                </button>
                            </form>
                            <a href="{{ route('templates.edit', $tpl) }}" class="text-yellow-600">Edit</a>
                            <form action="{{ route('templates.destroy', $tpl) }}"
                                  method="POST" class="inline" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-app-layout>