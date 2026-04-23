<div id="view-my-submissions" class="page-wrap hidden">
        <div class="my-layout">
            <aside class="my-sidebar">
                <div style="padding:16px;border-bottom:1px solid rgba(255,255,255,.12);">
                    <div style="font-size:18px;font-weight:700;">SATU FORM</div>
                    <div class="muted" style="color:rgba(255,255,255,.75);margin-top:4px;">My Menu</div>
                </div>
                <nav style="padding:12px;display:flex;flex-direction:column;gap:8px;flex:1;">
                    <button id="btn-my-menu-submit" class="admin-nav-btn" type="button">Submit Form</button>
                    <button id="btn-my-menu-subs" class="admin-nav-btn active" type="button">My Submission</button>
                </nav>
                <div style="padding:12px;">
                    <button id="btn-my-subs-logout" class="btn btn-ghost" style="width:100%;color:#fff;background:rgba(255,255,255,.08);">Logout</button>
                </div>
            </aside>
            <main class="my-main">
                <div class="container my-container">
            <div id="my-subs-dashboard" class="card">
                <h2 style="margin:0 0 16px;color:var(--primary)">My Submissions</h2>
                <div id="my-subs-stats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;"></div>
                <div id="my-subs-list"></div>
            </div>

            <!-- Form List -->
            <div id="view-my-form-list" class="card hidden" style="margin-top:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 style="margin:0;color:var(--primary)">Available Forms</h3>
                    <button id="btn-my-form-list-back" class="btn btn-ghost"><- Back</button>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                    <div style="min-width:220px;flex:1;">
                        <label class="label">Departement Penerbit Form</label>
                        <select id="my-form-filter-department" class="input">
                            <option value="">All Department</option>
                        </select>
                    </div>
                    <div style="min-width:260px;flex:2;">
                        <label class="label">Search Nama Form</label>
                        <input id="my-form-search-name" class="input" type="text" placeholder="Cari nama form...">
                    </div>
                </div>
                <div id="my-form-list" class="grid"></div>
            </div>

            <!-- Fill Form for non_admin -->
            <div id="view-my-fill-form" class="card hidden" style="margin-top:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 id="my-selected-form-title" style="margin:0;color:var(--primary)">Fill Form</h3>
                    <button id="btn-my-form-back" class="btn btn-ghost"><- Back</button>
                </div>
                <div id="my-prereq-check-section" class="hidden" style="margin-bottom:14px;padding:12px;background:#F8FAFC;border:1px solid var(--gray-light);border-radius:10px;">
                    <div style="font-weight:600;color:var(--primary);margin-bottom:8px;">Prerequisite Verification</div>
                    <p id="my-prereq-check-help" class="muted" style="margin:0 0 10px;"></p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <input id="my-prereq-submission-id" class="input" placeholder="Input prerequisite submission ID" style="flex:1;min-width:220px;">
                        <button id="btn-my-prereq-check" type="button" class="btn btn-outline">Check ID</button>
                    </div>
                    <div id="my-prereq-check-result" style="margin-top:8px;font-size:13px;"></div>
                </div>
                <div id="my-internal-approver-section" class="hidden" style="margin-bottom:14px;padding:12px;background:#F8FAFC;border:1px solid var(--gray-light);border-radius:10px;">
                    <div style="font-weight:600;color:var(--primary);margin-bottom:8px;">Internal Approval Assignment</div>
                    <div id="my-internal-approver-list"></div>
                </div>
                <div id="my-dynamic-fields"></div>
                <div style="margin-top:20px;display:flex;gap:10px;">
                    <button id="btn-my-submit-form" class="btn btn-primary">Submit Form</button>
                </div>
            </div>

            <div id="view-my-track" class="card hidden" style="margin-top:16px;">
                <h3 style="margin:0 0 12px;color:var(--primary)">Tracking</h3>
                <p class="muted" style="margin:0 0 16px;">Check your submission status by ID.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input id="my-track-id" class="input" placeholder="Enter submission ID" style="flex:1;min-width:220px;">
                    <button id="btn-my-track-search" class="btn btn-primary">Search</button>
                </div>
                <div id="my-track-result" style="margin-top:16px;"></div>
            </div>

            <!-- Submission Detail Modal -->
            <div id="my-sub-detail-modal" class="modal-overlay hidden">
                <div class="card" style="max-width:720px;width:100%;max-height:90vh;overflow:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <div>
                            <div id="my-sub-detail-id" style="font-family:monospace;color:var(--accent);font-size:13px;"></div>
                            <h3 id="my-sub-detail-title" style="margin:4px 0 0;color:var(--primary);"></h3>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span id="my-sub-detail-status" class="badge"></span>
                            <button id="btn-my-sub-detail-close" class="btn btn-ghost">Close</button>
                        </div>
                    </div>
                    <div id="my-sub-detail-body"></div>
                </div>
            </div>

            <!-- Progress Status Modal -->
            <div id="my-progress-modal" class="modal-overlay hidden">
                <div class="card" style="max-width:760px;width:100%;max-height:90vh;overflow:auto;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <h3 id="my-progress-title" style="margin:0;color:var(--primary);">Progress Status</h3>
                        <button id="btn-my-progress-close" class="btn btn-ghost">Close</button>
                    </div>
                    <div id="my-progress-body"></div>
                </div>
            </div>
                </div>
            </main>
        </div>
    </div>
