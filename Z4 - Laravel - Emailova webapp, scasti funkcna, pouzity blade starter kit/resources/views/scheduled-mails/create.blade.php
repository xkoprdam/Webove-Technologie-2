{{-- resources/views/scheduled-mails/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('New Scheduled Mail') }}
        </h2>
    </x-slot>

    <div class="p-4 max-w-2xl mx-auto space-y-6">
        <form method="POST" action="{{ route('scheduled-mails.store') }}" class="space-y-6">
            @csrf

            {{-- Template --}}
            <div>
                <x-input-label for="template_id" :value="__('Template')" />
                <select id="template_id" name="template_id"
                        class="mt-1 block w-full border-gray-300 rounded">
                    @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}"
                                {{ old('template_id')==$tpl->id?'selected':'' }}>
                            {{ $tpl->name }}
                        </option>
                    @endforeach
                </select>
                @error('template_id')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Recipients --}}
            <div>
                <x-input-label for="recipients" :value="__('Recipients')" />
                <select id="recipients" name="recipients[]" multiple
                        class="mt-1 block w-full border-gray-300 rounded">
                    @foreach($contacts as $c)
                        <option value="{{ $c->id }}"
                                {{ in_array($c->id, old('recipients', []))?'selected':'' }}>
                            {{ $c->name }} ({{ $c->email }})
                        </option>
                    @endforeach
                </select>
                @error('recipients')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Send At --}}
            <div>
                <x-input-label for="send_at" :value="__('Send At')" />
                <x-text-input id="send_at" name="send_at" type="datetime-local"
                              :value="old('send_at')" class="mt-1 w-full" required />
                @error('send_at')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex space-x-4">
                <x-primary-button>{{ __('Schedule') }}</x-primary-button>
                <a href="{{ route('scheduled-mails.index') }}"
                   class="underline text-gray-600">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>