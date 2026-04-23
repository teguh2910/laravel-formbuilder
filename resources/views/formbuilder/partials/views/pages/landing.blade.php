@php($fbRouteBase = '/ais-v4/satuform')
<div id="view-landing" class="landing-wrap">
        <nav class="landing-nav">
            <div class="brand">
                <div class="brand-logo">S</div>
                <span class="brand-text">SATU FORM</span>
            </div>
            <a id="btn-open-login" href="{{ '/ais-v4/satuform/login' }}" class="btn btn-outline" style="border-color: rgba(255,255,255,.3); color: #fff; text-decoration:none;">Admin Login</a>
        </nav>
        <div class="landing-main">
            <div class="landing-inner">
                <h1 class="landing-title">SATU FORM<br>Management System</h1>
                <p class="landing-sub">Submit forms, track approvals, manage workflows. No login required for submissions.</p>
                <div class="landing-actions">
                    <a id="btn-open-fill" href="{{ $fbRouteBase . '/forms' }}" class="btn btn-primary" style="background:#fff;color:var(--primary); text-decoration:none;">Submit a Form</a>
                    <a id="btn-open-track" href="{{ $fbRouteBase . '/track' }}" class="btn btn-outline" style="border-color:rgba(255,255,255,.4);color:#fff; text-decoration:none;">Track Submission</a>
                </div>
                <div class="feature-grid">
                    <div class="feature"><div class="feature-title">No Login Required</div><div class="feature-sub">Submit forms instantly</div></div>
                    <div class="feature"><div class="feature-title">Real-time Tracking</div><div class="feature-sub">Monitor approval status</div></div>
                    <div class="feature"><div class="feature-title">Auto Email Approval</div><div class="feature-sub">Routed to supervisors</div></div>
                    <div class="feature"><div class="feature-title">Table and Calc</div><div class="feature-sub">Built-in spreadsheet math</div></div>
                </div>
            </div>
        </div>
    </div>
