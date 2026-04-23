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
        document.getElementById("btn-track-search").addEventListener("click", searchTrack);
        document.getElementById("btn-admin-logout").addEventListener("click", () => {
            currentUser = null;
            clearCurrentUserSession();
            adminPage = "dashboard";
            showView("landing");
            showToast("Logged out");
        });
        document.querySelectorAll("[data-admin-page]").forEach(btn => {
            btn.addEventListener("click", () => {
                adminPage = btn.dataset.adminPage;
                renderAdmin();
            });
        });

        (async function initFormBuilderApp() {
            try {
                await loadAppData();
                currentUser = restoreCurrentUserSession();
                renderTemplateList();
                const initialView = resolveViewFromPath(window.location.pathname);
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
                showView(initialView, { syncRoute: false });
                if (currentUser && initialView === "admin") {
                    renderAdmin();
                }
                if (currentUser && initialView === "mySubmissions" && currentUser.role === "non_admin") {
                    if (typeof showMyView === "function") showMyView("myDashboard");
                    if (typeof renderMySubmissionsDashboard === "function") renderMySubmissionsDashboard();
                }
            } catch (e) {
                showToast(e.message || "Failed to load app data", "error");
            }
        })();
