// ==================== MY SUBMISSIONS (non_admin) ====================

        let myFormData = {};
        let mySelectedTemplate = null;
        let myVerifiedPrerequisiteSubmissionId = null;
        let myInternalApproverSelections = {};
        let myFormDepartmentFilter = "";
        let myFormSearchQuery = "";

        function setMyMenuActive(section) {
            const submitBtn = document.getElementById("btn-my-menu-submit");
            const subsBtn = document.getElementById("btn-my-menu-subs");
            if (!submitBtn || !subsBtn) return;
            submitBtn.classList.toggle("active", section === "submit");
            subsBtn.classList.toggle("active", section === "submissions");
        }

        function renderMySubmissions() {
            const el = document.getElementById("my-subs-list");
            const mySubs = submissions.filter(s => s.employeeEmail === currentUser.email);

            if (mySubs.length === 0) {
                el.innerHTML = '<p class="muted" style="text-align:center;padding:40px;">No submissions yet.</p>';
            } else {
                const recent = [...mySubs].sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt));
                el.innerHTML = `
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <thead><tr style="border-bottom:2px solid var(--gray-light)">
                            <th style="text-align:left;padding:8px">ID</th>
                            <th style="text-align:left;padding:8px">Form</th>
                            <th style="text-align:left;padding:8px">Submitted</th>
                            <th style="text-align:left;padding:8px">Status</th>
                            <th style="text-align:left;padding:8px">Action</th>
                        </tr></thead>
                        <tbody>
                            ${recent.map(s => `
                                <tr style="border-bottom:1px solid var(--gray-light)">
                                    <td style="padding:8px;font-family:monospace;font-size:12px;color:var(--accent)">${s.id}</td>
                                    <td style="padding:8px">${s.templateName}</td>
                                    <td style="padding:8px">${new Date(s.submittedAt).toLocaleDateString()}</td>
                                    <td style="padding:8px">
                                        <button
                                            class="badge ${badgeClass(s.status)} btn-my-status-progress"
                                            data-sub-id="${escapeHtml(s.id)}"
                                            type="button"
                                            style="border:none;cursor:pointer;"
                                            title="View progress status"
                                        >${statusLabel(s.status)}</button>
                                    </td>
                                    <td style="padding:8px">
                                        <button class="btn btn-outline btn-sm" onclick="openMySubDetail('${s.id}')">View</button>
                                    </td>
                                </tr>
                            `).join("")}
                        </tbody>
                    </table>
                `;
            }

            el.querySelectorAll(".btn-my-status-progress").forEach(btn => {
                btn.addEventListener("click", () => {
                    openMyProgressStatus(btn.dataset.subId);
                });
            });
        }

        function renderMyStats() {
            const mySubs = submissions.filter(s => s.employeeEmail === currentUser.email);
            const pend = mySubs.filter(s => s.status === "pending" || s.status === "in_review").length;
            const appr = mySubs.filter(s => s.status === "approved").length;
            document.getElementById("my-subs-stats").innerHTML = `
                <div class="stat-card"><div class="stat-num">${mySubs.length}</div><div class="muted">Total</div></div>
                <div class="stat-card"><div class="stat-num" style="color:var(--warn)">${pend}</div><div class="muted">Pending</div></div>
                <div class="stat-card"><div class="stat-num" style="color:var(--success)">${appr}</div><div class="muted">Approved</div></div>
            `;
        }

        function openMySubDetail(id) {
            const sub = submissions.find(s => s.id === id);
            if (!sub) return;
            document.getElementById("my-sub-detail-id").textContent = sub.id;
            document.getElementById("my-sub-detail-title").textContent = sub.templateName;
            const statusBadge = document.getElementById("my-sub-detail-status");
            statusBadge.textContent = statusLabel(sub.status);
            statusBadge.className = `badge ${badgeClass(sub.status)}`;

            const tpl = templates.find(t => t.id === sub.templateId);
            let fieldsHtml = '';
            if (tpl) {
                tpl.fields.forEach(f => {
                    const val = sub.data && sub.data[f.id] !== undefined ? sub.data[f.id] : "-";
                    const renderedValue = renderFieldValueHtml(f, val);
                    fieldsHtml += `
                        <div style="padding:8px 0;border-bottom:1px solid var(--gray-light)">
                            <div class="muted" style="margin-bottom:6px;">${escapeHtml(f.label || f.id)}</div>
                            <div style="font-weight:600;">${renderedValue}</div>
                        </div>
                    `;
                });
            }

            let stepsHtml = '';
            if (sub.approvalSteps && sub.approvalSteps.length > 0) {
                stepsHtml = sub.approvalSteps.map((step, i) => `
                    <div style="padding:12px;background:var(--light);border-radius:8px;margin-bottom:8px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <strong>Step ${i + 1}: ${step.role || "-"}</strong>
                            <span class="badge ${badgeClass(step.status)}">${statusLabel(step.status)}</span>
                        </div>
                        ${step.comments ? `<div style="margin-top:8px;font-style:italic;font-size:13px">"${step.comments}"</div>` : ""}
                    </div>
                `).join("");
            } else {
                stepsHtml = '<p class="muted">No approval steps — auto-approved.</p>';
            }

            document.getElementById("my-sub-detail-body").innerHTML = `
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:16px;background:var(--light);border-radius:8px;margin-bottom:16px;">
                    <div><span class="muted" style="font-size:12px">Department</span><div style="font-weight:600">${depts.find(d => d.id === sub.department)?.name || "-"}</div></div>
                    <div><span class="muted" style="font-size:12px">Submitted</span><div style="font-weight:600">${new Date(sub.submittedAt).toLocaleString()}</div></div>
                </div>
                <h4 style="margin:0 0 12px;color:var(--primary)">Form Data</h4>
                <div style="margin-bottom:16px;">${fieldsHtml}</div>
                <h4 style="margin:0 0 12px;color:var(--primary)">Approval Steps</h4>
                ${stepsHtml}
            `;
            document.getElementById("my-sub-detail-modal").classList.remove("hidden");
        }

        function openMyProgressStatus(id) {
            const sub = submissions.find(s => s.id === id);
            if (!sub) {
                showToast("Submission not found", "error");
                return;
            }

            const titleEl = document.getElementById("my-progress-title");
            const bodyEl = document.getElementById("my-progress-body");
            const modalEl = document.getElementById("my-progress-modal");
            if (!titleEl || !bodyEl || !modalEl) return;

            titleEl.textContent = `Progress Status - ${sub.id}`;
            bodyEl.innerHTML = renderTrackingResultHtml(sub, { showEmployee: false });
            modalEl.classList.remove("hidden");
        }

        function renderMyFormList() {
            const el = document.getElementById("my-form-list");
            const departmentEl = document.getElementById("my-form-filter-department");
            const published = templates.filter(t => t.published);

            if (departmentEl) {
                const deptIds = [...new Set(published.map(t => t.department).filter(Boolean))];
                const deptOptions = deptIds
                    .map(deptId => {
                        const dept = depts.find(d => d.id === deptId);
                        if (!dept) return null;
                        return `<option value="${escapeHtml(dept.id)}">${escapeHtml(dept.name)} (${escapeHtml(dept.code || "-")})</option>`;
                    })
                    .filter(Boolean)
                    .join("");
                departmentEl.innerHTML = `<option value="">All Department</option>${deptOptions}`;
                departmentEl.value = myFormDepartmentFilter || "";
            }

            if (published.length === 0) {
                el.innerHTML = '<p class="muted" style="text-align:center;padding:40px;">No published forms available.</p>';
                return;
            }

            const filtered = published.filter(form => {
                const byDepartment = !myFormDepartmentFilter || form.department === myFormDepartmentFilter;
                const byName = !myFormSearchQuery || (form.name || "").toLowerCase().includes(myFormSearchQuery.toLowerCase());
                return byDepartment && byName;
            });

            if (filtered.length === 0) {
                el.innerHTML = '<p class="muted" style="text-align:center;padding:26px;">No form matched your filter.</p>';
                return;
            }

            el.innerHTML = `
                <div style="overflow:auto;border:1px solid var(--gray-light);border-radius:10px;background:#fff;">
                    <table style="width:100%;border-collapse:collapse;min-width:860px;font-size:14px;">
                        <thead>
                            <tr style="background:#F8FAFC;border-bottom:1px solid var(--gray-light);">
                                <th style="text-align:left;padding:10px 12px;">Nama Form</th>
                                <th style="text-align:left;padding:10px 12px;">Departement Penerbit</th>
                                <th style="text-align:left;padding:10px 12px;">Description</th>
                                <th style="text-align:left;padding:10px 12px;">Fields</th>
                                <th style="text-align:left;padding:10px 12px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${filtered.map(form => {
                                const dept = depts.find(d => d.id === form.department);
                                return `
                                    <tr style="border-bottom:1px solid var(--gray-light);">
                                        <td style="padding:10px 12px;font-weight:600;color:var(--primary);">${escapeHtml(form.name || "-")}</td>
                                        <td style="padding:10px 12px;">${escapeHtml(dept ? `${dept.name} (${dept.code || "-"})` : "-")}</td>
                                        <td style="padding:10px 12px;" class="muted">${escapeHtml(form.description || "No description")}</td>
                                        <td style="padding:10px 12px;">${form.fields.length}</td>
                                        <td style="padding:10px 12px;">
                                            <button class="btn btn-outline btn-my-open-form" data-form-id="${escapeHtml(form.id)}">Fill Form</button>
                                        </td>
                                    </tr>
                                `;
                            }).join("")}
                        </tbody>
                    </table>
                </div>
            `;

            el.querySelectorAll(".btn-my-open-form").forEach(item => {
                item.addEventListener("click", () => {
                    const tpl = templates.find(t => t.id === item.dataset.formId);
                    if (!tpl) return;
                    mySelectedTemplate = tpl;
                    myFormData = {};
                    myVerifiedPrerequisiteSubmissionId = null;
                    myInternalApproverSelections = {};
                    renderMyDynamicFields();
                    document.getElementById("my-selected-form-title").textContent = tpl.name;
                    showMyView("myFillForm");
                });
            });
        }

        function getMyCalcValue(field) {
            if (!field.formula) return 0;
            let expr = field.formula;
            mySelectedTemplate.fields.forEach(f => {
                const k = `{${f.label}}`;
                const v = parseFloat(myFormData[f.id] || 0) || 0;
                expr = expr.split(k).join(v);
            });
            const n = safeCalc(expr);
            return Number.isFinite(n) ? n : 0;
        }

        function renderMyDynamicFields() {
            const wrap = document.getElementById("my-dynamic-fields");
            wrap.innerHTML = "";
            if (!mySelectedTemplate) return;
            const prereqSection = document.getElementById("my-prereq-check-section");
            const prereqHelp = document.getElementById("my-prereq-check-help");
            const prereqInput = document.getElementById("my-prereq-submission-id");
            const prereqResult = document.getElementById("my-prereq-check-result");
            const internalApproverSection = document.getElementById("my-internal-approver-section");
            const internalApproverList = document.getElementById("my-internal-approver-list");
            const submitBtn = document.getElementById("btn-my-submit-form");

            if (mySelectedTemplate.prerequisiteFormId) {
                const prereqTemplate = templates.find(t => t.id === mySelectedTemplate.prerequisiteFormId);
                prereqSection.classList.remove("hidden");
                prereqHelp.textContent = `This form requires approved submission ID from: ${prereqTemplate?.name || mySelectedTemplate.prerequisiteFormId}.`;
                if (myVerifiedPrerequisiteSubmissionId) {
                    prereqInput.value = myVerifiedPrerequisiteSubmissionId;
                    prereqResult.innerHTML = `<span style="color:var(--success);">Verified. Prerequisite submission is approved.</span>`;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove("hidden");
                    wrap.classList.remove("hidden");
                } else {
                    prereqResult.innerHTML = `<span class="muted">Please check prerequisite submission ID first.</span>`;
                    submitBtn.disabled = true;
                    submitBtn.classList.add("hidden");
                    wrap.classList.add("hidden");
                    return;
                }
            } else {
                prereqSection.classList.add("hidden");
                prereqHelp.textContent = "";
                prereqInput.value = "";
                prereqResult.innerHTML = "";
                myVerifiedPrerequisiteSubmissionId = null;
                submitBtn.disabled = false;
                submitBtn.classList.remove("hidden");
                wrap.classList.remove("hidden");
            }

            const internalApprovalFlow = (mySelectedTemplate?.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external");
            const approverCandidates = (users || []).filter(u => (u.role || "").toLowerCase() !== "non_admin");
            if (internalApprovalFlow.length > 0) {
                internalApproverSection.classList.remove("hidden");
                if (approverCandidates.length === 0) {
                    internalApproverList.innerHTML = `<p class="muted">No approver user available.</p>`;
                } else {
                    internalApproverList.innerHTML = internalApprovalFlow.map((a, i) => {
                        const levelLabel = a.role || `Superior Level ${i + 1}`;
                        const selectedUsername = myInternalApproverSelections[a.id] || "";
                        return `
                            <div style="margin-bottom:10px;">
                                <label class="label">${escapeHtml(levelLabel)} *</label>
                                <select class="input my-internal-approver" data-step-id="${a.id}">
                                    <option value="">Select approver</option>
                                    ${approverCandidates.map(u => `<option value="${escapeHtml(u.username)}" ${selectedUsername === u.username ? "selected" : ""}>${escapeHtml(u.name)} (${escapeHtml(u.username)})</option>`).join("")}
                                </select>
                            </div>
                        `;
                    }).join("");
                    internalApproverList.querySelectorAll(".my-internal-approver").forEach(el => {
                        el.addEventListener("change", () => {
                            myInternalApproverSelections[el.dataset.stepId] = el.value || "";
                        });
                    });
                }
            } else {
                internalApproverSection.classList.add("hidden");
                internalApproverList.innerHTML = "";
            }

            mySelectedTemplate.fields.forEach((field) => {
                const box = document.createElement("div");
                box.className = "field-box";
                const req = field.required ? "*" : "";
                let html = `<label class="label">${field.label} ${req}</label>`;

                if (field.type === "text" || field.type === "email" || field.type === "date" || field.type === "number") {
                    const t = field.type === "text" ? "text" : field.type;
                    html += `<input data-fid="${field.id}" data-ftype="${field.type}" type="${t}" class="input">`;
                } else if (field.type === "textarea") {
                    html += `<textarea data-fid="${field.id}" data-ftype="textarea" class="input" style="min-height:90px"></textarea>`;
                } else if (field.type === "dropdown") {
                    html += `<select data-fid="${field.id}" data-ftype="dropdown" class="input"><option value="">Select...</option>${(field.options || []).map(o => `<option value="${o}">${o}</option>`).join("")}</select>`;
                } else if (field.type === "radio") {
                    html += `<div>${(field.options || []).map(o => `<label style="margin-right:12px"><input data-fid="${field.id}" data-ftype="radio" type="radio" name="${field.id}" value="${o}"> ${o}</label>`).join("")}</div>`;
                } else if (field.type === "checkbox") {
                    html += `<div>${(field.options || []).map(o => `<label style="margin-right:12px"><input data-fid="${field.id}" data-ftype="checkbox" type="checkbox" value="${o}"> ${o}</label>`).join("")}</div>`;
                } else if (field.type === "file") {
                    html += `<input data-fid="${field.id}" data-ftype="file" type="file" class="input">`;
                } else if (field.type === "calculation") {
                    html += `<div style="padding:12px;border-radius:8px;background:#EDF3FF;color:var(--primary);font-weight:700" data-calc="${field.id}">${getMyCalcValue(field)}</div>`;
                } else if (field.type === "table") {
                    const tableCols = getTableColumnsForPreview(field);
                    const tableRows = getTableRowCount(field, 3);
                    html += `
                        <div style="overflow:auto;border:1px solid var(--gray-light);border-radius:8px;background:#fff;">
                            <table style="width:100%;border-collapse:collapse;min-width:520px;">
                                <thead>
                                    <tr style="background:#F8FAFC;">
                                        ${tableCols.map(col => `<th style="text-align:left;padding:8px;border-bottom:1px solid var(--gray-light);font-size:12px;">${escapeHtml(col.name || "Column")}</th>`).join("")}
                                    </tr>
                                </thead>
                                <tbody>
                                    ${Array.from({ length: tableRows }).map((_, rowIndex) => `
                                        <tr>
                                            ${tableCols.map((col, colIndex) => `
                                                <td style="padding:6px;border-bottom:1px solid var(--gray-light);">
                                                    <input class="input" data-fid="${field.id}" data-ftype="table-cell" data-row="${rowIndex}" data-col="${colIndex}" type="${col.type === "number" || col.type === "calc" ? "number" : "text"}" ${col.type === "calc" ? "readonly" : ""}>
                                                </td>
                                            `).join("")}
                                        </tr>
                                    `).join("")}
                                </tbody>
                            </table>
                        </div>
                    `;
                }

                box.innerHTML = html;
                wrap.appendChild(box);
            });

            wrap.querySelectorAll("[data-fid]").forEach(el => {
                const updateValue = () => {
                    const fid = el.dataset.fid;
                    const ftype = el.dataset.ftype;
                    if (ftype === "checkbox") {
                        const checks = [...wrap.querySelectorAll(`input[data-fid="${fid}"]:checked`)];
                        myFormData[fid] = checks.map(c => c.value);
                    } else if (ftype === "table-cell") {
                        const field = mySelectedTemplate.fields.find(f => f.id === fid);
                        const cols = getTableColumnsForPreview(field);
                        const rowNodes = wrap.querySelectorAll(`input[data-fid="${fid}"][data-ftype="table-cell"]`);
                        const rowMap = {};
                        rowNodes.forEach(node => {
                            const r = Number(node.dataset.row);
                            const c = Number(node.dataset.col);
                            if (!rowMap[r]) rowMap[r] = {};
                            const colName = cols[c]?.name || `col_${c + 1}`;
                            rowMap[r][colName] = node.value;
                        });
                        myFormData[fid] = Object.keys(rowMap).sort((a, b) => Number(a) - Number(b)).map(k => rowMap[k]);
                    } else if (ftype === "radio") {
                        myFormData[fid] = el.value;
                    } else if (ftype === "file") {
                        myFormData[fid] = el.files[0] ? el.files[0].name : "";
                    } else {
                        myFormData[fid] = el.value;
                    }
                    mySelectedTemplate.fields.filter(f => f.type === "calculation").forEach(f => {
                        const calcEl = wrap.querySelector(`[data-calc="${f.id}"]`);
                        if (calcEl) calcEl.textContent = getMyCalcValue(f);
                    });
                };
                el.addEventListener("change", updateValue);
                el.addEventListener("input", updateValue);
            });
        }

        async function submitMyForm() {
            if (!mySelectedTemplate) return;

            let prerequisiteSubmissionId = null;
            if (mySelectedTemplate.prerequisiteFormId) {
                const prereqInput = document.getElementById("my-prereq-submission-id");
                const prereqCheck = await verifyPrerequisiteSubmissionById(mySelectedTemplate, prereqInput ? prereqInput.value : "");
                if (!prereqCheck.ok) {
                    showToast(prereqCheck.message, "error");
                    return;
                }
                prerequisiteSubmissionId = prereqCheck.submissionId;
            }

            for (const f of mySelectedTemplate.fields) {
                if (!f.required) continue;
                const val = myFormData[f.id];
                const empty = val === undefined || val === null || val === "" || (Array.isArray(val) && val.length === 0);
                if (empty) {
                    showToast(`Field required: ${f.label}`, "error");
                    return;
                }
            }

            const internalApprovalFlow = (mySelectedTemplate.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external");
            for (const step of internalApprovalFlow) {
                const approverUsername = (myInternalApproverSelections[step.id] || "").trim();
                if (!approverUsername) {
                    showToast(`Please select approver for ${step.role || "internal step"}`, "error");
                    return;
                }
            }

            const id = genSubId();
            const payload = {
                id,
                templateId: mySelectedTemplate.id,
                templateName: mySelectedTemplate.name,
                department: mySelectedTemplate.department || null,
                employeeName: currentUser.name,
                employeeEmail: currentUser.email,
                data: { ...myFormData },
                prerequisiteSubmissionId,
                approvalSteps: (mySelectedTemplate.approvalFlow || []).map((a, i) => ({
                    ...(function() {
                        const approvalType = a.approvalType === "external" ? "external" : "internal";
                        const approverUsername = approvalType === "internal"
                            ? (myInternalApproverSelections[a.id] || "").trim()
                            : ((users || []).find(u => u.name === (a.role || ""))?.username || null);
                        const approverUser = approverUsername
                            ? (users || []).find(u => u.username === approverUsername)
                            : null;
                        return {
                            approverUsername: approverUsername || null,
                            approverName: approverUser?.name || (approvalType === "external" ? (a.role || null) : null),
                        };
                    })(),
                    id: a.id || `APR-${i + 1}`,
                    role: a.role || a.title || a.name || "spv",
                    approvalType: a.approvalType === "external" ? "external" : "internal",
                    order: i,
                    status: i === 0 ? "in_review" : "pending",
                })),
                status: (mySelectedTemplate.approvalFlow || []).length > 0 ? "in_review" : "approved",
                submittedAt: new Date().toISOString(),
            };

            try {
                await apiRequest("/submissions", {
                    method: "POST",
                    body: payload,
                });
                await loadAppData();
            } catch (e) {
                showToast(e.message || "Failed to submit form", "error");
                return;
            }

            mySelectedTemplate = null;
            myFormData = {};
            myVerifiedPrerequisiteSubmissionId = null;
            myInternalApproverSelections = {};
            showToast(`Form submitted. Tracking ID: ${id}`);
            showMyView("myDashboard");
        }

        function showMyView(view) {
            document.getElementById("view-my-submissions").classList.remove("hidden");
            document.getElementById("view-my-form-list").classList.add("hidden");
            document.getElementById("view-my-fill-form").classList.add("hidden");
            document.getElementById("view-my-track").classList.add("hidden");
            document.getElementById("my-subs-dashboard").style.display = "none";
            document.getElementById("my-sub-detail-modal").classList.add("hidden");
            document.getElementById("my-progress-modal").classList.add("hidden");

            if (view === "myDashboard") {
                document.getElementById("my-subs-dashboard").style.display = "block";
                renderMyStats();
                renderMySubmissions();
                setMyMenuActive("submissions");
            } else if (view === "myFormList") {
                document.getElementById("view-my-form-list").classList.remove("hidden");
                renderMyFormList();
                setMyMenuActive("submit");
            } else if (view === "myFillForm") {
                document.getElementById("view-my-fill-form").classList.remove("hidden");
                setMyMenuActive("submit");
            } else if (view === "myTrack") {
                document.getElementById("view-my-track").classList.remove("hidden");
                setMyMenuActive("submissions");
            }
        }

        function searchMyTracking() {
            const id = document.getElementById("my-track-id").value.trim().toLowerCase();
            const resultEl = document.getElementById("my-track-result");
            if (!id) {
                resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Please enter submission ID.</div>`;
                return;
            }
            const mySubs = submissions.filter(s => currentUser && s.employeeEmail === currentUser.email);
            const found = mySubs.find(s => s.id.toLowerCase() === id);
            if (!found) {
                resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Submission not found for your account.</div>`;
                return;
            }
            resultEl.innerHTML = renderTrackingResultHtml(found, { showEmployee: false });
        }

        // Event listeners
        const btnMySubsBack = document.getElementById("btn-my-subs-back");
        if (btnMySubsBack) {
            btnMySubsBack.addEventListener("click", () => {
                showMyView("myDashboard");
            });
        }

        document.getElementById("btn-my-subs-logout").addEventListener("click", () => {
            currentUser = null;
            clearCurrentUserSession();
            showView("landing");
        });

        document.getElementById("btn-my-form-list-back").addEventListener("click", () => {
            showMyView("myDashboard");
        });

        document.getElementById("btn-my-form-back").addEventListener("click", () => {
            mySelectedTemplate = null;
            myFormData = {};
            myInternalApproverSelections = {};
            showMyView("myFormList");
        });

        document.getElementById("btn-my-submit-form").addEventListener("click", () => {
            submitMyForm();
        });
        document.getElementById("btn-my-prereq-check").addEventListener("click", async () => {
            if (!mySelectedTemplate || !mySelectedTemplate.prerequisiteFormId) return;
            const prereqInput = document.getElementById("my-prereq-submission-id");
            const prereqResult = document.getElementById("my-prereq-check-result");
            const submitBtn = document.getElementById("btn-my-submit-form");

            const check = await verifyPrerequisiteSubmissionById(mySelectedTemplate, prereqInput.value);
            if (!check.ok) {
                myVerifiedPrerequisiteSubmissionId = null;
                submitBtn.disabled = true;
                submitBtn.classList.add("hidden");
                prereqResult.innerHTML = `<span style="color:var(--danger);">${escapeHtml(check.message)}</span>`;
                showToast(check.message, "error");
                return;
            }

            myVerifiedPrerequisiteSubmissionId = check.submissionId;
            prereqInput.value = check.submissionId;
            submitBtn.disabled = false;
            submitBtn.classList.remove("hidden");
            prereqResult.innerHTML = `<span style="color:var(--success);">Verified. Prerequisite submission is approved.</span>`;
            showToast("Prerequisite verified");
            renderMyDynamicFields();
        });
        document.getElementById("my-prereq-submission-id").addEventListener("input", () => {
            const submitBtn = document.getElementById("btn-my-submit-form");
            const prereqResult = document.getElementById("my-prereq-check-result");
            if (!mySelectedTemplate || !mySelectedTemplate.prerequisiteFormId) return;
            const currentValue = document.getElementById("my-prereq-submission-id").value.trim().toUpperCase();
            if (currentValue === myVerifiedPrerequisiteSubmissionId) return;
            myVerifiedPrerequisiteSubmissionId = null;
            submitBtn.disabled = true;
            submitBtn.classList.add("hidden");
            prereqResult.innerHTML = `<span class="muted">ID changed. Please check again.</span>`;
            renderMyDynamicFields();
        });
        document.getElementById("btn-my-menu-submit").addEventListener("click", () => {
            showMyView("myFormList");
        });
        document.getElementById("btn-my-menu-subs").addEventListener("click", () => {
            showMyView("myDashboard");
        });
        document.getElementById("btn-my-track-search").addEventListener("click", () => {
            searchMyTracking();
        });
        document.getElementById("my-track-id").addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                searchMyTracking();
            }
        });

        document.getElementById("btn-my-sub-detail-close").addEventListener("click", () => {
            document.getElementById("my-sub-detail-modal").classList.add("hidden");
        });
        document.getElementById("btn-my-progress-close").addEventListener("click", () => {
            document.getElementById("my-progress-modal").classList.add("hidden");
        });

        const myFormFilterDepartmentEl = document.getElementById("my-form-filter-department");
        if (myFormFilterDepartmentEl) {
            myFormFilterDepartmentEl.addEventListener("change", () => {
                myFormDepartmentFilter = myFormFilterDepartmentEl.value;
                renderMyFormList();
            });
        }

        const myFormSearchNameEl = document.getElementById("my-form-search-name");
        if (myFormSearchNameEl) {
            myFormSearchNameEl.addEventListener("input", () => {
                myFormSearchQuery = myFormSearchNameEl.value.trim();
                renderMyFormList();
            });
        }

        // Add "Submit New Form" button to dashboard
        function renderMySubmissionsDashboard() {
            const dash = document.getElementById("my-subs-dashboard");
            const title = dash.querySelector("h2");
            if (title && !document.getElementById("btn-my-new-form")) {
                title.innerHTML = 'My Submissions <button id="btn-my-new-form" class="btn btn-primary" style="float:right;font-size:13px;padding:6px 14px;">+ Submit New Form</button>';
                document.getElementById("btn-my-new-form").addEventListener("click", () => showMyView("myFormList"));
            }
        }

        // Override showView for non_admin only - delegate to original for all other views
        const originalShowView = showView;
        showView = function(view) {
            if (currentUser && currentUser.role === "non_admin") {
                Object.values(views).forEach(el => {
                    if (el) el.classList.add("hidden");
                });
                if (view === "mySubmissions") {
                    const mainEl = document.getElementById("view-my-submissions");
                    mainEl.classList.remove("hidden");
                    mainEl.style.display = "block";
                    syncRouteWithView("mySubmissions");
                    showMyView("myDashboard");
                    renderMySubmissionsDashboard();
                } else if (view === "fillList") {
                    // non_admin "Submit a Form" goes to their personal form list
                    const mainEl = document.getElementById("view-my-submissions");
                    mainEl.classList.remove("hidden");
                    mainEl.style.display = "block";
                    syncRouteWithView("fillList");
                    showMyView("myFormList");
                } else if (view === "track") {
                    const mainEl = document.getElementById("view-my-submissions");
                    mainEl.classList.remove("hidden");
                    mainEl.style.display = "block";
                    syncRouteWithView("track");
                    showMyView("myTrack");
                } else if (view === "landing" || view === "login") {
                    // non_admin can't go to landing/login, go back to dashboard
                    const mainEl = document.getElementById("view-my-submissions");
                    mainEl.classList.remove("hidden");
                    mainEl.style.display = "block";
                    syncRouteWithView("mySubmissions");
                    showMyView("myDashboard");
                } else {
                    // track and other views - delegate to original
                    originalShowView(view);
                }
                return;
            }
            // Not non_admin, use original behavior
            originalShowView(view);
        };
