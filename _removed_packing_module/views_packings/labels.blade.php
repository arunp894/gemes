<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Labels — {{ $packing->packing_number }}</title>
<style>
    @page { size: 2in 1in; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; }
    .label {
        width: 2in; height: 1in; padding: 4px 6px;
        display: flex; flex-direction: column; justify-content: space-between;
        page-break-after: always;
        overflow: hidden;
    }
    .label:last-child { page-break-after: auto; }
    .label .top { display: flex; justify-content: space-between; font-size: 8px; font-weight: bold; }
    .label .title {
        font-size: 9px; font-weight: bold;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .label .mid { font-size: 8px; color: #333; }
    .label .bottom { display: flex; justify-content: space-between; align-items: baseline; font-size: 8px; }
    .label .price { font-weight: bold; letter-spacing: 1px; font-size: 10px; }
    @media screen {
        body { background: #eee; padding: 20px; }
        .label { background: #fff; border: 1px dashed #999; margin-bottom: 10px; }
    }
</style>
</head>
<body>
    @forelse ($labels as $row)
        <div class="label">
            <div class="top">
                <span>{{ $row->lot_code }}</span>
                <span>{{ optional($row->product)->sku }}</span>
            </div>
            <div class="title">{{ optional($row->product)->title ?? '—' }}</div>
            <div class="mid">
                @if ($row->carat_weight)
                    {{ number_format((float) $row->carat_weight, 3) }} ct
                @endif
                @if (optional($row->product)->stone_type)
                    &middot; {{ $row->product->stone_type }}
                @endif
            </div>
            <div class="bottom">
                <span class="price">{{ $row->priceCode() }}</span>
                <span>{{ optional(optional($row->product)->primaryBarcode)->barcode_value }}</span>
            </div>
        </div>
    @empty
        <p style="padding:20px;font-family:sans-serif;">No labels selected.</p>
    @endforelse

    <script>window.onload = function () { window.print(); };</script>
</body>
</html>
