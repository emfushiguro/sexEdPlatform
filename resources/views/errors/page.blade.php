<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · Conscious Connections</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; color: #211532; background: #f8f5fc; font-family: 'Poppins', system-ui, sans-serif; }
        main { width: min(100%, 1000px); display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 48px; padding: clamp(28px, 6vw, 64px); background: white; border: 1px solid #eadcf6; border-radius: 28px; box-shadow: 0 24px 70px rgba(63, 16, 101, .09); }
        .brand { color: #730db1; font-weight: 800; font-size: 14px; letter-spacing: .04em; }
        .code { margin: 40px 0 8px; color: #830db1; font-size: 13px; font-weight: 800; letter-spacing: .14em; }
        h1 { margin: 0; font-size: clamp(30px, 4vw, 44px); line-height: 1.15; letter-spacing: -.035em; }
        p { color: #665d70; line-height: 1.7; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
        a, button { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 12px 20px; border: 1px solid #ded0eb; border-radius: 12px; background: white; color: #5f168d; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .primary { border-color: #730db1; background: #730db1; color: white; }
        a:focus-visible, button:focus-visible { outline: 3px solid #b667eb; outline-offset: 3px; }
        img { width: 100%; max-height: 320px; object-fit: contain; }
        @media (max-width: 680px) { main { grid-template-columns: 1fr; gap: 24px; } .art { grid-row: 1; } img { max-height: 190px; } .code { margin-top: 24px; } }
    </style>
</head>
<body>
    <main>
        <div>
            <div class="brand">Conscious Connections</div>
            <p class="code">ERROR {{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <div class="actions">
                @if($code === '500')
                    <a class="primary" href="">Try again</a>
                @else
                    <button class="primary" type="button" onclick="if (history.length > 1) history.back(); else location.href = '/'">Go back</button>
                @endif
                <a href="/">Return home</a>
            </div>
        </div>
        <div class="art"><img src="/assets/illustrations/{{ $illustration }}" alt=""></div>
    </main>
</body>
</html>
