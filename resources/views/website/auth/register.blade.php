@extends('website.layout')

@section('title', 'Create Account — Sukaina Gems')

@section('content')
<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;padding:60px 20px;background:radial-gradient(ellipse at 50% 0%,rgba(0,191,176,.07),transparent 60%)">
  <div style="width:100%;max-width:480px">

    <div style="text-align:center;margin-bottom:32px">
      <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--teal-400),var(--teal-700));border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;margin-bottom:16px;box-shadow:0 0 28px rgba(0,191,176,.3)">SG</div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:600;color:var(--white)">Create your account</h1>
      <p style="font-size:14px;color:var(--white-faint);margin-top:6px">Join Sukaina Gems to track orders and checkout faster</p>
    </div>

    @if($errors->any())
      <div style="padding:14px 18px;background:rgba(220,80,80,.1);border:1px solid rgba(220,80,80,.25);border-radius:4px;font-size:13px;color:#e07070;margin-bottom:20px">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
      </div>
    @endif

    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:6px;padding:36px">
      <form method="POST" action="{{ route('website.auth.register.post') }}">
        @csrf

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
          <div>
            <label class="sg-label">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="sg-input" placeholder="Your name">
          </div>
          <div>
            <label class="sg-label">Phone</label>
            <input type="tel" name="phone" value="{{ old('phone') }}" class="sg-input" placeholder="+91 98765 43210">
          </div>
        </div>

        <div style="margin-bottom:20px">
          <label class="sg-label">Email</label>
          <input type="email" name="email" value="{{ old('email') }}" required class="sg-input" placeholder="you@example.com">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px">
          <div>
            <label class="sg-label">Password</label>
            <input type="password" name="password" required class="sg-input" placeholder="Min. 8 characters">
          </div>
          <div>
            <label class="sg-label">Confirm Password</label>
            <input type="password" name="password_confirmation" required class="sg-input" placeholder="Repeat password">
          </div>
        </div>

        <button type="submit" class="sg-btn-primary" style="width:100%;justify-content:center">Create Account →</button>
      </form>
    </div>

    <p style="text-align:center;margin-top:24px;font-size:14px;color:var(--white-faint)">
      Already have an account?
      <a href="{{ route('website.auth.login') }}" style="color:var(--teal-300);text-decoration:none;font-weight:500">Sign in</a>
    </p>

  </div>
</div>

@push('head_styles')
<style>
.sg-label{display:block;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-dim);margin-bottom:8px}
.sg-input{width:100%;background:var(--dark-750);border:1px solid rgba(0,191,176,.2);border-radius:3px;color:var(--white);font-family:'Jost',sans-serif;font-size:14px;padding:12px 16px;outline:none;transition:border .3s;display:block}
.sg-input:focus{border-color:var(--teal-400)}
</style>
@endpush
@endsection
