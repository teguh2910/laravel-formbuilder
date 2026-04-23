    <div id="view-fill-form" class="page-wrap hidden">
        <div class="topbar">
            <button id="btn-fill-form-back" class="btn btn-ghost" style="color:#fff;padding:6px 12px;"><- Back</button>
            <strong id="selected-form-title">Fill Form</strong>
        </div>
        <div class="container">
            <div class="card" style="margin-bottom:16px;">
                <h3 style="margin:0 0 14px;color:var(--primary)">Employee Information</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field-box"><label class="label">Full Name *</label><input id="emp-name" class="input"></div>
                    <div class="field-box"><label class="label">Email *</label><input id="emp-email" type="email" class="input"></div>
                </div>
            </div>
            <div class="card">
                <h3 style="margin:0 0 14px;color:var(--primary)">Form Fields</h3>
                <div id="prereq-check-section" class="hidden" style="margin-bottom:14px;padding:12px;background:#F8FAFC;border:1px solid var(--gray-light);border-radius:10px;">
                    <div style="font-weight:600;color:var(--primary);margin-bottom:8px;">Prerequisite Verification</div>
                    <p id="prereq-check-help" class="muted" style="margin:0 0 10px;"></p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <input id="prereq-submission-id" class="input" placeholder="Input prerequisite submission ID" style="flex:1;min-width:220px;">
                        <button id="btn-prereq-check" type="button" class="btn btn-outline">Check ID</button>
                    </div>
                    <div id="prereq-check-result" style="margin-top:8px;font-size:13px;"></div>
                </div>
                <div id="internal-approver-section" class="hidden" style="margin-bottom:14px;padding:12px;background:#F8FAFC;border:1px solid var(--gray-light);border-radius:10px;">
                    <div style="font-weight:600;color:var(--primary);margin-bottom:8px;">Internal Approval Assignment</div>
                    <div id="internal-approver-list"></div>
                </div>
                <div id="dynamic-fields"></div>
                <button id="btn-submit-form" class="btn btn-primary">Submit Form</button>
            </div>
        </div>
    </div>
