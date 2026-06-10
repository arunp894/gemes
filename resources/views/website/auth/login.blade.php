@extends('website.layout')

@section('title', 'Sign In — Sukaina Gems')

@section('content')
<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;padding:60px 20px;background:radial-gradient(ellipse at 50% 0%,rgba(0,191,176,.07),transparent 60%)">
  <div style="width:100%;max-width:440px">

    <div style="text-align:center;margin-bottom:32px">
      <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--teal-400),var(--teal-700));border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;margin-bottom:16px;box-shadow:0 0 28px rgba(0,191,176,.3)">SG</div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:var(--white)">Welcome back</h1>
      <p style="font-size:14px;color:var(--white-faint);margin-top:6px">Sign in to your Sukaina Gems account</p>
    </div>

    @if(session('info'))
      <div style="padding:14px 18px;background:rgba(0,191,176,.1);border:1px solid rgba(0,191,176,.25);border-radius:4px;font-size:13px;color:var(--teal-300);margin-bottom:20px">{{ session('info') }}</div>
    @endif
    @if(session('success'))
      <div style="padding:14px 18px;background:rgba(80,200,130,.1);border:1px solid rgba(80,200,130,.25);border-radius:4px;font-size:13px;color:#7ec87e;margin-bottom:20px">{{ session('success') }}</div>
    @endif

    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:6px;padding:36px">

      @if($errors->any())
        <div style="padding:14px 18px;background:rgba(220,80,80,.1);border:1px solid rgba(220,80,80,.25);border-radius:4px;font-size:13px;color:#e07070;margin-bottom:24px">
          @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
      @endif

      <form method="POST" action="{{ route('website.auth.login.post') }}">
        @csrf

        <div style="margin-bottom:20px">
          <label style="display:block;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-dim);margin-bottom:8px">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" required autofocus
                 class="sg-input" placeholder="you@example.com">
        </div>

        <div style="margin-bottom:28px">
          <label style="display:block;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-dim);margin-bottom:8px">Password</label>
          <input type="password" name="password" required
                 class="sg-input" placeholder="••••••••">
        </div>

        <div style="display:flex;align-items:center;margin-bottom:24px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--white-dim)">
            <input type="checkbox" name="remember" style="accent-color:var(--teal-500)"> Remember me
          </label>
        </div>

        <button type="submit" class="sg-btn-primary" style="width:100%;justify-content:center">Sign In →</button>
      </form>
    </div>

    <p style="text-align:center;margin-top:24px;font-size:14px;color:var(--white-faint)">
      Don't have an account?
      <a href="{{ route('website.auth.register') }}" style="color:var(--teal-300);text-decoration:none;font-weight:500">Create one</a>
    </p>

  </div>
</div>

@push('head_styles')
<style>
.sg-input{width:100%;background:var(--dark-750);border:1px solid rgba(0,191,176,.2);border-radius:3px;color:var(--white);font-family:'Jost',sans-serif;font-size:14px;padding:12px 16px;outline:none;transition:border .3s;display:block}
.sg-input:focus{border-color:var(--teal-400)}
</style>
@endpush
@endsection
