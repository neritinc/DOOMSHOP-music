<!doctype html>
<html lang="hu">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email teszt</title>
    <style>
      body { font-family: Arial, sans-serif; color: #111; }
      .card { max-width: 560px; margin: 0 auto; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px; }
      .muted { color: #666; font-size: 12px; }
      .btn { display: inline-block; background: #111; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Email kuldes teszt</h1>
      <p>Szia{{ isset($name) ? ' ' . e($name) : '' }}!</p>
      <p>Ez egy teszt email a Doomshop rendszerbol.</p>
      <p><strong>Idopont:</strong> {{ $timestamp ?? now()->toDateTimeString() }}</p>
      @if (!empty($actionUrl))
        <p><a class="btn" href="{{ $actionUrl }}">Nyisd meg</a></p>
      @endif
      <p class="muted">Ha nem te kerted, nyugodtan figyelmen kivul hagyhatod.</p>
    </div>
  </body>
</html>
