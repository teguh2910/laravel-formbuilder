        document.getElementById("btn-open-login").addEventListener("click", () => {
            if (currentUser) {
                if (currentUser.role === "non_admin") {
                    showView("mySubmissions");
                    if (typeof showMyView === "function") {
                        showMyView("myDashboard");
                    }
                    if (typeof renderMySubmissionsDashboard === "function") {
                        renderMySubmissionsDashboard();
                    }
                } else {
                    showView("admin");
                    renderAdmin();
                }
                return;
            }
            showView("login");
        });
        document.getElementById("btn-back-home").addEventListener("click", () => showView("landing"));
        document.getElementById("btn-open-fill").addEventListener("click", () => { renderTemplateList(); showView("fillList"); });
        document.getElementById("btn-open-track").addEventListener("click", () => showView("track"));
        document.getElementById("btn-fill-list-back").addEventListener("click", () => showView("landing"));
        document.getElementById("btn-fill-form-back").addEventListener("click", () => showView("fillList"));
        document.getElementById("btn-track-back").addEventListener("click", () => showView("landing"));
        document.getElementById("btn-submit-form").addEventListener("click", submitForm);
        if (!document.getElementById("form-track-search")) {
            document.getElementById("btn-track-search").addEventListener("click", searchTrack);
        }
        document.getElementById("btn-admin-logout").addEventListener("click", () => {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = `${routePrefix}/logout`;

            const tokenInput = document.createElement("input");
            tokenInput.type = "hidden";
            tokenInput.name = "_token";
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            document.body.appendChild(form);
            form.submit();
        });
        document.querySelectorAll("[data-admin-page]").forEach(btn => {
            if (btn.tagName === "A" && btn.getAttribute("href")) return;
            btn.addEventListener("click", () => {
                adminPage = btn.dataset.adminPage;
                renderAdmin();
            });
        });

        (async function initFormBuilderApp() {
            try {
                const initialView = initialServerView || resolveViewFromPath(window.location.pathname);
                currentUser = restoreCurrentUserSession();
                hydrateAppDataFromServer();
                if (templates.length > 0) {
                    renderTemplateList();
                }
                if ((initialView === "admin" || initialView === "mySubmissions") && !currentUser) {
                    showView("login", { replaceRoute: true });
                    return;
                }
                if (currentUser && initialView === "login") {
                    if (currentUser.role === "non_admin") {
                        showView("mySubmissions", { replaceRoute: true });
                        if (typeof showMyView === "function") showMyView("myDashboard");
                        if (typeof renderMySubmissionsDashboard === "function") renderMySubmissionsDashboard();
                    } else {
                        showView("admin", { replaceRoute: true });
                        renderAdmin();
                    }
                    return;
                }
                if (currentUser && initialView === "mySubmissions" && currentUser.role !== "non_admin") {
                    showView("admin", { replaceRoute: true });
                    renderAdmin();
                    return;
                }
                showView(initialView, { syncRoute: false, forceLocal: true });
                if (initialView === "fillForm") {
                    const params = new URLSearchParams(window.location.search);
                    const templateId = (serverInitialData?.selectedTemplateId || params.get("template") || "").trim();
                    if (!templateId) {
                        showView("fillList");
                        return;
                    }

                    const found = templates.find(t => t.id === templateId);
                    if (!found) {
                        showToast("Form template not found", "error");
                        showView("fillList");
                        return;
                    }

                    selectedTemplate = found;
                    formData = {};
                    verifiedPrerequisiteSubmissionId = null;
                    internalApproverSelections = {};
                    renderDynamicFields();
                    document.getElementById("selected-form-title").textContent = selectedTemplate.name;
                }
                if (initialView === "track" && serverInitialData && typeof serverInitialData === "object") {
                    const inputEl = document.getElementById("track-id");
                    const resultEl = document.getElementById("track-result");
                    if (inputEl && serverInitialData.trackQuery) {
                        inputEl.value = serverInitialData.trackQuery;
                    }
                    if (resultEl && serverInitialData.trackSubmission) {
                        resultEl.innerHTML = renderTrackingResultHtml(serverInitialData.trackSubmission, { showEmployee: true });
                    } else if (resultEl && serverInitialData.trackNotFound) {
                        resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Submission not found.</div>`;
                    }
                }
                if (currentUser && initialView === "admin") {
                    if (serverInitialData && typeof serverInitialData === "object") {
                        if (serverInitialData.adminEditorDraft) {
                            editorDraft = JSON.parse(JSON.stringify(serverInitialData.adminEditorDraft));
                        }
                        if (serverInitialData.adminEditorTab) {
                            editorTab = serverInitialData.adminEditorTab;
                        }
                    }
                    renderAdmin();
                }
                if (currentUser && initialView === "mySubmissions" && currentUser.role === "non_admin") {
                    if (typeof showMyView === "function") showMyView("myDashboard");
                    if (typeof renderMySubmissionsDashboard === "function") renderMySubmissionsDashboard();
                }
                if (serverFlash && typeof serverFlash === "object" && serverFlash.message) {
                    showToast(serverFlash.message, serverFlash.type || "success");
                }
            } catch (e) {
                showToast(e.message || "Failed to load app data", "error");
            }
        })();
