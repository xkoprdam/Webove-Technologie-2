{{-- resources/views/templates/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('New Template') }}
        </h2>
    </x-slot>

    <div class="container mx-auto p-4 max-w-2xl">
        <h1 class="text-2xl font-bold mb-4">New Template</h1>

        <form method="POST" action="{{ route('templates.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

{{--            <div x-data="{ editorType: '{{ old('is_html', true) ? 'html' : 'text' }}' }">--}}
            <div x-data="{ editorType: '{{ old('is_html', false) ? 'html' : 'text' }}' }">
                {{-- Name --}}
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" :value="old('name')" required autofocus />
                    @error('name')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Subject --}}
                <div>
                    <x-input-label for="subject" :value="__('Subject')" />
                    <x-text-input id="subject" name="subject" type="text" :value="old('subject')" required />
                    @error('subject')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Editor Type --}}
                <div class="mb-4">
                    <x-input-label for="editor_type" :value="__('Editor Type')" />
                    <select id="editor_type"
                            name="editor_type"
                            x-model="editorType"
                            class="mt-1 block w-full border-gray-300 rounded shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="html">{{ __('HTML') }}</option>
                        <option value="text">{{ __('Plain Text') }}</option>
                    </select>
                    {{-- Hidden is_html flag --}}
                    <input type="hidden" name="is_html" :value="editorType === 'html' ? 1 : 0">
                </div>

                <div x-show="editorType === 'html'" x-cloak>
                    <x-input-label for="body_html" :value="__('HTML Body')" />
                    <trix-editor input="body_html"></trix-editor>
                    <input id="body_html" type="hidden" name="body_html" value="{{ old('body_html') }}">
                    @error('body_html')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div x-show="editorType === 'text'" x-cloak>
                    <x-input-label for="body_text" :value="__('Plain-text Body')" />
                    <textarea id="body_text"
                              name="body_text"
                              rows="4"
                              class="block w-full rounded border-gray-300">{{ old('body_text') }}</textarea>
                    @error('body_text')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Attachments --}}
                <div>
                    <x-input-label for="attachments" :value="__('Attachments')" />
                    <input type="file" name="attachments[]" id="attachments" multiple>
                    @error('attachments')<p class="text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="flex space-x-4">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('templates.index') }}" class="underline text-gray-600">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>