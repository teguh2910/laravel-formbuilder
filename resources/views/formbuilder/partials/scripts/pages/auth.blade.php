        async function login() {
            const username = document.getElementById("login-username").value.trim();
            const password = document.getElementById("login-password").value;

            if (!username || !password) {
                showToast("Username and password are required", "error");
                return;
            }

            let authUser = null;
            try {
                const res = await fetch(`${routePrefix}/login`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({ username, password }),
                });
                const raw = await res.text();
                const result = raw ? JSON.parse(raw) : {};
                if (!res.ok) {
                    throw new Error(result?.message || `Request failed (${res.status})`);
                }
                authUser = result.user || null;
            } catch (e) {
                showToast(e.message || "Invalid credentials", "error");
                return;
            }

            if (!authUser) {
                showToast("Invalid credentials", "error");
                return;
            }

            currentUser = authUser;
            persistCurrentUserSession(authUser);
            try {
                await loadAppData({ includeUsers: true });
            } catch (e) {
                showToast(e.message || "Failed to load app data", "error");
                return;
            }

            // non_admin goes to personal portal
            if (authUser.role === "non_admin") {
                showToast(`Welcome, ${authUser.name}!`);
                showView("mySubmissions");
                return;
            }

            showToast(`Welcome, ${authUser.name}!`);
            renderAdmin();
            showView("admin");
        }

        document.getElementById("btn-login").addEventListener("click", () => {
            login();
        });
        document.getElementById("login-password").addEventListener("keydown", (e) => {
            if (e.key === "Enter") login();
        });
