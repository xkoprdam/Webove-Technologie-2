{{-- resources/views/contacts/table.blade.php --}}

<div class="container mx-auto p-4">
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter podľa mena --}}
    <form method="GET" action="{{ route('contacts.index') }}" class="mb-4">
        <div class="flex items-end space-x-2">
            <div class="flex-1">
                <x-input-label for="name" :value="__('Filter by name')" />
                <x-text-input id="name"
                              class="mt-1 w-full"
                              type="text"
                              name="name"
                              :value="request('name')"
                              placeholder="Enter name…" />
            </div>
            <div class="mt-6 flex space-x-2">
                <x-primary-button>{{ __('Filter') }}</x-primary-button>
                <a href="{{ route('contacts.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                    {{ __('Clear') }}
                </a>
            </div>
        </div>
    </form>


    @if($contacts->isEmpty())
        <p class="text-gray-600 dark:text-gray-400">{{ __('You have no contacts yet.') }}</p>
    @else
        <div class="shadow overflow-hidden border-b border-gray-200 dark:border-gray-700 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Name') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Email') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Friend') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('Actions') }}
                    </th>
                </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($contacts as $contact)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $contact->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $contact->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900 dark:text-gray-100">
                            {{ $contact->is_friend ? __('Yes') : __('No') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-4">
                            <a href="{{ route('contacts.edit', $contact) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200">
                                {{ __('Edit') }}
                            </a>
                            <form action="{{ route('contacts.destroy', $contact) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('{{ __('Delete this contact?') }}')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-200">
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
