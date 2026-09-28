<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Labels &mdash; {{ $purchase->invoice_number }}</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.12.3/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2/qz-tray.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            margin: 0;
            padding: 16px;
            background: #f4f5f7;
            color: #1f2430;
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            max-width: 900px;
            margin: 0 auto 16px;
            padding: 12px 16px;
            background: #fff;
            border: 1px solid #e2e4e9;
            border-radius: 8px;
        }
        .toolbar h1 { font-size: 15px; margin: 0; }
        .toolbar p { margin: 2px 0 0; font-size: 12px; color: #6b7280; }
        .toolbar-actions { display: flex; align-items: center; }
        .toolbar a { font-size: 12px; color: #6b7280; text-decoration: none; margin-right: 14px; }
        .toolbar button {
            border: none;
            background: #3b5bfd;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .toolbar button:hover { background: #2f49d1; }
        .toolbar button.secondary {
            background: #fff;
            color: #3b5bfd;
            border: 1px solid #c7cdfa;
            margin-right: 8px;
        }
        .toolbar button.secondary:hover { background: #f0f2ff; }
        .toolbar select {
            font-size: 12px;
            padding: 7px 8px;
            border-radius: 6px;
            border: 1px solid #d7dae0;
            margin-right: 8px;
            max-width: 180px;
        }
        .tsc-status {
            max-width: 900px;
            margin: -8px auto 16px;
            padding: 0 16px;
            font-size: 12px;
            color: #6b7280;
            min-height: 16px;
        }
        .tsc-status.error { color: #b91c1c; }

        .sheet {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 8mm;
        }
        /* Sized to the actual physical label stock (45mm x 13mm) — not a
           placeholder — so nothing here ever needs the browser to
           shrink-to-fit at print time. Shrinking was the real cause of
           blurry text/QR/barcode: every element got rasterized smaller
           than its declared size, well past what a 203dpi thermal head
           can resolve cleanly. QR and barcode are paired on the same
           row (not QR+title) so the QR's height only has to fit
           alongside a row that already needs real height for the
           barcode, instead of forcing a whole extra row sized to the QR
           for a one-line title. */
        .label {
            width: 45mm;
            height: 13mm;
            border: 1px dashed #b6bac4;
            border-radius: 0.5mm;
            padding: 0.8mm 1.2mm 0.6mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            background: #fff;
            overflow: hidden;
        }
        .label .title {
            width: 100%;
            font-size: 2.3mm;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.15;
            text-align: left;
        }
        .barcode-row {
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .label svg.barcode {
            flex: 1 1 auto;
            min-width: 0;
            height: 100%;
            max-height: 7mm;
            display: block;
        }
        .label .barcode-error { font-size: 1.8mm; color: #b91c1c; }
        .qr-row {
            width: 100%;
            flex: 1 1 auto;
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .label .qr-code {
            flex: 0 0 auto;
            width: 7mm;
            height: 7mm;
        }
        .label .qr-code img {
            width: 100%;
            height: 100%;
            display: block;
            image-rendering: pixelated;
        }
        .label .qr-error {
            font-size: 1.6mm;
            color: #b91c1c;
        }
        .label-bottom {
            width: 100%;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1mm;
        }
        .label .price-code {
            font-family: "Courier New", monospace;
            font-weight: 700;
            font-size: 2.4mm;
            letter-spacing: 0.2mm;
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .label .pcs {
            flex: 0 0 auto;
            font-size: 1.7mm;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty {
            max-width: 900px;
            margin: 60px auto;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .empty a { color: #3b5bfd; }

        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .no-print { display: none !important; }
            .sheet { display: block; }
            .label {
                border: none;
                border-radius: 0;
                break-after: page;
                page-break-after: always;
            }
            .label:last-child {
                break-after: auto;
                page-break-after: auto;
            }
            @page {
                size: 45mm 13mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    @php
        // Single source for both the on-screen labels below and the TSC
        // (TSPL) print payload in the script block — computed once so the
        // two can never drift apart from each other.
        $labelData = $labels->map(function ($row) use ($purchase) {
            // New-style rows own their product directly; historical rows
            // (pre this column existing) fall back to the line's shared
            // product.
            $product = $row->product ?? $row->line?->product;
            $carat   = $row->carat_weight !== null
                ? rtrim(rtrim(number_format((float) $row->carat_weight, 3), '0'), '.') . ' Ct'
                : null;

            return [
                'title'              => $product?->stone_type ?: ($product?->title ?? 'Unknown product'),
                'carat'              => $carat,
                'lot_code'           => (string) $row->lot_code,
                'price_code'         => $row->priceCode(),
                'selling_price_code' => $row->sellingPriceCode(),
                'product_url'        => $product ? route('website.product', $product) : null,
                'qty'                => (int) $row->qty,
            ];
        });
    @endphp

    <div class="toolbar no-print">
        <div>
            <h1>Labels &mdash; {{ $purchase->invoice_number }}</h1>
            <p>{{ $labels->count() }} label{{ $labels->count() === 1 ? '' : 's' }} ready to print</p>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('purchases.show', $purchase) }}">&larr; Back to purchase</a>
            <select id="tscPrinterSelect" title="TSC printer">
                <option value="">— Detect printers —</option>
            </select>
            <button type="button" class="secondary" id="tscDetectBtn">Detect Printers</button>
            <button type="button" class="secondary" id="tscTestBtn">Send Test Label</button>
            <button type="button" class="secondary" id="tscPrintBtn">Print via TSC</button>
            <button type="button" onclick="window.print()">Print</button>
        </div>
    </div>
    <div id="tscStatus" class="tsc-status no-print"></div>

    @if ($labels->isEmpty())
        <div class="empty">
            No items were selected. <a href="{{ route('purchases.show', $purchase) }}">Go back</a> and select at least one row.
        </div>
    @else
        <div class="sheet">
            @foreach ($labelData as $item)
                @foreach (['barcode', 'qr'] as $labelKind)
                <div class="label">
                    <div class="title">{{ $item['title'] }}</div>
                    @if ($labelKind === 'barcode')
                        <div class="barcode-row">
                            <svg class="barcode" data-value="{{ $item['lot_code'] }}"></svg>
                        </div>
                    @else
                        <div class="qr-row">
                            <div class="qr-code" data-value="{{ $item['product_url'] ?? $item['lot_code'] }}"></div>
                        </div>
                    @endif
                    <div class="label-bottom">
                        <div class="price-code">
                            {{ $item['price_code'] }}
                            @if ($item['carat'])
                                - {{ $item['carat'] }}
                            @endif
                            @if ($item['selling_price_code'])
                                - {{ $item['selling_price_code'] }}
                            @endif
                        </div>
                        <div class="pcs">Pcs: {{ $item['qty'] }}</div>
                    </div>
                </div>
                @endforeach
            @endforeach
        </div>
    @endif

    <script>
        document.querySelectorAll('svg.barcode').forEach(function (el) {
            try {
                JsBarcode(el, el.dataset.value || '', {
                    format: 'CODE128',
                    displayValue: true,
                    fontSize: 22,
                    fontOptions: 'bold',
                    width: 2.6,
                    height: 40,
                    margin: 0,
                });
            } catch (e) {
                var msg = document.createElement('div');
                msg.className = 'barcode-error';
                msg.textContent = 'Barcode error';
                el.replaceWith(msg);
            }
        });

        document.querySelectorAll('.qr-code').forEach(function (el) {
            var value = el.dataset.value || '';
            try {
                var qr = qrcode(0, 'M');
                qr.addData(value);
                qr.make();
                var img = document.createElement('img');
                img.src = qr.createDataURL(10, 0);
                img.alt = 'QR: ' + value;
                el.appendChild(img);
            } catch (e) {
                var msg = document.createElement('div');
                msg.className = 'qr-error';
                msg.textContent = 'QR error';
                el.replaceWith(msg);
            }
        });
    </script>

    {{-- ==================== Print via TSC (QZ Tray / TSPL) ==================== --}}
    <script>
        var TSC_LABELS = @json($labelData);
        var TSC_PRINTER_KEY = 'paces.tscPrinterName';

        function tsplEscape(value) {
            return String(value == null ? '' : value).replace(/"/g, "'");
        }

        // Coordinates are in dots (203 dpi ≈ 8 dots/mm, so the full
        // canvas is ~360x104 dots), tuned for the 45mm x 13mm stock this
        // purchase's labels are actually printed on (see purchases.labels'
        // .label CSS for the on-screen equivalent) — a starting point,
        // not exact; use TSC's Diamond Editor/TSC Console to fine-tune
        // positions against the real printer before relying on this for
        // a large run.
        //
        // Barcode and QR now print on their own separate labels (mirrors
        // purchases.labels' on-screen layout) instead of sharing one row,
        // so neither has to be narrowed to avoid running into the other —
        // each can use a more readable native module/cell size.
        function tsplBottomLine(item) {
            var line = item.carat ? (item.price_code + ' - ' + item.carat) : item.price_code;
            if (item.selling_price_code) {
                line += ' - ' + item.selling_price_code;
            }
            return line + '  Pcs: ' + item.qty;
        }

        function buildTsplBarcodeLabel(item) {
            return [
                'SIZE 45 mm, 13 mm',
                'GAP 2 mm, 0 mm',
                'DIRECTION 1',
                'CLS',
                'TEXT 16,8,"2",0,1,1,"' + tsplEscape(item.title) + '"',
                'BARCODE 16,30,"128",40,0,0,2,2,"' + tsplEscape(item.lot_code) + '"',
                'TEXT 16,90,"1",0,1,1,"' + tsplEscape(tsplBottomLine(item)) + '"',
                'PRINT 1,1',
            ].join('\r\n');
        }

        function buildTsplQrLabel(item) {
            return [
                'SIZE 45 mm, 13 mm',
                'GAP 2 mm, 0 mm',
                'DIRECTION 1',
                'CLS',
                'TEXT 16,8,"2",0,1,1,"' + tsplEscape(item.title) + '"',
                'QRCODE 140,25,L,3,A,0,"' + tsplEscape(item.product_url || item.lot_code) + '"',
                'TEXT 16,90,"1",0,1,1,"' + tsplEscape(tsplBottomLine(item)) + '"',
                'PRINT 1,1',
            ].join('\r\n');
        }

        function tscStatus(message, isError) {
            var el = document.getElementById('tscStatus');
            el.textContent = message;
            el.className = 'tsc-status no-print' + (isError ? ' error' : '');
        }

        async function tscEnsureConnected() {
            if (typeof qz === 'undefined') {
                throw new Error('QZ Tray script did not load.');
            }
            if (!qz.websocket.isActive()) {
                await qz.websocket.connect();
            }
            try {
                console.log('[TSC] QZ Tray version:', await qz.api.getVersion());
            } catch (e) { /* non-fatal, just diagnostic */ }
        }

        // QZ Tray can reject with a plain string, an Error, or a nested
        // object depending on where the failure happened (websocket vs.
        // print vs. printer resolution) — normalize all of them to a
        // readable string AND always dump the raw value to the console,
        // since the status line has to stay short but the console can
        // hold whatever detail QZ actually gave us.
        function tscDescribeError(err) {
            console.error('[TSC] raw error:', err);
            if (!err) return 'Unknown error (see console).';
            if (typeof err === 'string') return err;
            if (err.message) return err.message;
            try { return JSON.stringify(err); } catch (e) { return String(err); }
        }

        // Deliberately as simple as TSPL gets — one line of text, no
        // barcode/QR, generous coordinates that can't possibly run off a
        // 45x13mm label. Isolates "does raw data reach the printer at
        // all" from "is the fuller label's TSPL correct" — if this
        // doesn't print, the problem is upstream of buildTsplBarcodeLabel()/
        // buildTsplQrLabel() entirely (Windows print queue / driver / raw
        // datatype), not anything about barcode or QR positioning.
        function buildTsplTestLabel() {
            return [
                'SIZE 45 mm, 13 mm',
                'GAP 2 mm, 0 mm',
                'CLS',
                'TEXT 10,10,"3",0,1,1,"TEST OK"',
                'PRINT 1,1',
            ].join('\r\n');
        }

        async function sendTestLabel() {
            var select = document.getElementById('tscPrinterSelect');
            try {
                if (!select.value) {
                    await detectPrinters();
                }
                if (!select.value) {
                    tscStatus('No printer selected — pick one from the list.', true);
                    return;
                }

                var printerName = select.value;
                tscStatus('Sending test label to ' + printerName + '…');
                await tscEnsureConnected();

                var config = qz.configs.create(printerName);
                console.log('[TSC] printing to config:', config, '(exact name QZ Tray resolved, compare this against the OS print queue you check afterward)');
                var result = await qz.print(config, [{ type: 'raw', format: 'plain', data: buildTsplTestLabel() }]);
                console.log('[TSC] qz.print() resolved with:', result);

                tscStatus('Test label sent to ' + printerName + ' — check the printer AND its Windows print queue now. If both are empty, see the note below the labels.');
            } catch (err) {
                tscStatus('Test print failed: ' + tscDescribeError(err), true);
            }
        }

        async function detectPrinters() {
            var select = document.getElementById('tscPrinterSelect');
            try {
                tscStatus('Connecting to QZ Tray…');
                await tscEnsureConnected();

                var printers = await qz.printers.find();
                console.log('[TSC] qz.printers.find() returned:', printers);
                select.innerHTML = '';
                if (!printers.length) {
                    select.innerHTML = '<option value="">— No printers found —</option>';
                    tscStatus('QZ Tray is running but reports no printers.', true);
                    return;
                }
                printers.forEach(function (name) {
                    var opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    select.appendChild(opt);
                });

                var saved = localStorage.getItem(TSC_PRINTER_KEY);
                var tscMatches = printers.filter(function (n) { return /tsc/i.test(n); });
                if (saved && printers.indexOf(saved) !== -1) {
                    select.value = saved;
                } else if (tscMatches.length) {
                    select.value = tscMatches[0];
                }

                var statusMsg = printers.length + ' printer(s) found.';
                if (tscMatches.length > 1) {
                    // Windows sometimes leaves a stale/duplicate printer
                    // object behind after a driver reinstall — QZ Tray
                    // can happily "succeed" against the dead one while
                    // nothing reaches the real device or its queue.
                    statusMsg += ' Note: ' + tscMatches.length + ' printers match "tsc" (' + tscMatches.join(', ') + ') — if printing silently does nothing, try the other one.';
                }
                tscStatus(statusMsg, tscMatches.length > 1);
            } catch (err) {
                select.innerHTML = '<option value="">— Detect printers —</option>';
                tscStatus('Could not reach QZ Tray. Is it installed and running? (' + tscDescribeError(err) + ')', true);
            }
        }

        async function printViaTsc() {
            var select = document.getElementById('tscPrinterSelect');

            if (!TSC_LABELS.length) {
                tscStatus('No labels to print.', true);
                return;
            }

            try {
                if (!select.value) {
                    await detectPrinters();
                }
                if (!select.value) {
                    tscStatus('No printer selected — pick one from the list.', true);
                    return;
                }

                var printerName = select.value;
                localStorage.setItem(TSC_PRINTER_KEY, printerName);

                tscStatus('Printing ' + TSC_LABELS.length + ' label(s) to ' + printerName + '…');
                await tscEnsureConnected();

                var config = qz.configs.create(printerName);
                console.log('[TSC] printing to config:', config);
                var data = [];
                TSC_LABELS.forEach(function (item) {
                    data.push({ type: 'raw', format: 'plain', data: buildTsplBarcodeLabel(item) });
                    data.push({ type: 'raw', format: 'plain', data: buildTsplQrLabel(item) });
                });
                console.log('[TSC] raw TSPL being sent:', data.map(function (d) { return d.data; }));
                var result = await qz.print(config, data);
                console.log('[TSC] qz.print() resolved with:', result);

                tscStatus('Sent ' + TSC_LABELS.length + ' label(s) to ' + printerName + '. Check the Windows print queue for that printer to confirm it actually arrived.');
            } catch (err) {
                tscStatus('Print failed: ' + tscDescribeError(err), true);
            }
        }

        document.getElementById('tscDetectBtn').addEventListener('click', detectPrinters);
        document.getElementById('tscTestBtn').addEventListener('click', sendTestLabel);
        document.getElementById('tscPrintBtn').addEventListener('click', printViaTsc);
    </script>

</body>
</html>
