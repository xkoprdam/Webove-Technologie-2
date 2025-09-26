{{-- resources/views/contacts/create-contact-form.blade.php --}}

<form method="POST" action="{{ route('contacts.store') }}" class="space-y-6">
    @csrf

    {{--     Name--}}
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input
                id="name"
                class="block mt-1 w-full"
                type="text"
                name="name"
                :value="old('name')"
                required
                autofocus
        />
        @error('name')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{--     Email--}}
    <div>
        <x-input-label for="email" :value="__('Email')" />
        <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                required
        />
        @error('email')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Friend flag --}}
    <div class="block mt-4">
        <label for="is_friend" class="inline-flex items-center">
            <input id="is_friend"
                   type="checkbox"
                   name="is_friend"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    {{ old('is_friend') ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-gray-600">{{ __('Friend') }}</span>
        </label>
        @error('is_friend')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>


    <div class="flex items-center gap-4">
        <x-primary-button>
            {{ __('Add Contact') }}
        </x-primary-button>

        <a href="{{ route('dashboard') }}" class="underline text-sm text-gray-600 hover:text-gray-900">
            {{ __('Cancel') }}
        </a>
    </div>
</form>
