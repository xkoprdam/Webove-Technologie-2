{{-- resources/views/templates/show.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Preview Template') }}
        </h2>
    </x-slot>
    <div class="container mx-auto p-4 max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold">{{ $template->name }}</h1>
        <p class="text-gray-700"><strong>Subject:</strong> {{ $template->subject }}</p>

        @php
            $dummy = (object)[
              'name' => 'John Doe',
              'email' => 'john@example.com',
            ];
        @endphp

        <section class="prose dark:prose-dark">
            @if($template->is_html)
                {!! str_replace(
                     ['{{ $contact->name }}', '{{ $contact->email }}' ],
                     [ $dummy->name,         $dummy->email         ],
                     $template->body_html
                ) !!}
            @else
                <pre>
                    {!! str_replace(
                        ['{{ $contact->name }}', '{{ $contact->email }}'],
                        [$dummy->name, $dummy->email],
                        $template->body_text
                    ) !!}
                </pre>
            @endif
        </section>
        
{{--        <form action="{{ route('templates.sendNow', $template) }}" method="POST">--}}
{{--            @csrf--}}
{{--            <x-primary-button>{{ __('Send to all contacts') }}</x-primary-button>--}}
{{--        </form>--}}
    </div>
</x-app-layout>