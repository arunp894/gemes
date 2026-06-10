@extends('website.layout')

@section('title', 'My Account — Sukaina Gems')

@section('content')

{{-- Hero band --}}
<div style="height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--dark-900),var(--dark-750));border-bottom:1px solid rgba(0,191,176,.1);position:relative;overflow:hidden">
  <div style="position:absolute;inset:0;background-image:linear-gradient(rgba(0,191,176,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(0,191,176,.025) 1px,transparent 1px);background-size:60px 60px"></div>
  <div style="position:relative;z-index:1;text-align:center">
    <div class="sg-eyebrow" style="margin-bottom:6px">My Account</div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:700">{{ $customer->name }}</h1>
    <p style="font-size:13px;color:var(--white-faint);margin-top:4px">{{ $customer->customer_code }}</p>
  </div>
</div>

<div style="padding:48px 60px;background:var(--dark-900);min-height:60vh">
  <div style="display:grid;grid-template-columns:260px 1fr;gap:36px;max-width:1200px;margin:0 auto">

    {{-- Sidebar nav --}}
    <div>
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;overflow:hidden">
        <div style="padding:20px 22px;border-bottom:1px solid rgba(0,191,176,.08)">
          <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--white)">{{ $customer->name }}</div>
          <div style="font-size:12px;color:var(--teal-300);margin-top:2px">{{ $customer->email }}</div>
          @if($customer->customer_type)
            <div style="margin-top:8px">
              <span style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:3px 9px;background:rgba(0,191,176,.1);border:1px solid rgba(0,191,176,.2);border-radius:2px;color:var(--teal-300)">
                {{ $customer->typeLabel() }}
              </span>
            </div>
          @endif
        </div>
        <nav style="padding:8px 0">
          @php
            $navItems = [
              ['route' => 'website.account.profile', 'label' => 'Overview', 'icon' => '◈'],
              ['route' => 'website.account.orders',  'label' => 'My Orders', 'icon' => '◫'],
              ['route' => 'website.account.edit',    'label' => 'Edit Profile', 'icon' => '◎'],
            ];
            $current = request()->routeIs('*'.last(explode('.', Route::currentRouteName())));
          @endphp
          @foreach($navItems as $item)
            @php $isActive = request()->routeIs($item['route']); @endphp
            <a href="{{ route($item['route']) }}"
               style="display:flex;align-items:center;gap:12px;padding:12px 22px;font-size:13px;font-weight:{{ $isActive ? '500' : '400' }};color:{{ $isActive ? 'var(--teal-300)' : 'var(--white-dim)' }};text-decoration:none;background:{{ $isActive ? 'rgba(0,191,176,.07)' : 'transparent' }};border-left:2px solid {{ $isActive ? 'var(--teal-400)' : 'transparent' }};transition:all .2s">
              <span style="font-size:15px;opacity:.7">{{ $item['icon'] }}</span>
              {{ $item['label'] }}
            </a>
          @endforeach
        </nav>
        <div style="padding:16px 22px;border-top:1px solid rgba(0,191,176,.08)">
          <form method="POST" action="{{ route('website.auth.logout') }}">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:13px;color:rgba(220,80,80,.7);padding:0;font-family:'Jost',sans-serif;transition:color .2s" onmouseover="this.style.color='#e07070'" onmouseout="this.style.color='rgba(220,80,80,.7)'">
              Sign Out
            </button>
          </form>
        </div>
      </div>
    </div>

    {{-- Main content --}}
    <div>

      @if(session('success'))
        <div style="padding:14px 18px;background:rgba(80,200,130,.1);border:1px solid rgba(80,200,130,.25);border-radius:4px;font-size:13px;color:#7ec87e;margin-bottom:24px">{{ session('success') }}</div>
      @endif

      {{-- Quick stats --}}
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:32px">
        @php
          $totalOrders = $customer->sales()->count();
          $totalSpent  = $customer->sales()->where('status','!=','cancelled')->sum('grand_total');
          $lastOrder   = $customer->sales()->orderByDesc('created_at')->first();
        @endphp
        <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:20px 22px">
          <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-faint);margin-bottom:6px">Total Orders</div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--teal-300)">{{ $totalOrders }}</div>
        </div>
        <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:20px 22px">
          <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-faint);margin-bottom:6px">Total Spent</div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:700;color:var(--teal-300)">{{ $settings->formatPrice($totalSpent) }}</div>
        </div>
        <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:20px 22px">
          <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-faint);margin-bottom:6px">Member Since</div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:700;color:var(--teal-300)">{{ $customer->created_at->format('M Y') }}</div>
        </div>
      </div>

      {{-- Recent orders --}}
      <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid rgba(0,191,176,.08)">
          <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600">Recent Orders</div>
          <a href="{{ route('website.account.orders') }}" style="font-size:12px;color:var(--teal-300);text-decoration:none;letter-spacing:1px;text-transform:uppercase">View all →</a>
        </div>

        @if($recentOrders->isEmpty())
          <div style="padding:48px;text-align:center;color:var(--white-faint)">
            <div style="font-size:36px;margin-bottom:12px">💎</div>
            <p style="font-size:14px">No orders yet. <a href="{{ route('website.collections') }}" style="color:var(--teal-300);text-decoration:none">Browse our collection</a></p>
          </div>
        @else
          <table style="width:100%;border-collapse:collapse">
            <thead>
              <tr style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-faint)">
                <th style="padding:12px 22px;text-align:left;font-weight:500">Order</th>
                <th style="padding:12px 22px;text-align:left;font-weight:500">Date</th>
                <th style="padding:12px 22px;text-align:left;font-weight:500">Items</th>
                <th style="padding:12px 22px;text-align:right;font-weight:500">Total</th>
                <th style="padding:12px 22px;text-align:center;font-weight:500">Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($recentOrders as $order)
                <tr style="border-top:1px solid rgba(0,191,176,.06);transition:background .2s" onmouseover="this.style.background='rgba(0,191,176,.04)'" onmouseout="this.style.background='transparent'">
                  <td style="padding:14px 22px">
                    <a href="{{ route('website.account.order-detail', $order->id) }}" style="color:var(--teal-300);text-decoration:none;font-size:13px;font-weight:500">{{ $order->sale_number }}</a>
                  </td>
                  <td style="padding:14px 22px;font-size:13px;color:var(--white-dim)">{{ $order->sale_date->format('d M Y') }}</td>
                  <td style="padding:14px 22px;font-size:13px;color:var(--white-dim)">{{ $order->lines->count() }} item(s)</td>
                  <td style="padding:14px 22px;text-align:right;font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:700;color:var(--teal-300)">{{ $settings->formatPrice($order->grand_total) }}</td>
                  <td style="padding:14px 22px;text-align:center">
                    <span style="font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:4px 10px;border-radius:2px;
                      background:{{ $order->status === 'completed' ? 'rgba(80,200,130,.12)' : ($order->status === 'posted' ? 'rgba(0,191,176,.12)' : 'rgba(200,160,80,.12)') }};
                      color:{{ $order->status === 'completed' ? '#7ec87e' : ($order->status === 'posted' ? 'var(--teal-300)' : 'var(--gold-light)') }};
                      border:1px solid {{ $order->status === 'completed' ? 'rgba(80,200,130,.25)' : ($order->status === 'posted' ? 'rgba(0,191,176,.25)' : 'rgba(200,160,80,.25)') }}">
                      {{ $order->statusLabel() }}
                    </span>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>

      {{-- Contact info --}}
      <div style="margin-top:24px;background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:22px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600">Contact Details</div>
          <a href="{{ route('website.account.edit') }}" style="font-size:12px;color:var(--teal-300);text-decoration:none;letter-spacing:1px;text-transform:uppercase">Edit →</a>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px">
          <div><span style="color:var(--white-faint)">Email</span><br><span style="color:var(--white-dim);margin-top:4px;display:block">{{ $customer->email }}</span></div>
          <div><span style="color:var(--white-faint)">Phone</span><br><span style="color:var(--white-dim);margin-top:4px;display:block">{{ $customer->phone ?: '—' }}</span></div>
          <div style="grid-column:1/-1"><span style="color:var(--white-faint)">Address</span><br>
            <span style="color:var(--white-dim);margin-top:4px;display:block">
              @if($customer->address_line1)
                {{ $customer->address_line1 }}{{ $customer->address_line2 ? ', '.$customer->address_line2 : '' }}<br>
                {{ collect([$customer->city, $customer->state, $customer->zip_code])->filter()->implode(', ') }}<br>
                {{ $customer->country }}
              @else
                <span style="color:var(--white-faint)">No address on file.</span>
              @endif
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection
