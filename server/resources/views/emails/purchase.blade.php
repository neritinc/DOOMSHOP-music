<!doctype html>
<html lang="hu">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Letoltesi linkek</title>
    <style>
      body { font-family: Arial, sans-serif; color: #111; }
      .card { max-width: 620px; margin: 0 auto; border: 1px solid #e5e5e5; border-radius: 8px; padding: 20px; }
      .muted { color: #666; font-size: 12px; }
      .btn { display: inline-block; background: #111; color: #fff; padding: 10px 14px; border-radius: 6px; text-decoration: none; }
      .item { margin: 12px 0; padding: 12px; border: 1px solid #eee; border-radius: 6px; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Letoltesi linkek</h1>
      <p>Szia{{ isset($name) && $name !== '' ? ' ' . e($name) : '' }}!</p>
      <p>Koszonjuk a vasarlast! Az alabbi linkekkel tudod letolteni a teljes hanganyagot.</p>

      @if (!empty($items))
        @foreach ($items as $item)
          <div class="item">
            <div><strong>{{ ucfirst($item['type'] ?? 'item') }}</strong>: {{ $item['title'] ?? '' }}</div>
            @if (!empty($item['url']))
              <div style="margin-top:8px;">
                <a class="btn" href="{{ $item['url'] }}">Letoltes</a>
              </div>
            @endif
          </div>
        @endforeach
      @endif

      <p class="muted">A linkek ervenyesek: {{ $expires_at ?? '' }}</p>
      <p class="muted">Ha nem te kerted, nyugodtan figyelmen kivul hagyhatod.</p>
    </div>
  </body>
</html>
