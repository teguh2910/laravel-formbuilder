    <div id="view-track" class="page-wrap hidden">
        <div class="topbar">
            <button id="btn-track-back" class="btn btn-ghost" style="color:#fff;padding:6px 12px;"><- Back</button>
            <strong>Track Submission</strong>
        </div>
        <div class="container">
            <div class="card" style="max-width:760px;margin:0 auto;">
                <h2 style="margin:0 0 8px;color:var(--primary)">Check Status</h2>
                <p class="muted" style="margin:0 0 14px">Enter your Submission ID to view progress.</p>
                <form id="form-track-search" method="GET" action="{{ ($formbuilderRouteBase ?? ('/' . trim((string) config('formbuilder.route_prefix', 'formbuilder'), '/'))) . '/track' }}" style="display:flex;gap:8px;margin-bottom:14px;">
                    <input id="track-id" name="id" class="input" placeholder="e.g. SUB-ABC123">
                    <button id="btn-track-search" class="btn btn-primary" type="submit">Search</button>
                </form>
                <div id="track-result"></div>
            </div>
        </div>
    </div>
