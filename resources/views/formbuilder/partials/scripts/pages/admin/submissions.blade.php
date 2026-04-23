            if (adminPage === "submissions") {
                const getActiveStep = (submission) => (submission.approvalSteps || []).find(step => step.status === "in_review") || null;
                const canReviewSubmission = (submission) => {
                    const step = getActiveStep(submission);
                    if (!step || !currentUser) return false;
                    const userRole = String(currentUser.role || "").toLowerCase();
                    const currentUsername = String(currentUser.username || "").toLowerCase();
                    const stepRole = String(step.role || "").toLowerCase();
                    const stepApproverUsername = String(step.approverUsername || "").toLowerCase();
                    if (userRole === "superadmin") return true;
                    if (stepApproverUsername !== "") return currentUsername === stepApproverUsername;
                    return stepRole !== "" && userRole === stepRole;
                };
                const renderSubmissionDetail = (submission) => {
                    const tpl = templates.find(t => t.id === submission.templateId);
                    let fieldsHtml = "";
                    if (tpl) {
                        tpl.fields.forEach(f => {
                            const val = submission.data && submission.data[f.id] !== undefined ? submission.data[f.id] : "-";
                            const renderedValue = renderFieldValueHtml(f, val);
                            fieldsHtml += `
                                <div style="padding:8px 0;border-bottom:1px solid var(--gray-light);">
                                    <div class="muted" style="margin-bottom:6px;">${escapeHtml(f.label || f.id)}</div>
                                    <div style="font-weight:600;">${renderedValue}</div>
                                </div>
                            `;
                        });
                    }

                    let stepsHtml = "";
                    if (submission.approvalSteps && submission.approvalSteps.length > 0) {
                        stepsHtml = submission.approvalSteps.map((step, i) => `
                            <div style="padding:12px;background:var(--light);border-radius:8px;margin-bottom:8px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                                    <strong>Step ${i + 1}: ${escapeHtml(step.role || "-")}</strong>
                                    <span class="badge ${badgeClass(step.status)}">${statusLabel(step.status)}</span>
                                </div>
                                ${step.comments ? `<div style="margin-top:8px;font-style:italic;font-size:13px;">"${escapeHtml(step.comments)}"</div>` : ""}
                            </div>
                        `).join("");
                    } else {
                        stepsHtml = `<p class="muted">No approval steps.</p>`;
                    }

                    return `
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:16px;background:var(--light);border-radius:8px;margin-bottom:16px;">
                            <div><span class="muted" style="font-size:12px;">Submission ID</span><div style="font-weight:600;font-family:monospace;">${escapeHtml(submission.id)}</div></div>
                            <div><span class="muted" style="font-size:12px;">Submitted</span><div style="font-weight:600;">${new Date(submission.submittedAt).toLocaleString()}</div></div>
                            <div><span class="muted" style="font-size:12px;">Employee</span><div style="font-weight:600;">${escapeHtml(submission.employeeName || "-")} (${escapeHtml(submission.employeeEmail || "-")})</div></div>
                            <div><span class="muted" style="font-size:12px;">Status</span><div><span class="badge ${badgeClass(submission.status)}">${statusLabel(submission.status)}</span></div></div>
                        </div>
                        <h4 style="margin:0 0 12px;color:var(--primary);">Form Data</h4>
                        <div style="margin-bottom:16px;">${fieldsHtml || `<p class="muted">No data.</p>`}</div>
                        <h4 style="margin:0 0 12px;color:var(--primary);">Approval Steps</h4>
                        ${stepsHtml}
                    `;
                };

                content.innerHTML = `
                    <h2 style="margin:0 0 16px;color:var(--primary)">Submissions</h2>
                    <div class="card">
                        ${allowedSubs.length === 0 ? `<p class="muted">No submissions available.</p>` : `
                            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                <thead><tr style="border-bottom:2px solid var(--gray-light)">
                                    <th style="text-align:left;padding:8px">ID</th>
                                    <th style="text-align:left;padding:8px">Form</th>
                                    <th style="text-align:left;padding:8px">Employee</th>
                                    <th style="text-align:left;padding:8px">Date</th>
                                    <th style="text-align:left;padding:8px">Current Step</th>
                                    <th style="text-align:left;padding:8px">Status</th>
                                    <th style="text-align:left;padding:8px">Action</th>
                                </tr></thead>
                                <tbody>
                                    ${allowedSubs.map(s => {
                                        const activeStep = getActiveStep(s);
                                        const stepLabel = activeStep ? `Role: ${activeStep.role || "-"}` : "-";
                                        const canReview = canReviewSubmission(s);
                                        const actionButtons = canReview ? `
                                            <button class="btn btn-outline btn-admin-view-sub" data-id="${s.id}" style="padding:4px 8px;">View</button>
                                            <button class="btn btn-primary btn-sub-approve" data-id="${s.id}" style="padding:4px 8px;">Approve</button>
                                            <button class="btn btn-ghost btn-sub-reject" data-id="${s.id}" style="padding:4px 8px;color:var(--danger);">Reject</button>
                                        ` : `
                                            <button class="btn btn-outline btn-admin-view-sub" data-id="${s.id}" style="padding:4px 8px;">View</button>
                                        `;
                                        return `
                                        <tr style="border-bottom:1px solid var(--gray-light)">
                                            <td style="padding:8px">${s.id}</td>
                                            <td style="padding:8px">${s.templateName}</td>
                                            <td style="padding:8px">${s.employeeName}</td>
                                            <td style="padding:8px">${new Date(s.submittedAt).toLocaleDateString()}</td>
                                            <td style="padding:8px">${stepLabel}</td>
                                            <td style="padding:8px"><span class="badge ${badgeClass(s.status)}">${statusLabel(s.status)}</span></td>
                                            <td style="padding:8px;display:flex;gap:8px;">${actionButtons}</td>
                                        </tr>
                                    `;
                                    }).join("")}
                                </tbody>
                            </table>
                        `}
                    </div>
                    <div id="admin-sub-detail-modal" class="modal-overlay hidden">
                        <div class="card" style="max-width:780px;width:100%;max-height:90vh;overflow:auto;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                <h3 id="admin-sub-detail-title" style="margin:0;color:var(--primary);">Submission Detail</h3>
                                <button id="btn-admin-sub-detail-close" class="btn btn-ghost">Close</button>
                            </div>
                            <div id="admin-sub-detail-body"></div>
                        </div>
                    </div>
                `;

                const reviewSubmission = async (submissionId, action) => {
                    try {
                        await apiRequest(`/submissions/${encodeURIComponent(submissionId)}/review`, {
                            method: "POST",
                            body: {
                                action,
                                reviewerRole: currentUser.role,
                                reviewerUsername: currentUser.username,
                                reviewerName: currentUser.name,
                                comments: "",
                            },
                        });
                        await loadAppData();
                        renderAdmin();
                        showToast(`Submission ${action}`);
                    } catch (e) {
                        showToast(e.message || "Failed to review submission", "error");
                    }
                };

                content.querySelectorAll(".btn-sub-approve").forEach(btn => {
                    btn.addEventListener("click", () => reviewSubmission(btn.dataset.id, "approved"));
                });

                content.querySelectorAll(".btn-sub-reject").forEach(btn => {
                    btn.addEventListener("click", () => reviewSubmission(btn.dataset.id, "rejected"));
                });
                content.querySelectorAll(".btn-admin-view-sub").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const sub = allowedSubs.find(s => s.id === btn.dataset.id);
                        if (!sub) return;
                        document.getElementById("admin-sub-detail-title").textContent = `Submission Detail - ${sub.templateName || sub.id}`;
                        document.getElementById("admin-sub-detail-body").innerHTML = renderSubmissionDetail(sub);
                        document.getElementById("admin-sub-detail-modal").classList.remove("hidden");
                    });
                });
                document.getElementById("btn-admin-sub-detail-close").addEventListener("click", () => {
                    document.getElementById("admin-sub-detail-modal").classList.add("hidden");
                });

                return;
            }

            if (adminPage === "tracking") {
                content.innerHTML = `
                    <h2 style="margin:0 0 16px;color:var(--primary)">Tracking</h2>
                    <div class="card">
                        <p class="muted" style="margin:0 0 12px;">Find submission by ID.</p>
                        <div style="display:flex;gap:10px;flex-wrap:wrap;">
                            <input id="admin-track-id" class="input" placeholder="Enter submission ID" style="flex:1;min-width:220px;">
                            <button id="btn-admin-track-search" class="btn btn-primary">Search</button>
                        </div>
                        <div id="admin-track-result" style="margin-top:16px;"></div>
                    </div>
                `;

                const searchAdminTracking = () => {
                    const id = document.getElementById("admin-track-id").value.trim().toLowerCase();
                    const resultEl = document.getElementById("admin-track-result");
                    if (!id) {
                        resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Please enter submission ID.</div>`;
                        return;
                    }
                    const found = allowedSubs.find(s => s.id.toLowerCase() === id);
                    if (!found) {
                        resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Submission not found in your access scope.</div>`;
                        return;
                    }
                    resultEl.innerHTML = renderTrackingResultHtml(found, { showEmployee: true });
                };

                document.getElementById("btn-admin-track-search").addEventListener("click", searchAdminTracking);
                document.getElementById("admin-track-id").addEventListener("keydown", (e) => {
                    if (e.key === "Enter") searchAdminTracking();
                });
                return;
            }

            if (adminPage === "my-submissions") {
                const mySubs = allowedSubs
                    .filter(s => currentUser && s.employeeEmail === currentUser.email)
                    .sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt));
                const renderSubmissionDetail = (submission) => {
                    const tpl = templates.find(t => t.id === submission.templateId);
                    let fieldsHtml = "";
                    if (tpl) {
                        tpl.fields.forEach(f => {
                            const val = submission.data && submission.data[f.id] !== undefined ? submission.data[f.id] : "-";
                            const renderedValue = renderFieldValueHtml(f, val);
                            fieldsHtml += `
                                <div style="padding:8px 0;border-bottom:1px solid var(--gray-light);">
                                    <div class="muted" style="margin-bottom:6px;">${escapeHtml(f.label || f.id)}</div>
                                    <div style="font-weight:600;">${renderedValue}</div>
                                </div>
                            `;
                        });
                    }

                    let stepsHtml = "";
                    if (submission.approvalSteps && submission.approvalSteps.length > 0) {
                        stepsHtml = submission.approvalSteps.map((step, i) => `
                            <div style="padding:12px;background:var(--light);border-radius:8px;margin-bottom:8px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                                    <strong>Step ${i + 1}: ${escapeHtml(step.role || "-")}</strong>
                                    <span class="badge ${badgeClass(step.status)}">${statusLabel(step.status)}</span>
                                </div>
                                ${step.comments ? `<div style="margin-top:8px;font-style:italic;font-size:13px;">"${escapeHtml(step.comments)}"</div>` : ""}
                            </div>
                        `).join("");
                    } else {
                        stepsHtml = `<p class="muted">No approval steps.</p>`;
                    }

                    return `
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:16px;background:var(--light);border-radius:8px;margin-bottom:16px;">
                            <div><span class="muted" style="font-size:12px;">Submission ID</span><div style="font-weight:600;font-family:monospace;">${escapeHtml(submission.id)}</div></div>
                            <div><span class="muted" style="font-size:12px;">Submitted</span><div style="font-weight:600;">${new Date(submission.submittedAt).toLocaleString()}</div></div>
                            <div><span class="muted" style="font-size:12px;">Status</span><div><span class="badge ${badgeClass(submission.status)}">${statusLabel(submission.status)}</span></div></div>
                        </div>
                        <h4 style="margin:0 0 12px;color:var(--primary);">Form Data</h4>
                        <div style="margin-bottom:16px;">${fieldsHtml || `<p class="muted">No data.</p>`}</div>
                        <h4 style="margin:0 0 12px;color:var(--primary);">Approval Steps</h4>
                        ${stepsHtml}
                    `;
                };

                content.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 16px;">
                        <h2 style="margin:0;color:var(--primary)">My Submission</h2>
                        <button id="btn-admin-my-new-form" class="btn btn-primary">+ Submit Form</button>
                    </div>
                    <div class="card">
                        ${mySubs.length === 0 ? `<p class="muted">No submissions yet.</p>` : `
                            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                <thead><tr style="border-bottom:2px solid var(--gray-light)">
                                    <th style="text-align:left;padding:8px">ID</th>
                                    <th style="text-align:left;padding:8px">Form</th>
                                    <th style="text-align:left;padding:8px">Submitted</th>
                                    <th style="text-align:left;padding:8px">Status</th>
                                    <th style="text-align:left;padding:8px">Action</th>
                                </tr></thead>
                                <tbody>
                                    ${mySubs.map(s => `
                                        <tr style="border-bottom:1px solid var(--gray-light)">
                                            <td style="padding:8px;font-family:monospace;font-size:12px;color:var(--accent)">${s.id}</td>
                                            <td style="padding:8px">${s.templateName}</td>
                                            <td style="padding:8px">${new Date(s.submittedAt).toLocaleString()}</td>
                                            <td style="padding:8px"><span class="badge ${badgeClass(s.status)}">${statusLabel(s.status)}</span></td>
                                            <td style="padding:8px;"><button class="btn btn-outline btn-admin-my-view-sub" data-id="${s.id}" style="padding:4px 8px;">View</button></td>
                                        </tr>
                                    `).join("")}
                                </tbody>
                            </table>
                        `}
                    </div>
                    <div id="admin-my-sub-detail-modal" class="modal-overlay hidden">
                        <div class="card" style="max-width:780px;width:100%;max-height:90vh;overflow:auto;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                <h3 id="admin-my-sub-detail-title" style="margin:0;color:var(--primary);">Submission Detail</h3>
                                <button id="btn-admin-my-sub-detail-close" class="btn btn-ghost">Close</button>
                            </div>
                            <div id="admin-my-sub-detail-body"></div>
                        </div>
                    </div>
                `;

                const btnAdminMyNewForm = document.getElementById("btn-admin-my-new-form");
                if (btnAdminMyNewForm) {
                    btnAdminMyNewForm.addEventListener("click", () => {
                        if (currentUser && currentUser.role === "admin_department") {
                            adminPage = "submit-form";
                            renderAdmin();
                            return;
                        }
                        showView("mySubmissions");
                        if (typeof showMyView === "function") {
                            showMyView("myFormList");
                        }
                    });
                }
                content.querySelectorAll(".btn-admin-my-view-sub").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const sub = mySubs.find(s => s.id === btn.dataset.id);
                        if (!sub) return;
                        document.getElementById("admin-my-sub-detail-title").textContent = `Submission Detail - ${sub.templateName || sub.id}`;
                        document.getElementById("admin-my-sub-detail-body").innerHTML = renderSubmissionDetail(sub);
                        document.getElementById("admin-my-sub-detail-modal").classList.remove("hidden");
                    });
                });
                document.getElementById("btn-admin-my-sub-detail-close").addEventListener("click", () => {
                    document.getElementById("admin-my-sub-detail-modal").classList.add("hidden");
                });
                return;
            }
