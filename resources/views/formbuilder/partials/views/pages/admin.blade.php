    @php($fbRouteBase = $formbuilderRouteBase ?? ('/' . trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/')))
    <div id="view-admin" class="admin-wrap hidden">
        <aside class="admin-sidebar">
            <div style="padding:18px;border-bottom:1px solid rgba(255,255,255,.12);">
                <div class="brand" style="margin-bottom:12px;">
                    <div class="brand-logo">S</div>
                    <span class="brand-text" style="font-size:18px;">SATU FORM</span>
                </div>
                <div style="padding:10px;background:rgba(255,255,255,.1);border-radius:8px;">
                    <div id="admin-user-name" style="font-weight:700;font-size:14px;">-</div>
                    <div id="admin-user-role" style="font-size:12px;opacity:.8;">-</div>
                </div>
            </div>
            <nav style="padding:12px;display:flex;flex-direction:column;gap:6px;flex:1;">
                <a class="admin-nav-btn active" data-admin-page="dashboard" href="{{ $fbRouteBase . '/admin?page=dashboard' }}">Dashboard</a>
                <a class="admin-nav-btn" data-admin-page="submit-form" href="{{ $fbRouteBase . '/admin?page=submit-form' }}">Submit Form</a>
                <a class="admin-nav-btn" data-admin-page="my-submissions" href="{{ $fbRouteBase . '/admin?page=my-submissions' }}">My Submission</a>
                <a class="admin-nav-btn" data-admin-page="forms" href="{{ $fbRouteBase . '/admin?page=forms' }}">FORM List</a>
                <a class="admin-nav-btn" data-admin-page="submissions" href="{{ $fbRouteBase . '/admin?page=submissions' }}">Submission</a>
                <a class="admin-nav-btn" data-admin-page="tracking" href="{{ $fbRouteBase . '/admin?page=tracking' }}">Tracking</a>
                <a class="admin-nav-btn" data-admin-page="departments" href="{{ $fbRouteBase . '/admin?page=departments' }}">Departments</a>
                <a class="admin-nav-btn" data-admin-page="users" href="{{ $fbRouteBase . '/admin?page=users' }}">Users</a>
            </nav>
            <div style="padding:12px;">
                <button id="btn-admin-logout" class="btn btn-ghost" style="width:100%;color:#fff;background:rgba(255,255,255,.08);">Logout</button>
            </div>
        </aside>
        <main class="admin-main">
            <div id="admin-content"></div>
        </main>
        <form id="admin-submit-form" method="POST" action="{{ $fbRouteBase . '/forms/submit-auth' }}" class="hidden">
            @csrf
            <input type="hidden" name="redirect_to" value="admin">
            <input type="hidden" id="admin-submit-payload" name="payload" value="">
        </form>
    </div>
