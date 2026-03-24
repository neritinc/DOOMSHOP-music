Email kuldes teszt
===================

Szia{{ isset($name) ? ' ' . $name : '' }}!

Ez egy teszt email a Doomshop rendszerbol.
Idopont: {{ $timestamp ?? now()->toDateTimeString() }}

@if (!empty($actionUrl))
    Nyisd meg: {{ $actionUrl }}
@endif
