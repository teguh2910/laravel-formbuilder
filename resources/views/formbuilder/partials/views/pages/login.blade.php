    <div id="view-login" class="login-wrap hidden">
        <div class="card login-card">
            <div class="login-head">
                <div class="login-icon">S</div>
                <h2 class="login-title">SATU FORM</h2>
                <p class="login-sub">Admin Login</p>
            </div>
            <form id="form-login" method="POST" action="{{ '/' . trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/') . '/login' }}" data-mode="server">
                @csrf
                <div class="mb-18">
                    <label class="label">Username</label>
                    <input id="login-username" name="username" class="input" placeholder="Enter username" value="{{ old('username') }}">
                </div>
                <div class="mb-24">
                    <label class="label">Password</label>
                    <input id="login-password" name="password" class="input" type="password" placeholder="Enter password">
                </div>
                @if ($errors->has('login'))
                    <div class="mb-18" style="padding:10px 12px;border-radius:8px;background:#FEE2E2;color:var(--danger);font-size:13px;">
                        {{ $errors->first('login') }}
                    </div>
                @endif
                <button id="btn-login" class="btn btn-primary" style="width:100%" type="submit">Sign In</button>
            </form>
            <div style="text-align:center;margin-top:16px;">
                <button id="btn-back-home" class="btn btn-ghost" style="font-size:13px;"><- Back to Home</button>
            </div>
            <div class="demo-box">
                <strong>Demo:</strong> superadmin / admin123 - hr.admin / hr123 - staff1 / staff123
            </div>
        </div>
    </div>
