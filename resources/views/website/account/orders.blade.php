@extends('website.layout')

@section('title', 'My Orders — Sukaina Gems')

@section('content')

<div style="height:140px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--dark-900),var(--dark-750));border-bottom:1px solid rgba(0,191,176,.1);text-align:center">
  <div class="sg-eyebrow" style="margin-bottom:6px">My Account</div>
  <h1 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:700">Order History</h1>
</div>

<div style="padding:48px 60px;background:var(--dark-900);min-height:60vh">
  <div style="max-width:1100px;margin:0 auto">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
      <a href="{{ route('website.account.profile') }}" style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:var(--teal-300);text-decoration:none">← Back to Profile</a>
      <div style="font-size:13px;color:var(--white-faint)">{{ $orders->total() }} order(s)</div>
    </div>

    @if($orders->isEmpty())
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:80px;text-align:center">
        <div style="font-size:48px;margin-bottom:16px">💎</div>
        <p style="font-size:16px;color:var(--white-dim);margin-bottom:20px">You haven't placed any orders yet.</p>
        <a href="{{ route('website.collections') }}" class="sg-btn-primary">Browse Collection →</a>
      </div>
    @else
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="background:rgba(0,191,176,.04);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-faint)">
              <th style="padding:14px 22px;text-align:left;font-weight:500">Order Number</th>
              <th style="padding:14px 22px;text-align:left;font-weight:500">Date</th>
              <th style="padding:14px 22px;text-align:left;font-weight:500">Items</th>
              <th style="padding:14px 22px;text-align:right;font-weight:500">Total</th>
              <th style="padding:14px 22px;text-align:center;font-weight:500">Payment</th>
              <th style="padding:14px 22px;text-align:center;font-weight:500">Status</th>
              <th style="padding:14px 22px;text-align:center;font-weight:500"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($orders as $order)
              <tr style="border-top:1px solid rgba(0,191,176,.06);transition:background .2s" onmouseover="this.style.background='rgba(0,191,176,.04)'" onmouseout="this.style.background='transparent'">
                <td style="padding:16px 22px">
                  <div style="font-weight:500;font-size:14px;color:var(--white)">{{ $order->sale_number }}</div>
                  @if($order->location)
                    <div style="font-size:11px;color:var(--white-faint);margin-top:2px">{{ $order->location->name }}</div>
                  @endif
                </td>
                <td style="padding:16px 22px;font-size:13px;color:var(--white-dim)">{{ $order->sale_date->format('d M Y') }}</td>
                <td style="padding:16px 22px;font-size:13px;color:var(--white-dim)">
                  {{ $order->lines->count() }} gem(s)
                  @if($order->lines->count() <= 2)
                    <div style="font-size:11px;color:var(--white-faint);margin-top:2px">
                      {{ $order->lines->take(2)->map(fn($l) => $l->product?->title ?? 'Item')->implode(', ') }}
                    </div>
                  @endif
                </td>
                <td style="padding:16px 22px;text-align:right;font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--teal-300)">
                  {{ $settings->formatPrice($order->grand_total) }}
                </td>
                <td style="padding:16px 22px;text-align:center">
                  @php
                    $payColors = [
                      'paid'    => ['bg' => 'rgba(80,200,130,.12)', 'border' => 'rgba(80,200,130,.25)', 'color' => '#7ec87e'],
                      'partial' => ['bg' => 'rgba(200,160,80,.12)', 'border' => 'rgba(200,160,80,.25)', 'color' => 'var(--gold-light)'],
                      'unpaid'  => ['bg' => 'rgba(220,80,80,.12)',  'border' => 'rgba(220,80,80,.25)',  'color' => '#e07070'],
                    ];
                    $pc = $payColors[$order->payment_status] ?? $payColors['unpaid'];
                  @endphp
                  <span style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:4px 10px;border-radius:2px;background:{{ $pc['bg'] }};border:1px solid {{ $pc['border'] }};color:{{ $pc['color'] }}">
                    {{ $order->paymentStatusLabel() }}
                  </span>
                </td>
                <td style="padding:16px 22px;text-align:center">
                  @php
                    $sc = match($order->status) {
                      'completed' => ['bg'=>'rgba(80,200,130,.12)','b'=>'rgba(80,200,130,.25)','c'=>'#7ec87e'],
                      'posted'    => ['bg'=>'rgba(0,191,176,.12)',  'b'=>'rgba(0,191,176,.25)',  'c'=>'var(--teal-300)'],
                      'cancelled', 'refunded' => ['bg'=>'rgba(220,80,80,.12)','b'=>'rgba(220,80,80,.25)','c'=>'#e07070'],
                      default     => ['bg'=>'rgba(200,160,80,.12)','b'=>'rgba(200,160,80,.25)','c'=>'var(--gold-light)'],
                    };
                  @endphp
                  <span style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:4px 10px;border-radius:2px;background:{{ $sc['bg'] }};border:1px solid {{ $sc['b'] }};color:{{ $sc['c'] }}">
                    {{ $order->statusLabel() }}
                  </span>
                </td>
                <td style="padding:16px 22px;text-align:center">
                  <a href="{{ route('website.account.order-detail', $order->id) }}"
                     style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--teal-300);text-decoration:none;padding:6px 14px;border:1px solid rgba(0,191,176,.3);border-radius:2px;transition:all .2s"
                     onmouseover="this.style.background='rgba(0,191,176,.07)'" onmouseout="this.style.background='transparent'">
                    View
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($orders->hasPages())
        <div style="margin-top:24px;display:flex;justify-content:center;gap:8px">
          @if($orders->onFirstPage())
            <span style="padding:8px 16px;font-size:12px;color:var(--white-faint);border:1px solid rgba(255,255,255,.08);border-radius:2px">← Prev</span>
          @else
            <a href="{{ $orders->previousPageUrl() }}" style="padding:8px 16px;font-size:12px;color:var(--teal-300);border:1px solid rgba(0,191,176,.25);border-radius:2px;text-decoration:none;transition:all .2s">← Prev</a>
          @endif
          @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
            @if($page == $orders->currentPage())
              <span style="padding:8px 14px;font-size:12px;color:#fff;background:var(--teal-600);border:1px solid var(--teal-500);border-radius:2px">{{ $page }}</span>
            @else
              <a href="{{ $url }}" style="padding:8px 14px;font-size:12px;color:var(--teal-300);border:1px solid rgba(0,191,176,.25);border-radius:2px;text-decoration:none;transition:all .2s">{{ $page }}</a>
            @endif
          @endforeach
          @if($orders->hasMorePages())
            <a href="{{ $orders->nextPageUrl() }}" style="padding:8px 16px;font-size:12px;color:var(--teal-300);border:1px solid rgba(0,191,176,.25);border-radius:2px;text-decoration:none;transition:all .2s">Next →</a>
          @else
            <span style="padding:8px 16px;font-size:12px;color:var(--white-faint);border:1px solid rgba(255,255,255,.08);border-radius:2px">Next →</span>
          @endif
        </div>
      @endif

    @endif

  </div>
</div>
@endsection
