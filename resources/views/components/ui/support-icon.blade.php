@props(['name'])

@switch($name)
    @case('help')
        <svg
            {{ $attributes->class(['h-5 w-5']) }}
            data-support-icon="help"
            aria-hidden="true"
            focusable="false"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.8"
        >
            <circle cx="12" cy="12" r="9" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9a2.25 2.25 0 1 1 3.7 1.73C12.57 11.43 12 11.79 12 13" />
            <path stroke-linecap="round" d="M12 16.25h.01" />
        </svg>
        @break

    @case('feedback')
    @case('my-feedback')
        <svg
            {{ $attributes->class(['h-5 w-5']) }}
            data-support-icon="{{ $name }}"
            aria-hidden="true"
            focusable="false"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.8"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 18.25H5.75a2 2 0 0 1-2-2V6.75a2 2 0 0 1 2-2h12.5a2 2 0 0 1 2 2v9.5a2 2 0 0 1-2 2h-6.5L7 21v-2.75Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.25 13.5.4-2 4.94-4.94a1.06 1.06 0 0 1 1.5 1.5L11.15 13l-1.9.5Z" />
        </svg>
        @break

    @case('testimonial')
        <svg
            {{ $attributes->class(['h-5 w-5']) }}
            data-support-icon="testimonial"
            aria-hidden="true"
            focusable="false"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="1.8"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5.75A2.75 2.75 0 0 1 7.75 3h8.5A2.75 2.75 0 0 1 19 5.75v8.5A2.75 2.75 0 0 1 16.25 17H12l-4.5 4v-4.25A2.75 2.75 0 0 1 5 14.25v-8.5Z" />
            <path stroke-linecap="round" d="M8.5 8.5h7M8.5 12h4.5" />
        </svg>
        @break
@endswitch
