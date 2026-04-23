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
                <button class="admin-nav-btn active" data-admin-page="dashboard">Dashboard</button>
                <button class="admin-nav-btn" data-admin-page="submit-form">Submit Form</button>
                <button class="admin-nav-btn" data-admin-page="my-submissions">My Submission</button>
                <button class="admin-nav-btn" data-admin-page="forms">FORM List</button>
                <button class="admin-nav-btn" data-admin-page="submissions">Submission</button>
                <button class="admin-nav-btn" data-admin-page="tracking">Tracking</button>
                <button class="admin-nav-btn" data-admin-page="departments">Departments</button>
                <button class="admin-nav-btn" data-admin-page="users">Users</button>
            </nav>
            <div style="padding:12px;">
                <button id="btn-admin-logout" class="btn btn-ghost" style="width:100%;color:#fff;background:rgba(255,255,255,.08);">Logout</button>
            </div>
        </aside>
        <main class="admin-main">
            <div id="admin-content"></div>
        </main>
    </div>
