            if (adminPage === "users") {
                content.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 16px;">
                        <h2 style="margin:0;color:var(--primary)">Users</h2>
                        <button id="btn-user-add" class="btn btn-primary" style="padding:8px 12px;">Add User</button>
                    </div>
                    <div class="card">
                        <table style="width:100%;border-collapse:collapse;font-size:14px;">
                            <thead><tr style="border-bottom:2px solid var(--gray-light)">
                                <th style="text-align:left;padding:8px">Name</th>
                                <th style="text-align:left;padding:8px">Username</th>
                                <th style="text-align:left;padding:8px">Role</th>
                                <th style="text-align:left;padding:8px">Department</th>
                                <th style="text-align:left;padding:8px">Action</th>
                            </tr></thead>
                            <tbody>
                                ${users.map(u => {
                                    const dep = depts.find(d => d.id === u.department)?.name || "-";
                                    return `
                                        <tr style="border-bottom:1px solid var(--gray-light)">
                                            <td style="padding:8px">${u.name}</td>
                                            <td style="padding:8px">${u.username}</td>
                                            <td style="padding:8px">${u.role}</td>
                                            <td style="padding:8px">${dep}</td>
                                            <td style="padding:8px">
                                                <div style="display:flex;gap:8px;">
                                                    <button class="btn btn-outline btn-user-edit" data-id="${u.id}" style="padding:4px 8px;">Edit</button>
                                                    ${u.role === "superadmin"
                                                        ? ""
                                                        : `<button class="btn btn-ghost btn-user-delete" data-id="${u.id}" style="padding:4px 8px;color:var(--danger);">Delete</button>`}
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                                }).join("")}
                            </tbody>
                        </table>
                    </div>
                    <div id="user-modal" class="hidden" style="position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1200;display:flex;align-items:center;justify-content:center;padding:16px;">
                        <div class="card" style="width:100%;max-width:560px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                                <h3 id="user-modal-title" style="margin:0;color:var(--primary);font-size:18px;">Add User</h3>
                                <button id="btn-user-modal-close" class="btn btn-ghost" style="padding:4px 8px;">Close</button>
                            </div>
                            <div class="editor-grid">
                                <input type="hidden" id="user-id">
                                <div>
                                    <label class="label">Name *</label>
                                    <input id="user-name" class="input">
                                </div>
                                <div>
                                    <label class="label">Username *</label>
                                    <input id="user-username" class="input">
                                </div>
                                <div>
                                    <label class="label">Password <span id="user-password-required">*</span></label>
                                    <input id="user-password" class="input" type="text" placeholder="Required for new user">
                                </div>
                                <div>
                                    <label class="label">Email</label>
                                    <input id="user-email" class="input" type="email">
                                </div>
                                <div>
                                    <label class="label">Role *</label>
                                    <input id="user-role" class="input" list="user-role-options" placeholder="Contoh: spv">
                                    <datalist id="user-role-options">
                                        ${[...new Set(users.map(u => (u.role || "").trim()).filter(Boolean))].map(role => `<option value="${role}">`).join("")}
                                        <option value="superadmin">
                                        <option value="admin_department">
                                        <option value="non_admin">
                                        <option value="supervisor">
                                        <option value="manager">
                                        <option value="group_manager">
                                        <option value="direktur">
                                        <option value="vice_presiden_direktur">
                                        <option value="presiden_direktur">
                                    </datalist>
                                </div>
                                <div style="grid-column:1/-1">
                                    <label class="label">Department</label>
                                    <select id="user-department" class="input">
                                        <option value="">Select</option>
                                        ${depts.map(d => `<option value="${d.id}">${d.name}</option>`).join("")}
                                    </select>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
                                <button id="btn-user-cancel" class="btn btn-outline">Cancel</button>
                                <button id="btn-user-save" class="btn btn-primary">Save User</button>
                            </div>
                        </div>
                    </div>
                `;

                const userModal = document.getElementById("user-modal");
                const userIdEl = document.getElementById("user-id");
                const userNameEl = document.getElementById("user-name");
                const userUsernameEl = document.getElementById("user-username");
                const userPasswordEl = document.getElementById("user-password");
                const userEmailEl = document.getElementById("user-email");
                const userRoleEl = document.getElementById("user-role");
                const userDepartmentEl = document.getElementById("user-department");
                const userPasswordRequiredEl = document.getElementById("user-password-required");
                const userModalTitleEl = document.getElementById("user-modal-title");

                function syncUserRoleState() {
                    const isSuperadmin = (userRoleEl.value || "").trim().toLowerCase() === "superadmin";
                    userDepartmentEl.disabled = isSuperadmin;
                    if (isSuperadmin) userDepartmentEl.value = "";
                }

                function resetUserForm() {
                    userIdEl.value = "";
                    userNameEl.value = "";
                    userUsernameEl.value = "";
                    userPasswordEl.value = "";
                    userEmailEl.value = "";
                    userRoleEl.value = "spv";
                    userDepartmentEl.value = "";
                    userPasswordRequiredEl.textContent = "*";
                    userModalTitleEl.textContent = "Add User";
                    syncUserRoleState();
                }

                function openUserModalForCreate() {
                    resetUserForm();
                    userModal.classList.remove("hidden");
                }

                function openUserModalForEdit(user) {
                    userIdEl.value = user.id || "";
                    userNameEl.value = user.name || "";
                    userUsernameEl.value = user.username || "";
                    userPasswordEl.value = "";
                    userEmailEl.value = user.email || "";
                    userRoleEl.value = user.role || "spv";
                    userDepartmentEl.value = user.department || "";
                    userPasswordRequiredEl.textContent = "(optional)";
                    userModalTitleEl.textContent = "Edit User";
                    syncUserRoleState();
                    userModal.classList.remove("hidden");
                }

                function closeUserModal() {
                    userModal.classList.add("hidden");
                }

                document.getElementById("btn-user-add").addEventListener("click", openUserModalForCreate);
                document.getElementById("btn-user-modal-close").addEventListener("click", closeUserModal);
                document.getElementById("btn-user-cancel").addEventListener("click", closeUserModal);
                userRoleEl.addEventListener("change", syncUserRoleState);

                content.querySelectorAll(".btn-user-edit").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const user = users.find(u => String(u.id) === String(btn.dataset.id));
                        if (!user) return;
                        openUserModalForEdit(user);
                    });
                });

                content.querySelectorAll(".btn-user-delete").forEach(btn => {
                    btn.addEventListener("click", async () => {
                        const user = users.find(u => String(u.id) === String(btn.dataset.id));
                        if (!user) return;
                        if (!confirm(`Delete user ${user.username}?`)) return;
                        try {
                            await apiRequest(`/users/${user.id}`, { method: "DELETE" });
                            await loadAppData();
                            renderAdmin();
                            showToast("User deleted");
                        } catch (e) {
                            showToast(e.message || "Failed to delete user", "error");
                        }
                    });
                });

                document.getElementById("btn-user-save").addEventListener("click", async () => {
                    const payload = {
                        id: userIdEl.value ? Number(userIdEl.value) : null,
                        name: userNameEl.value.trim(),
                        username: userUsernameEl.value.trim(),
                        password: userPasswordEl.value,
                        email: userEmailEl.value.trim() || null,
                        role: userRoleEl.value.trim(),
                        department: userDepartmentEl.value || null,
                    };

                    if (!payload.name || !payload.username) {
                        showToast("Name and username are required", "error");
                        return;
                    }
                    if (!payload.role) {
                        showToast("Role is required", "error");
                        return;
                    }
                    if (!payload.id && !payload.password) {
                        showToast("Password is required for new user", "error");
                        return;
                    }
                    if (payload.role.toLowerCase() !== "superadmin" && !payload.department) {
                        showToast("Department is required for non-superadmin user", "error");
                        return;
                    }

                    try {
                        await apiRequest("/users", {
                            method: "POST",
                            body: payload,
                        });
                        await loadAppData();
                        closeUserModal();
                        renderAdmin();
                        showToast("User saved");
                    } catch (e) {
                        showToast(e.message || "Failed to save user", "error");
                    }
                });

                return;
            }
        }
