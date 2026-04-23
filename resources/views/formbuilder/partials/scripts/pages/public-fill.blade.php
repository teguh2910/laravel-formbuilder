        let verifiedPrerequisiteSubmissionId = null;
        let internalApproverSelections = {};

        function renderTemplateList() {
            const listEl = document.getElementById("template-list-grouped");
            const published = templates.filter(t => t.published);
            const grouped = depts.map(d => ({
                ...d,
                forms: published.filter(f => f.department === d.id)
            })).filter(g => g.forms.length > 0);

            if (grouped.length === 0) {
                listEl.innerHTML = '<div class="card"><p class="muted" style="text-align:center;padding:40px;">No published forms yet.</p></div>';
                return;
            }

            listEl.innerHTML = grouped.map(g => `
                <div class="card" style="margin-bottom:16px;padding:0;overflow:hidden;">
                    <div style="background:var(--primary);color:#fff;padding:14px 20px;display:flex;align-items:center;gap:12px;font-weight:700;font-size:15px;">
                        <span>${g.name} (${g.code})</span>
                        <span class="badge" style="margin-left:auto;background:rgba(255,255,255,.2);color:#fff;">${g.forms.length} form${g.forms.length > 1 ? "s" : ""}</span>
                    </div>
                    ${g.forms.map((f, fi) => `
                        <div class="form-row" data-id="${f.id}" style="padding:16px 20px;cursor:pointer;border-bottom:${fi < g.forms.length - 1 ? "1px solid var(--gray-light)" : "none"};display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-weight:600;color:var(--primary);margin-bottom:4px">${f.name}</div>
                                <div class="muted" style="font-size:13px;margin-bottom:6px">${f.description || "No description"}</div>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <span class="badge">${f.fields.length} fields</span>
                                    ${f.fields.some(x => x.type === "table") ? '<span class="badge" style="background:#DBEAFE;color:var(--accent)">Table</span>' : ""}
                                    ${f.fields.some(x => x.type === "calculation") ? '<span class="badge" style="background:#FEF3C7;color:var(--warn)">Calc</span>' : ""}
                                    ${f.prerequisiteFormId ? '<span class="badge" style="background:#EDE9FE;color:#7C3AED">Prereq</span>' : ""}
                                </div>
                            </div>
                            <span style="color:var(--primary);font-size:20px">→</span>
                        </div>
                    `).join("")}
                </div>
            `).join("");

            listEl.querySelectorAll("[data-id]").forEach(el => {
                el.addEventListener("click", () => {
                    selectedTemplate = templates.find(t => t.id === el.dataset.id);
                    formData = {};
                    verifiedPrerequisiteSubmissionId = null;
                    internalApproverSelections = {};
                    renderDynamicFields();
                    document.getElementById("selected-form-title").textContent = selectedTemplate.name;
                    showView("fillForm");
                });
            });
        }

        function getCalcValue(field) {
            if (!field.formula) return 0;
            let expr = field.formula;
            selectedTemplate.fields.forEach(f => {
                const k = `{${f.label}}`;
                const v = parseFloat(formData[f.id] || 0) || 0;
                expr = expr.split(k).join(v);
            });
            const n = safeCalc(expr);
            return Number.isFinite(n) ? n : 0;
        }

        function renderDynamicFields() {
            const wrap = document.getElementById("dynamic-fields");
            wrap.innerHTML = "";
            const prereqSection = document.getElementById("prereq-check-section");
            const prereqHelp = document.getElementById("prereq-check-help");
            const prereqInput = document.getElementById("prereq-submission-id");
            const prereqResult = document.getElementById("prereq-check-result");
            const internalApproverSection = document.getElementById("internal-approver-section");
            const internalApproverList = document.getElementById("internal-approver-list");
            const submitBtn = document.getElementById("btn-submit-form");

            if (selectedTemplate?.prerequisiteFormId) {
                const prereqTemplate = templates.find(t => t.id === selectedTemplate.prerequisiteFormId);
                prereqSection.classList.remove("hidden");
                prereqHelp.textContent = `This form requires approved submission ID from: ${prereqTemplate?.name || selectedTemplate.prerequisiteFormId}.`;
                if (verifiedPrerequisiteSubmissionId) {
                    prereqInput.value = verifiedPrerequisiteSubmissionId;
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
                verifiedPrerequisiteSubmissionId = null;
                submitBtn.disabled = false;
                submitBtn.classList.remove("hidden");
                wrap.classList.remove("hidden");
            }

            const internalApprovalFlow = (selectedTemplate?.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external");
            const approverCandidates = (users || []).filter(u => (u.role || "").toLowerCase() !== "non_admin");
            if (internalApprovalFlow.length > 0) {
                internalApproverSection.classList.remove("hidden");
                if (approverCandidates.length === 0) {
                    internalApproverList.innerHTML = `<p class="muted">No approver user available.</p>`;
                } else {
                    internalApproverList.innerHTML = internalApprovalFlow.map((a, i) => {
                        const levelLabel = a.role || `Superior Level ${i + 1}`;
                        const selectedUsername = internalApproverSelections[a.id] || "";
                        return `
                            <div style="margin-bottom:10px;">
                                <label class="label">${escapeHtml(levelLabel)} *</label>
                                <select class="input public-internal-approver" data-step-id="${a.id}">
                                    <option value="">Select approver</option>
                                    ${approverCandidates.map(u => `<option value="${escapeHtml(u.username)}" ${selectedUsername === u.username ? "selected" : ""}>${escapeHtml(u.name)} (${escapeHtml(u.username)})</option>`).join("")}
                                </select>
                            </div>
                        `;
                    }).join("");
                    internalApproverList.querySelectorAll(".public-internal-approver").forEach(el => {
                        el.addEventListener("change", () => {
                            internalApproverSelections[el.dataset.stepId] = el.value || "";
                        });
                    });
                }
            } else {
                internalApproverSection.classList.add("hidden");
                internalApproverList.innerHTML = "";
            }

            selectedTemplate.fields.forEach((field) => {
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
                        formData[fid] = checks.map(c => c.value);
                    } else if (ftype === "table-cell") {
                        const field = selectedTemplate.fields.find(f => f.id === fid);
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
                        formData[fid] = Object.keys(rowMap).sort((a, b) => Number(a) - Number(b)).map(k => rowMap[k]);
                    } else if (ftype === "radio") {
                        formData[fid] = el.value;
                    } else if (ftype === "file") {
                        formData[fid] = el.files[0] ? el.files[0].name : "";
                    } else {
                        formData[fid] = el.value;
                    }

                    selectedTemplate.fields.filter(f => f.type === "calculation").forEach(f => {
                        const calcEl = wrap.querySelector(`[data-calc="${f.id}"]`);
                        if (calcEl) calcEl.textContent = getCalcValue(f);
                    });
                };
                el.addEventListener("change", updateValue);
                el.addEventListener("input", updateValue);
            });
        }

        async function submitForm() {
            if (!selectedTemplate) return;
            const name = document.getElementById("emp-name").value.trim();
            const email = document.getElementById("emp-email").value.trim();

            if (!name || !email) {
                showToast("Please fill name and email", "error");
                return;
            }

            let prerequisiteSubmissionId = null;
            if (selectedTemplate.prerequisiteFormId) {
                const prereqInput = document.getElementById("prereq-submission-id");
                const prereqId = prereqInput ? prereqInput.value : "";
                const prereqCheck = await verifyPrerequisiteSubmissionById(selectedTemplate, prereqId);
                if (!prereqCheck.ok) {
                    showToast(prereqCheck.message, "error");
                    return;
                }
                prerequisiteSubmissionId = prereqCheck.submissionId;
            }

            for (const f of selectedTemplate.fields) {
                if (!f.required) continue;
                const val = formData[f.id];
                const empty = val === undefined || val === null || val === "" || (Array.isArray(val) && val.length === 0);
                if (empty) {
                    showToast(`Field required: ${f.label}`, "error");
                    return;
                }
            }

            const internalApprovalFlow = (selectedTemplate.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external");
            for (const step of internalApprovalFlow) {
                const approverUsername = (internalApproverSelections[step.id] || "").trim();
                if (!approverUsername) {
                    showToast(`Please select approver for ${step.role || "internal step"}`, "error");
                    return;
                }
            }

            const id = genSubId();
            const payload = {
                id,
                templateId: selectedTemplate.id,
                templateName: selectedTemplate.name,
                department: selectedTemplate.department || null,
                employeeName: name,
                employeeEmail: email,
                data: { ...formData },
                prerequisiteSubmissionId,
                approvalSteps: (selectedTemplate.approvalFlow || []).map((a, i) => ({
                    ...(function() {
                        const approvalType = a.approvalType === "external" ? "external" : "internal";
                        const approverUsername = approvalType === "internal"
                            ? (internalApproverSelections[a.id] || "").trim()
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
                status: (selectedTemplate.approvalFlow || []).length > 0 ? "in_review" : "approved",
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

            document.getElementById("emp-name").value = "";
            document.getElementById("emp-email").value = "";
            selectedTemplate = null;
            formData = {};
            verifiedPrerequisiteSubmissionId = null;
            internalApproverSelections = {};
            renderTemplateList();
            showView("fillList");
            showToast(`Form submitted. Tracking ID: ${id}`);
        }

        document.getElementById("btn-prereq-check").addEventListener("click", async () => {
            if (!selectedTemplate || !selectedTemplate.prerequisiteFormId) return;
            const prereqInput = document.getElementById("prereq-submission-id");
            const prereqResult = document.getElementById("prereq-check-result");
            const submitBtn = document.getElementById("btn-submit-form");

            const check = await verifyPrerequisiteSubmissionById(selectedTemplate, prereqInput.value);
            if (!check.ok) {
                verifiedPrerequisiteSubmissionId = null;
                submitBtn.disabled = true;
                submitBtn.classList.add("hidden");
                prereqResult.innerHTML = `<span style="color:var(--danger);">${escapeHtml(check.message)}</span>`;
                showToast(check.message, "error");
                return;
            }

            verifiedPrerequisiteSubmissionId = check.submissionId;
            prereqInput.value = check.submissionId;
            submitBtn.disabled = false;
            submitBtn.classList.remove("hidden");
            prereqResult.innerHTML = `<span style="color:var(--success);">Verified. Prerequisite submission is approved.</span>`;
            showToast("Prerequisite verified");
            renderDynamicFields();
        });

        document.getElementById("prereq-submission-id").addEventListener("input", () => {
            const submitBtn = document.getElementById("btn-submit-form");
            const prereqResult = document.getElementById("prereq-check-result");
            if (!selectedTemplate || !selectedTemplate.prerequisiteFormId) return;
            const currentValue = document.getElementById("prereq-submission-id").value.trim().toUpperCase();
            if (currentValue === verifiedPrerequisiteSubmissionId) return;
            verifiedPrerequisiteSubmissionId = null;
            submitBtn.disabled = true;
            submitBtn.classList.add("hidden");
            prereqResult.innerHTML = `<span class="muted">ID changed. Please check again.</span>`;
            renderDynamicFields();
        });
