<x-mail::message>
# {{ $contactMessage->subject }}

**{{ $contactMessage->name }}** &lt;{{ $contactMessage->email }}&gt;
{{ $contactMessage->created_at->format('d/m/Y H:i') }}

{{ $contactMessage->message }}

<x-mail::button :url="route('dashboard.messages')">
{{ __('portfolio.admin.messages') }}
</x-mail::button>
</x-mail::message>
