<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 16px;
            background: #e9ecef;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
        }

        .toolbar {
            max-width: 420px;
            margin: 0 auto 16px;
            display: flex;
            gap: 8px;
        }

        .toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 11px 16px;
            background: #176b42;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }

        .toolbar .secondary {
            background: #475569;
        }

        .paper {
            width: {{ $paperWidth }}mm;
            margin: 0 auto;
            background: #fff;
        }

        .voucher {
            width: 100%;
            min-height: 38mm;
            padding: 3mm 2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-bottom: 1px dashed #555;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .brand {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 1.5px;
        }

        .site {
            margin-top: 2px;
            font-size: 9px;
        }

        .package {
            margin-top: 3px;
            font-size: 11px;
            font-weight: 700;
        }

        .price {
            margin-top: 2px;
            font-size: 11px;
        }

        .code {
            margin-top: 5px;
            font-family: "Courier New", monospace;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 1px;
            overflow-wrap: anywhere;
        }

        .qr {
            width: 25mm;
            height: 25mm;
            margin-top: 4px;
            object-fit: contain;
        }

        .instructions {
            margin-top: 3px;
            font-size: 8px;
        }

        .expiry {
            margin-top: 2px;
            font-size: 8px;
        }

        @page {
            size: {{ $paperWidth }}mm auto;
            margin: 2mm;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .paper {
                width: 100%;
                margin: 0;
            }

            .voucher {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Print Vouchers</button>
        <button class="secondary" onclick="window.close()">Close</button>
    </div>

    <main class="paper">
        @foreach ($vouchers as $voucher)
            <article class="voucher">
                <div class="brand">NACPASI</div>
                <div class="site">{{ $siteName }}</div>

                <div class="package">
                    {{ $voucher['package_name'] ?? $packageName ?? 'WiFi Access' }}
                </div>

                <div class="price">
                    {{ $voucher['currency'] }} {{ number_format((float) $voucher['price'], 2) }}
                </div>

                <div class="code">{{ $voucher['code'] }}</div>

                <img class="qr" src="{{ $voucher['qr'] }}" alt="Voucher QR">

                <div class="instructions">
                    Scan QR to read voucher code, then enter it on the WiFi portal.
                </div>

                @if ($voucher['expires_at'])
                    <div class="expiry">
                        Expires: {{ \Illuminate\Support\Carbon::parse($voucher['expires_at'])->format('d/m/Y H:i') }}
                    </div>
                @endif
            </article>
        @endforeach
    </main>
</body>
</html>
