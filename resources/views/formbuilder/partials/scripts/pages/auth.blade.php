        async function login() {
            const username = document.getElementById("login-username").value.trim();
            const password = document.getElementById("login-password").value;

            if (users.length === 0) {
                try {
                    await loadAppData();
                } catch (e) {
                    showToast(e.message || "Failed to load users", "error");
                    return;
                }
            }

            const user = users.find(u => u.username === username && u.password === password);
            if (!user) {
                showToast("Invalid credentials", "error");
                return;
            }
            currentUser = user;
            persistCurrentUserSession(user);

            // non_admin goes to personal portal
            if (user.role === "non_admin") {
                showToast(`Welcome, ${user.name}!`);
                showView("mySubmissions");
                return;
            }

            showToast(`Welcome, ${user.name}!`);
            renderAdmin();
            showView("admin");
        }

        document.getElementById("btn-login").addEventListener("click", () => {
            login();
        });
        document.getElementById("login-password").addEventListener("keydown", (e) => {
            if (e.key === "Enter") login();
        });
