        function getAdminData() {
            const isSuperadmin = currentUser && currentUser.role === "superadmin";
            const allowedTemplates = isSuperadmin
                ? templates
                : templates.filter(t => t.department === currentUser.department || !t.department);
            const allowedSubs = isSuperadmin
                ? submissions
                : submissions.filter(s => s.department === currentUser.department || !s.department);
            return { allowedTemplates, allowedSubs };
        }

        function openTemplateEditor(templateId = null) {
            if (templateId) {
                const found = templates.find(t => t.id === templateId);
                if (!found) return;
                editorDraft = JSON.parse(JSON.stringify(found));
            } else {
                editorDraft = {
                    id: genTplId(),
                    name: "",
                    description: "",
                    department: currentUser.role === "superadmin" ? "" : currentUser.department,
                    published: false,
                    approvalFlow: [],
                    fields: [],
                };
            }
            editorTab = "fields";
            adminPage = "formEditor";
            renderAdmin();
        }

        async function saveTemplateEditor() {
            if (!editorDraft.name.trim()) {
                showToast("Form name is required", "error");
                return;
            }
            if (!editorDraft.department) {
                showToast("Department is required", "error");
                return;
            }

            try {
                await apiRequest("/templates", {
                    method: "POST",
                    body: editorDraft,
                });
                await loadAppData();
            } catch (e) {
                showToast(e.message || "Failed to save form", "error");
                return;
            }

            adminPage = "forms";
            editorDraft = null;
            showToast("Form saved");
            renderAdmin();
        }

        function renderAdmin() {
            if (!currentUser) return;
            const isSuperadmin = currentUser.role === "superadmin";
            const isAdminDepartment = currentUser.role === "admin_department";
            const adminDepartmentPages = new Set(["submit-form", "my-submissions", "forms", "submissions", "formEditor"]);
            if (isAdminDepartment && !adminDepartmentPages.has(adminPage)) {
                adminPage = "submit-form";
            }
            const roleLabel = currentUser.role.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());
            document.getElementById("admin-user-name").textContent = currentUser.name;
            document.getElementById("admin-user-role").textContent = roleLabel;

            const navBtns = [...document.querySelectorAll("[data-admin-page]")];
            navBtns.forEach(btn => {
                const page = btn.dataset.adminPage;
                let isRestricted = false;
                if (isAdminDepartment) {
                    isRestricted = !adminDepartmentPages.has(page);
                } else {
                    isRestricted = ((page === "departments" || page === "users") && !isSuperadmin) || page === "submit-form";
                }
                btn.classList.toggle("active", page === adminPage);
                btn.classList.toggle("hidden", isRestricted);
            });

            const { allowedTemplates, allowedSubs } = getAdminData();
            const content = document.getElementById("admin-content");

            if (adminPage === "submit-form") {
                if (currentUser.role === "admin_department") {
                    const published = templates.filter(t => t.published);
                    content.innerHTML = `
                        <div id="ad-submit-form-list" class="card">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                <h2 style="margin:0;color:var(--primary)">Submit Form</h2>
                            </div>
                            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                                <div style="min-width:220px;flex:1;">
                                    <label class="label">Departement Penerbit Form</label>
                                    <select id="ad-form-filter-department" class="input">
                                        <option value="">All Department</option>
                                    </select>
                                </div>
                                <div style="min-width:260px;flex:2;">
                                    <label class="label">Search Nama Form</label>
                                    <input id="ad-form-search-name" class="input" type="text" placeholder="Cari nama form...">
                                </div>
                            </div>
                            <div id="ad-form-list-container"></div>
                        </div>

                        <div id="ad-submit-form-fill" class="card hidden" style="margin-top:16px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                                <h3 id="ad-selected-form-title" style="margin:0;color:var(--primary)">Fill Form</h3>
                                <button id="btn-ad-form-back" class="btn btn-ghost"><- Back</button>
                            </div>
                            <div id="ad-prereq-check-section" class="hidden" style="margin-bottom:14px;padding:12px;background:#F8FAFC;border:1px solid var(--gray-light);border-radius:10px;">
                                <div style="font-weight:600;color:var(--primary);margin-bottom:8px;">Prerequisite Verification</div>
                                <p id="ad-prereq-check-help" class="muted" style="margin:0 0 10px;"></p>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                    <input id="ad-prereq-submission-id" class="input" placeholder="Input prerequisite submission ID" style="flex:1;min-width:220px;">
                                    <button id="btn-ad-prereq-check" type="button" class="btn btn-outline">Check ID</button>
                                </div>
                                <div id="ad-prereq-check-result" style="margin-top:8px;font-size:13px;"></div>
                            </div>
                            <div id="ad-internal-approver-section" class="hidden" style="margin-bottom:14px;padding:12px;background:#F8FAFC;border:1px solid var(--gray-light);border-radius:10px;">
                                <div style="font-weight:600;color:var(--primary);margin-bottom:8px;">Internal Approval Assignment</div>
                                <div id="ad-internal-approver-list"></div>
                            </div>
                            <div id="ad-dynamic-fields"></div>
                            <div style="margin-top:20px;display:flex;gap:10px;">
                                <button id="btn-ad-submit-form" class="btn btn-primary">Submit Form</button>
                            </div>
                        </div>
                    `;

                    let adSelectedTemplate = null;
                    let adFormData = {};
                    let adVerifiedPrerequisiteSubmissionId = null;
                    let adInternalApproverSelections = {};
                    let adDepartmentFilter = "";
                    let adSearchQuery = "";

                    const listWrap = document.getElementById("ad-submit-form-list");
                    const fillWrap = document.getElementById("ad-submit-form-fill");
                    const listContainer = document.getElementById("ad-form-list-container");
                    const filterDepartmentEl = document.getElementById("ad-form-filter-department");
                    const searchNameEl = document.getElementById("ad-form-search-name");

                    const showList = () => {
                        listWrap.classList.remove("hidden");
                        fillWrap.classList.add("hidden");
                    };

                    const showFill = () => {
                        listWrap.classList.add("hidden");
                        fillWrap.classList.remove("hidden");
                    };

                    const getCalcValue = (field) => {
                        if (!field.formula || !adSelectedTemplate) return 0;
                        let expr = field.formula;
                        adSelectedTemplate.fields.forEach(f => {
                            const k = `{${f.label}}`;
                            const v = parseFloat(adFormData[f.id] || 0) || 0;
                            expr = expr.split(k).join(v);
                        });
                        const n = safeCalc(expr);
                        return Number.isFinite(n) ? n : 0;
                    };

                    const renderDynamicFields = () => {
                        const wrap = document.getElementById("ad-dynamic-fields");
                        wrap.innerHTML = "";
                        if (!adSelectedTemplate) return;

                        const prereqSection = document.getElementById("ad-prereq-check-section");
                        const prereqHelp = document.getElementById("ad-prereq-check-help");
                        const prereqInput = document.getElementById("ad-prereq-submission-id");
                        const prereqResult = document.getElementById("ad-prereq-check-result");
                        const internalApproverSection = document.getElementById("ad-internal-approver-section");
                        const internalApproverList = document.getElementById("ad-internal-approver-list");
                        const submitBtn = document.getElementById("btn-ad-submit-form");

                        if (adSelectedTemplate.prerequisiteFormId) {
                            const prereqTemplate = templates.find(t => t.id === adSelectedTemplate.prerequisiteFormId);
                            prereqSection.classList.remove("hidden");
                            prereqHelp.textContent = `This form requires approved submission ID from: ${prereqTemplate?.name || adSelectedTemplate.prerequisiteFormId}.`;
                            if (adVerifiedPrerequisiteSubmissionId) {
                                prereqInput.value = adVerifiedPrerequisiteSubmissionId;
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
                            adVerifiedPrerequisiteSubmissionId = null;
                            submitBtn.disabled = false;
                            submitBtn.classList.remove("hidden");
                            wrap.classList.remove("hidden");
                        }

                        const internalApprovalFlow = (adSelectedTemplate?.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external");
                        const approverCandidates = (users || []).filter(u => (u.role || "").toLowerCase() !== "non_admin");
                        if (internalApprovalFlow.length > 0) {
                            internalApproverSection.classList.remove("hidden");
                            if (approverCandidates.length === 0) {
                                internalApproverList.innerHTML = `<p class="muted">No approver user available.</p>`;
                            } else {
                                internalApproverList.innerHTML = internalApprovalFlow.map((a, i) => {
                                    const levelLabel = a.role || `Superior Level ${i + 1}`;
                                    const selectedUsername = adInternalApproverSelections[a.id] || "";
                                    return `
                                        <div style="margin-bottom:10px;">
                                            <label class="label">${escapeHtml(levelLabel)} *</label>
                                            <select class="input ad-internal-approver" data-step-id="${a.id}">
                                                <option value="">Select approver</option>
                                                ${approverCandidates.map(u => `<option value="${escapeHtml(u.username)}" ${selectedUsername === u.username ? "selected" : ""}>${escapeHtml(u.name)} (${escapeHtml(u.username)})</option>`).join("")}
                                            </select>
                                        </div>
                                    `;
                                }).join("");
                                internalApproverList.querySelectorAll(".ad-internal-approver").forEach(el => {
                                    el.addEventListener("change", () => {
                                        adInternalApproverSelections[el.dataset.stepId] = el.value || "";
                                    });
                                });
                            }
                        } else {
                            internalApproverSection.classList.add("hidden");
                            internalApproverList.innerHTML = "";
                        }

                        adSelectedTemplate.fields.forEach((field) => {
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
                                html += `<div style="padding:12px;border-radius:8px;background:#EDF3FF;color:var(--primary);font-weight:700" data-calc="${field.id}">${getCalcValue(field)}</div>`;
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
                                    adFormData[fid] = checks.map(c => c.value);
                                } else if (ftype === "table-cell") {
                                    const field = adSelectedTemplate.fields.find(f => f.id === fid);
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
                                    adFormData[fid] = Object.keys(rowMap).sort((a, b) => Number(a) - Number(b)).map(k => rowMap[k]);
                                } else if (ftype === "radio") {
                                    adFormData[fid] = el.value;
                                } else if (ftype === "file") {
                                    adFormData[fid] = el.files[0] ? el.files[0].name : "";
                                } else {
                                    adFormData[fid] = el.value;
                                }
                                adSelectedTemplate.fields.filter(f => f.type === "calculation").forEach(f => {
                                    const calcEl = wrap.querySelector(`[data-calc="${f.id}"]`);
                                    if (calcEl) calcEl.textContent = getCalcValue(f);
                                });
                            };
                            el.addEventListener("change", updateValue);
                            el.addEventListener("input", updateValue);
                        });
                    };

                    const renderFormList = () => {
                        const deptIds = [...new Set(published.map(t => t.department).filter(Boolean))];
                        const deptOptions = deptIds.map(deptId => {
                            const dept = depts.find(d => d.id === deptId);
                            if (!dept) return "";
                            return `<option value="${escapeHtml(dept.id)}">${escapeHtml(dept.name)} (${escapeHtml(dept.code || "-")})</option>`;
                        }).join("");
                        filterDepartmentEl.innerHTML = `<option value="">All Department</option>${deptOptions}`;
                        filterDepartmentEl.value = adDepartmentFilter;

                        const filtered = published.filter(form => {
                            const byDepartment = !adDepartmentFilter || form.department === adDepartmentFilter;
                            const byName = !adSearchQuery || (form.name || "").toLowerCase().includes(adSearchQuery.toLowerCase());
                            return byDepartment && byName;
                        });

                        if (filtered.length === 0) {
                            listContainer.innerHTML = `<p class="muted" style="text-align:center;padding:26px;">No form matched your filter.</p>`;
                            return;
                        }

                        listContainer.innerHTML = `
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
                                                    <td style="padding:10px 12px;"><button class="btn btn-outline btn-ad-fill-form" data-id="${escapeHtml(form.id)}">Fill Form</button></td>
                                                </tr>
                                            `;
                                        }).join("")}
                                    </tbody>
                                </table>
                            </div>
                        `;

                        listContainer.querySelectorAll(".btn-ad-fill-form").forEach(btn => {
                            btn.addEventListener("click", () => {
                                const tpl = templates.find(t => t.id === btn.dataset.id);
                                if (!tpl) return;
                                adSelectedTemplate = tpl;
                                adFormData = {};
                                adVerifiedPrerequisiteSubmissionId = null;
                                adInternalApproverSelections = {};
                                document.getElementById("ad-selected-form-title").textContent = tpl.name;
                                renderDynamicFields();
                                showFill();
                            });
                        });
                    };

                    const submitAdminForm = async () => {
                        if (!adSelectedTemplate) return;
                        let prerequisiteSubmissionId = null;
                        if (adSelectedTemplate.prerequisiteFormId) {
                            const prereqInput = document.getElementById("ad-prereq-submission-id");
                            const prereqCheck = await verifyPrerequisiteSubmissionById(adSelectedTemplate, prereqInput ? prereqInput.value : "");
                            if (!prereqCheck.ok) {
                                showToast(prereqCheck.message, "error");
                                return;
                            }
                            prerequisiteSubmissionId = prereqCheck.submissionId;
                        }
                        for (const f of adSelectedTemplate.fields) {
                            if (!f.required) continue;
                            const val = adFormData[f.id];
                            const empty = val === undefined || val === null || val === "" || (Array.isArray(val) && val.length === 0);
                            if (empty) {
                                showToast(`Field required: ${f.label}`, "error");
                                return;
                            }
                        }

                        const internalApprovalFlow = (adSelectedTemplate.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external");
                        for (const step of internalApprovalFlow) {
                            const approverUsername = (adInternalApproverSelections[step.id] || "").trim();
                            if (!approverUsername) {
                                showToast(`Please select approver for ${step.role || "internal step"}`, "error");
                                return;
                            }
                        }

                        const id = genSubId();
                        const payload = {
                            id,
                            templateId: adSelectedTemplate.id,
                            templateName: adSelectedTemplate.name,
                            department: adSelectedTemplate.department || null,
                            employeeName: currentUser.name,
                            employeeEmail: currentUser.email,
                            data: { ...adFormData },
                            prerequisiteSubmissionId,
                            approvalSteps: (adSelectedTemplate.approvalFlow || []).map((a, i) => ({
                                ...(function() {
                                    const approvalType = a.approvalType === "external" ? "external" : "internal";
                                    const approverUsername = approvalType === "internal"
                                        ? (adInternalApproverSelections[a.id] || "").trim()
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
                            status: (adSelectedTemplate.approvalFlow || []).length > 0 ? "in_review" : "approved",
                            submittedAt: new Date().toISOString(),
                        };

                        try {
                            await apiRequest("/submissions", { method: "POST", body: payload });
                            await loadAppData();
                        } catch (e) {
                            showToast(e.message || "Failed to submit form", "error");
                            return;
                        }

                        adSelectedTemplate = null;
                        adFormData = {};
                        adVerifiedPrerequisiteSubmissionId = null;
                        adInternalApproverSelections = {};
                        showToast(`Form submitted. Tracking ID: ${id}`);
                        showList();
                        renderFormList();
                    };

                    filterDepartmentEl.addEventListener("change", () => {
                        adDepartmentFilter = filterDepartmentEl.value;
                        renderFormList();
                    });
                    searchNameEl.addEventListener("input", () => {
                        adSearchQuery = searchNameEl.value.trim();
                        renderFormList();
                    });
                    document.getElementById("btn-ad-form-back").addEventListener("click", () => {
                        adSelectedTemplate = null;
                        adFormData = {};
                        adInternalApproverSelections = {};
                        showList();
                        renderFormList();
                    });
                    document.getElementById("btn-ad-prereq-check").addEventListener("click", async () => {
                        if (!adSelectedTemplate || !adSelectedTemplate.prerequisiteFormId) return;
                        const prereqInput = document.getElementById("ad-prereq-submission-id");
                        const prereqResult = document.getElementById("ad-prereq-check-result");
                        const submitBtn = document.getElementById("btn-ad-submit-form");
                        const check = await verifyPrerequisiteSubmissionById(adSelectedTemplate, prereqInput.value);
                        if (!check.ok) {
                            adVerifiedPrerequisiteSubmissionId = null;
                            submitBtn.disabled = true;
                            submitBtn.classList.add("hidden");
                            prereqResult.innerHTML = `<span style="color:var(--danger);">${escapeHtml(check.message)}</span>`;
                            showToast(check.message, "error");
                            return;
                        }
                        adVerifiedPrerequisiteSubmissionId = check.submissionId;
                        prereqInput.value = check.submissionId;
                        submitBtn.disabled = false;
                        submitBtn.classList.remove("hidden");
                        prereqResult.innerHTML = `<span style="color:var(--success);">Verified. Prerequisite submission is approved.</span>`;
                        showToast("Prerequisite verified");
                        renderDynamicFields();
                    });
                    document.getElementById("ad-prereq-submission-id").addEventListener("input", () => {
                        const submitBtn = document.getElementById("btn-ad-submit-form");
                        const prereqResult = document.getElementById("ad-prereq-check-result");
                        if (!adSelectedTemplate || !adSelectedTemplate.prerequisiteFormId) return;
                        const currentValue = document.getElementById("ad-prereq-submission-id").value.trim().toUpperCase();
                        if (currentValue === adVerifiedPrerequisiteSubmissionId) return;
                        adVerifiedPrerequisiteSubmissionId = null;
                        submitBtn.disabled = true;
                        submitBtn.classList.add("hidden");
                        prereqResult.innerHTML = `<span class="muted">ID changed. Please check again.</span>`;
                        renderDynamicFields();
                    });
                    document.getElementById("btn-ad-submit-form").addEventListener("click", submitAdminForm);

                    showList();
                    renderFormList();
                    return;
                }
                content.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:0 0 16px;">
                        <h2 style="margin:0;color:var(--primary)">Submit Form</h2>
                    </div>
                    <div class="card">
                        <p class="muted" style="margin:0 0 14px;">Use FORM List to manage/publish forms, then check submitted data in My Submission.</p>
                        <button id="btn-admin-open-submit-form" class="btn btn-primary">Go to FORM List</button>
                    </div>
                `;

                const btnOpenSubmitForm = document.getElementById("btn-admin-open-submit-form");
                if (btnOpenSubmitForm) {
                    btnOpenSubmitForm.addEventListener("click", () => {
                        adminPage = "forms";
                        renderAdmin();
                    });
                }
                return;
            }
