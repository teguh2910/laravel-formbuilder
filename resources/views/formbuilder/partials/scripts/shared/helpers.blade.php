        const viewRouteMap = {
            landing: "/",
            login: "/formbuilder/login",
            fillList: "/formbuilder/forms",
            fillForm: "/formbuilder/forms/fill",
            track: "/formbuilder/track",
            admin: "/formbuilder/admin",
            mySubmissions: "/formbuilder/my-submissions",
        };

        function normalizePath(path) {
            const p = String(path || "/").replace(/\/+$/, "");
            return p === "" ? "/" : p;
        }

        function resolveViewFromPath(path = window.location.pathname) {
            const normalized = normalizePath(path);
            if (normalized === "/" || normalized === "/formbuilder") return "landing";
            if (normalized === "/formbuilder/login") return "login";
            if (normalized === "/formbuilder/forms") return "fillList";
            if (normalized === "/formbuilder/forms/fill") return "fillForm";
            if (normalized === "/formbuilder/track") return "track";
            if (normalized === "/formbuilder/admin") return "admin";
            if (normalized === "/formbuilder/my-submissions") return "mySubmissions";
            return "landing";
        }

        function syncRouteWithView(viewName, options = {}) {
            const targetPath = viewRouteMap[viewName];
            if (!targetPath) return;
            const currentPath = normalizePath(window.location.pathname);
            const nextPath = normalizePath(targetPath);
            if (currentPath === nextPath) return;
            const method = options.replace ? "replaceState" : "pushState";
            window.history[method]({}, "", targetPath);
        }

        function showView(name, options = {}) {
            Object.values(views).forEach(v => {
                if (v) v.classList.add("hidden");
            });
            if (!views[name]) {
                throw new Error(`Unknown view: ${name}`);
            }
            views[name].classList.remove("hidden");
            if (options.syncRoute !== false) {
                syncRouteWithView(name, { replace: !!options.replaceRoute });
            }
        }

        function showToast(message, type = "success") {
            toastEl.textContent = message;
            toastEl.className = `toast ${type}`;
            setTimeout(() => toastEl.className = "toast hidden", 2500);
        }

        async function apiRequest(path, options = {}) {
            const method = options.method || "GET";
            const headers = {
                "Accept": "application/json",
                ...options.headers,
            };

            const fetchOptions = {
                method,
                headers,
                credentials: "same-origin",
            };

            if (options.body !== undefined) {
                headers["Content-Type"] = "application/json";
                headers["X-CSRF-TOKEN"] = csrfToken;
                fetchOptions.body = JSON.stringify(options.body);
            } else if (method !== "GET") {
                headers["X-CSRF-TOKEN"] = csrfToken;
            }

            const res = await fetch(`${apiBase}${path}`, fetchOptions);
            const raw = await res.text();
            const data = raw ? JSON.parse(raw) : {};

            if (!res.ok) {
                const msg = data?.message || `Request failed (${res.status})`;
                throw new Error(msg);
            }

            return data;
        }

        async function loadAppData() {
            const data = await apiRequest("/bootstrap");
            users = data.users || [];
            depts = data.depts || [];
            templates = data.templates || [];
            submissions = data.submissions || [];
        }

        function persistCurrentUserSession(user) {
            try {
                if (!user || !user.username) return;
                localStorage.setItem(authStorageKey, JSON.stringify({
                    username: user.username,
                }));
            } catch (_) {}
        }

        function clearCurrentUserSession() {
            try {
                localStorage.removeItem(authStorageKey);
            } catch (_) {}
        }

        function restoreCurrentUserSession() {
            try {
                const raw = localStorage.getItem(authStorageKey);
                if (!raw) return null;
                const parsed = JSON.parse(raw);
                if (!parsed || !parsed.username) return null;
                const found = users.find(u => u.username === parsed.username);
                if (!found) {
                    clearCurrentUserSession();
                    return null;
                }
                return found;
            } catch (_) {
                clearCurrentUserSession();
                return null;
            }
        }

        function safeCalc(expr) {
            try {
                const s = String(expr).replace(/[^0-9+\-*/().,%\s]/g, "");
                return Function(`"use strict"; return (${s})`)();
            } catch {
                return 0;
            }
        }

        function badgeClass(status) {
            if (status === "approved") return "status-approved";
            if (status === "rejected") return "status-rejected";
            if (status === "in_review") return "status-in-review";
            return "status-pending";
        }

        function statusLabel(status) {
            if (status === "approved") return "Approved";
            if (status === "rejected") return "Rejected";
            if (status === "in_review") return "In Review";
            return "Pending";
        }

        function escapeHtml(value) {
            return String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatDateTime(value) {
            if (!value) return "-";
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return "-";
            return date.toLocaleString();
        }

        function renderApprovalFlowHtml(submission) {
            const steps = Array.isArray(submission?.approvalSteps) ? submission.approvalSteps : [];
            if (steps.length === 0) {
                return `<div style="padding:12px;background:#ECFDF5;color:var(--success);border-radius:8px;">No approval flow (auto-approved).</div>`;
            }

            return `
                <div style="display:flex;flex-direction:column;gap:10px;">
                    ${steps.map((step, index) => `
                        <div style="padding:12px;background:#fff;border:1px solid var(--gray-light);border-radius:10px;">
                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
                                <div style="font-weight:600;color:var(--primary);">Step ${index + 1}: ${escapeHtml(step.role || "-")}</div>
                                <span class="badge ${badgeClass(step.status)}">${statusLabel(step.status)}</span>
                            </div>
                            ${step.approverName ? `
                                <div class="muted" style="margin-top:8px;font-size:12px;">
                                    Approver: ${escapeHtml(step.approverName)}
                                </div>
                            ` : ""}
                            ${(step.reviewedBy || step.reviewedAt) ? `
                                <div class="muted" style="margin-top:8px;font-size:12px;">
                                    Reviewed by ${escapeHtml(step.reviewedBy || "-")} at ${formatDateTime(step.reviewedAt)}
                                </div>
                            ` : ""}
                            ${step.comments ? `
                                <div style="margin-top:8px;padding:8px 10px;background:var(--light);border-radius:8px;font-size:13px;">
                                    ${escapeHtml(step.comments)}
                                </div>
                            ` : ""}
                        </div>
                    `).join("")}
                </div>
            `;
        }

        function renderTrackingResultHtml(submission, options = {}) {
            const showEmployee = options.showEmployee !== false;

            return `
                <div class="card" style="padding:16px;background:var(--light);">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:12px;color:var(--gray)">Submission ID</div>
                            <div style="font-weight:700;color:var(--primary)">${escapeHtml(submission.id)}</div>
                        </div>
                        <span class="badge ${badgeClass(submission.status)}">${statusLabel(submission.status)}</span>
                    </div>
                    <hr style="border:none;border-top:1px solid var(--gray-light);margin:12px 0;">
                    <div class="muted">Form: ${escapeHtml(submission.templateName || "-")}</div>
                    ${showEmployee ? `<div class="muted">Employee: ${escapeHtml(submission.employeeName || "-")} - ${escapeHtml(submission.employeeEmail || "-")}</div>` : ""}
                    <div class="muted">Submitted: ${formatDateTime(submission.submittedAt)}</div>
                    <div style="margin-top:14px;">
                        <div style="font-weight:700;color:var(--primary);margin-bottom:8px;">Approval Flow</div>
                        ${renderApprovalFlowHtml(submission)}
                    </div>
                </div>
            `;
        }

        function getTableColumnsForPreview(field) {
            const cols = Array.isArray(field?.tableColumns) ? field.tableColumns : [];
            if (cols.length > 0) return cols;
            return [
                { name: "Item", type: "text" },
                { name: "Qty", type: "number" },
                { name: "Price", type: "number" },
                { name: "Total", type: "calc" },
            ];
        }

        function getTableRowCount(field, fallback = 2) {
            const n = Number(field?.tableRows);
            if (Number.isFinite(n) && n > 0) return Math.floor(n);
            return fallback;
        }

        function renderTableFieldPreview(field, options = {}) {
            const compact = !!options.compact;
            const columns = getTableColumnsForPreview(field);
            const configuredRows = getTableRowCount(field, 2);
            const rows = compact ? 1 : Math.max(1, Math.min(configuredRows, 5));

            const renderCell = (col, rowIndex) => {
                const type = String(col?.type || "text").toLowerCase();
                if (type === "number") return rowIndex === 0 ? "0" : "0";
                if (type === "calc") return "0";
                if (type === "dropdown") return "-";
                if (type === "date") return "-";
                return rowIndex === 0 ? "-" : "-";
            };

            return `
                <div style="margin-top:10px;border:1px solid var(--gray-light);border-radius:8px;overflow:auto;background:#fff;">
                    <table style="width:100%;border-collapse:collapse;min-width:${compact ? "420px" : "520px"};font-size:12px;">
                        <thead>
                            <tr style="background:#F8FAFC;">
                                ${columns.map(col => `
                                    <th style="text-align:left;padding:8px;border-bottom:1px solid var(--gray-light);white-space:nowrap;">
                                        ${escapeHtml(col?.name || "Column")}
                                    </th>
                                `).join("")}
                            </tr>
                        </thead>
                        <tbody>
                            ${Array.from({ length: rows }).map((_, rowIndex) => `
                                <tr>
                                    ${columns.map(col => `
                                        <td style="padding:8px;border-bottom:1px solid var(--gray-light);color:${String(col?.type || "").toLowerCase() === "calc" ? "var(--primary)" : "var(--gray-dark)"};font-weight:${String(col?.type || "").toLowerCase() === "calc" ? "700" : "400"};">
                                            ${escapeHtml(renderCell(col, rowIndex))}
                                        </td>
                                    `).join("")}
                                </tr>
                            `).join("")}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function renderFieldValueHtml(field, rawValue) {
            const value = rawValue === undefined || rawValue === null ? "" : rawValue;

            if (field && field.type === "table" && Array.isArray(value)) {
                const rows = value.filter(v => v && typeof v === "object");
                const columns = getTableColumnsForPreview(field).map(c => c.name || "Column");

                if (rows.length === 0) {
                    return `<span class="muted">No table rows.</span>`;
                }

                return `
                    <div style="overflow:auto;border:1px solid var(--gray-light);border-radius:8px;background:#fff;max-width:100%;">
                        <table style="width:100%;border-collapse:collapse;min-width:520px;font-size:12px;">
                            <thead>
                                <tr style="background:#F8FAFC;">
                                    ${columns.map(name => `<th style="text-align:left;padding:8px;border-bottom:1px solid var(--gray-light);white-space:nowrap;">${escapeHtml(name)}</th>`).join("")}
                                </tr>
                            </thead>
                            <tbody>
                                ${rows.map(row => `
                                    <tr>
                                        ${columns.map(name => `<td style="padding:8px;border-bottom:1px solid var(--gray-light);">${escapeHtml(row[name] ?? "-")}</td>`).join("")}
                                    </tr>
                                `).join("")}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            if (Array.isArray(value)) {
                return escapeHtml(value.map(v => typeof v === "object" ? JSON.stringify(v) : String(v)).join(", "));
            }
            if (typeof value === "object" && value !== null) {
                return escapeHtml(JSON.stringify(value));
            }

            return escapeHtml(String(value || "-"));
        }

        async function verifyPrerequisiteSubmissionById(template, rawSubmissionId) {
            if (!template || !template.prerequisiteFormId) {
                return { ok: true, submissionId: null, submission: null };
            }

            const submissionId = String(rawSubmissionId || "").trim().toUpperCase();
            if (!submissionId) {
                return { ok: false, message: "Please input prerequisite submission ID." };
            }

            let submission = null;
            try {
                const res = await apiRequest(`/submissions/${encodeURIComponent(submissionId)}`);
                submission = res.submission || null;
            } catch (_) {
                submission = null;
            }

            if (!submission) {
                return { ok: false, message: "Prerequisite submission ID not found." };
            }

            const prereqTemplate = templates.find(t => t.id === template.prerequisiteFormId);
            if (submission.templateId !== template.prerequisiteFormId) {
                return {
                    ok: false,
                    message: `Submission ID is not from required form: ${prereqTemplate?.name || template.prerequisiteFormId}.`,
                };
            }

            if (submission.status !== "approved") {
                return {
                    ok: false,
                    message: `Prerequisite submission status is ${statusLabel(submission.status)}. It must be Approved.`,
                };
            }

            return { ok: true, submissionId, submission };
        }

        function genSubId() {
            return `SUB-${Date.now().toString(36).toUpperCase()}-${Math.random().toString(36).slice(2, 6).toUpperCase()}`;
        }

        function genTplId() {
            return `TPL-${Date.now().toString(36).toUpperCase()}-${Math.random().toString(36).slice(2, 5).toUpperCase()}`;
        }

        function genFldId() {
            return `FLD-${Date.now().toString(36).toUpperCase()}-${Math.random().toString(36).slice(2, 5).toUpperCase()}`;
        }

        function createField(type) {
            const field = {
                id: genFldId(),
                type,
                label: "",
                required: false,
                options: ["dropdown", "radio", "checkbox"].includes(type) ? ["Option 1"] : undefined,
                formula: type === "calculation" ? "" : undefined,
            };
            if (type === "table") {
                field.tableColumns = [
                    { id: genFldId(), name: "Item", type: "text", formula: "", options: [] },
                    { id: genFldId(), name: "Qty", type: "number", formula: "", options: [] },
                    { id: genFldId(), name: "Price", type: "number", formula: "", options: [] },
                    { id: genFldId(), name: "Total", type: "calc", formula: "{Qty} * {Price}", options: [] },
                ];
                field.tableRows = 3;
            }
            return field;
        }

        window.addEventListener("popstate", () => {
            const view = resolveViewFromPath(window.location.pathname);
            try {
                showView(view, { syncRoute: false });
                if (view === "fillList" && typeof renderTemplateList === "function") {
                    renderTemplateList();
                }
                if (view === "admin" && currentUser && typeof renderAdmin === "function") {
                    renderAdmin();
                }
                if (view === "mySubmissions" && currentUser && typeof showMyView === "function") {
                    showMyView("myDashboard");
                }
            } catch (_) {
                showView("landing", { syncRoute: false });
            }
        });
