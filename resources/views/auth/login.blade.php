<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log In — Geekguayaco</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    body { margin:0; background:#0a0e27; min-height:100vh; display:flex; flex-direction:column; font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#e0e7ff; }
    .input-field { width:100%; padding:.75rem 1rem; background:rgba(255,255,255,.05); border:1px solid rgba(99,102,241,.25); border-radius:8px; color:#e0e7ff; font-size:.95rem; outline:none; transition:border-color .2s; box-sizing:border-box; }
    .input-field:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
    .input-field::placeholder { color:#64748b; }
    .input-field.error { border-color:#f87171; }
    .btn-submit { width:100%; padding:.85rem; border:none; border-radius:8px; background:linear-gradient(135deg,#6366f1,#a855f7); color:#fff; font-weight:600; font-size:1rem; cursor:pointer; transition:opacity .2s; }
    .btn-submit:hover { opacity:.88; }
    .error-msg { color:#f87171; font-size:.82rem; margin-top:.35rem; }
    @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(18px)} }
  </style>
</head>
<body>

  {{-- Nav --}}
  <nav style="display:flex;justify-content:space-between;align-items:center;padding:1.25rem 3rem;border-bottom:1px solid rgba(99,102,241,.12);background:rgba(10,14,39,.95);">
    <a href="/" style="display:flex;align-items:center;gap:.4rem;font-size:1.3rem;font-weight:700;text-decoration:none;color:inherit;">
      <span style="background:linear-gradient(135deg,#6366f1,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">⚡</span>
      Geekguayaco
    </a>
    <a href="/register" style="font-size:.9rem;color:#a5b4fc;text-decoration:none;">No account? <strong>Sign up</strong></a>
  </nav>

  {{-- Background blobs --}}
  <div style="position:fixed;width:380px;height:380px;background:radial-gradient(circle,rgba(99,102,241,.15) 0%,transparent 70%);border-radius:50%;top:-100px;right:-80px;animation:float 7s ease-in-out infinite;pointer-events:none;z-index:0;"></div>
  <div style="position:fixed;width:300px;height:300px;background:radial-gradient(circle,rgba(168,85,247,.1) 0%,transparent 70%);border-radius:50%;bottom:-60px;left:-60px;animation:float 9s ease-in-out infinite 1.5s;pointer-events:none;z-index:0;"></div>

  {{-- Card --}}
  <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:2rem;position:relative;z-index:1;">
    <div style="width:100%;max-width:420px;background:rgba(15,21,53,.9);border:1px solid rgba(99,102,241,.2);border-radius:18px;padding:2.5rem;backdrop-filter:blur(12px);">

      <div style="text-align:center;margin-bottom:2rem;">
        <div style="font-size:2rem;margin-bottom:.5rem;">⚡</div>
        <h1 style="font-size:1.6rem;font-weight:700;margin:0 0 .4rem;color:#e0e7ff;">Welcome back</h1>
        <p style="color:#a5b4fc;font-size:.9rem;margin:0;">Log in to access your saved prompts</p>
      </div>

      @if ($errors->any())
        <div style="background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);border-radius:8px;padding:.85rem 1rem;margin-bottom:1.25rem;font-size:.875rem;color:#f87171;">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="/login" style="display:flex;flex-direction:column;gap:1.1rem;">
        @csrf

        <div>
          <label style="display:block;font-size:.85rem;font-weight:500;margin-bottom:.4rem;color:#cbd5e1;">Email</label>
          <input
            type="email"
            name="email"
            value="{{ old('email') }}"
            class="input-field {{ $errors->has('email') ? 'error' : '' }}"
            placeholder="you@example.com"
            autocomplete="email"
            required
          >
        </div>

        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem;">
            <label style="font-size:.85rem;font-weight:500;color:#cbd5e1;">Password</label>
            {{-- <a href="/forgot-password" style="font-size:.8rem;color:#6366f1;text-decoration:none;">Forgot?</a> --}}
          </div>
          <input
            type="password"
            name="password"
            class="input-field"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          >
        </div>

        <label style="display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#a5b4fc;cursor:pointer;">
          <input type="checkbox" name="remember" style="accent-color:#6366f1;">
          Remember me
        </label>

        <button type="submit" class="btn-submit">Log In</button>
      </form>

      <p style="text-align:center;margin-top:1.5rem;font-size:.875rem;color:#64748b;">
        Don't have an account?
        <a href="/register" style="color:#a5b4fc;text-decoration:none;font-weight:600;">Sign up free</a>
      </p>

    </div>
  </div>

  <footer style="text-align:center;padding:1.5rem;color:#334155;font-size:.8rem;position:relative;z-index:1;">
    © {{ date('Y') }} Geekguayaco
  </footer>

</body>
</html>
