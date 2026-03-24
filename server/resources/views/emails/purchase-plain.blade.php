Letoltesi linkek
================

Szia{{ isset($name) && $name !== '' ? ' ' . $name : '' }}!

Koszonjuk a vasarlast! Az alabbi linkekkel tudod letolteni a teljes hanganyagot.

@if (!empty($items))
@foreach ($items as $item)
- {{ ucfirst($item['type'] ?? 'item') }}: {{ $item['title'] ?? '' }}
  Letoltes: {{ $item['url'] ?? '' }}
@endforeach
@endif

A linkek ervenyesek: {{ $expires_at ?? '' }}

Ha nem te kerted, nyugodtan figyelmen kivul hagyhatod.
