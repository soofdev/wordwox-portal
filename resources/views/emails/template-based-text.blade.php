{{ $fromName ?? config('app.name') }}
{{ str_repeat('=', strlen($fromName ?? config('app.name'))) }}

{!! $textData !!}

---

© {{ date('Y') }} {{ $orgName ?? config('app.name') }}. All rights reserved.

@if($templateId)
Template ID: {{ $templateId }}
@endif






