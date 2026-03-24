Your Doomshop download links
============================

Hi{{ isset($name) && $name !== '' ? ' ' . $name : '' }},

Thanks for your purchase! Use the links below to download your full audio files.

@if (!empty($items))
@foreach ($items as $item)
- {{ ucfirst($item['type'] ?? 'item') }}: {{ $item['title'] ?? '' }}
  Download: {{ $item['url'] ?? '' }}
@endforeach
@endif

Links valid until: {{ $expires_at ?? '' }}

If you did not request this email, you can safely ignore it.
