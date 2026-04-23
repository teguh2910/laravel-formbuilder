            if (adminPage === "forms") {
                content.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <h2 style="margin:0;color:var(--primary)">FORM List</h2>
                        <button id="btn-new-template" class="btn btn-primary">Create Form</button>
                    </div>
                    <div class="card">
                        ${allowedTemplates.length === 0 ? `<p class="muted">No forms available.</p>` : `
                            <div style="overflow:auto;">
                                <table style="width:100%;border-collapse:collapse;font-size:14px;min-width:920px;">
                                    <thead>
                                        <tr style="border-bottom:2px solid var(--gray-light);">
                                            <th style="text-align:left;padding:8px;">Form Name</th>
                                            <th style="text-align:left;padding:8px;">Description</th>
                                            <th style="text-align:left;padding:8px;">Department</th>
                                            <th style="text-align:left;padding:8px;">Fields</th>
                                            <th style="text-align:left;padding:8px;">Status</th>
                                            <th style="text-align:left;padding:8px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${allowedTemplates.map(t => {
                                            const dept = depts.find(d => d.id === t.department);
                                            const deptLabel = dept ? `${dept.name} (${dept.code || "-"})` : "-";
                                            return `
                                                <tr style="border-bottom:1px solid var(--gray-light);">
                                                    <td style="padding:8px;font-weight:600;color:var(--primary);">${escapeHtml(t.name || "-")}</td>
                                                    <td style="padding:8px;" class="muted">${escapeHtml(t.description || "-")}</td>
                                                    <td style="padding:8px;">${escapeHtml(deptLabel)}</td>
                                                    <td style="padding:8px;">${t.fields.length}</td>
                                                    <td style="padding:8px;">
                                                        <span class="badge ${t.published ? "status-approved" : "status-pending"}">${t.published ? "Published" : "Draft"}</span>
                                                    </td>
                                                    <td style="padding:8px;">
                                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                                            <button class="btn btn-outline btn-edit-template" data-id="${t.id}" style="padding:6px 10px;font-size:12px;">Edit</button>
                                                            <button class="btn btn-ghost btn-toggle-template" data-id="${t.id}" style="padding:6px 10px;font-size:12px;background:var(--light);">${t.published ? "Unpublish" : "Publish"}</button>
                                                            <button class="btn btn-ghost btn-delete-template" data-id="${t.id}" style="padding:6px 10px;font-size:12px;color:var(--danger);">Delete</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            `;
                                        }).join("")}
                                    </tbody>
                                </table>
                            </div>
                        `}
                    </div>
                `;
                const btnNew = document.getElementById("btn-new-template");
                if (btnNew) btnNew.addEventListener("click", () => openTemplateEditor());
                content.querySelectorAll(".btn-edit-template").forEach(btn => {
                    btn.addEventListener("click", () => openTemplateEditor(btn.dataset.id));
                });
                content.querySelectorAll(".btn-toggle-template").forEach(btn => {
                    btn.addEventListener("click", async () => {
                        try {
                            await apiRequest(`/templates/${btn.dataset.id}/toggle-publish`, {
                                method: "POST",
                            });
                            await loadAppData();
                            showToast("Form status updated");
                            renderAdmin();
                        } catch (e) {
                            showToast(e.message || "Failed to update publish status", "error");
                        }
                    });
                });
                content.querySelectorAll(".btn-delete-template").forEach(btn => {
                    btn.addEventListener("click", async () => {
                        try {
                            await apiRequest(`/templates/${btn.dataset.id}`, {
                                method: "DELETE",
                            });
                            await loadAppData();
                            showToast("Form deleted");
                            renderAdmin();
                        } catch (e) {
                            showToast(e.message || "Failed to delete form", "error");
                        }
                    });
                });
                return;
            }
