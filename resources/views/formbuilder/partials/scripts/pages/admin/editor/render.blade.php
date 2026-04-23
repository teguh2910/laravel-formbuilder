            if (adminPage === "formEditor") {
                if (!editorDraft) {
                    goAdminPage("forms");
                    return;
                }

                content.innerHTML = `
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <button id="btn-editor-back" class="btn btn-ghost"><- Back</button>
                            <h2 style="margin:0;color:var(--primary);font-size:22px;">${editorDraft.name ? "Edit Form" : "New Form"}</h2>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button id="btn-editor-cancel" class="btn btn-outline">Cancel</button>
                            <button id="btn-editor-save" class="btn btn-primary">Save Form</button>
                        </div>
                    </div>
                    <div class="card editor-section" style="margin-bottom:20px;">
                        <div class="editor-grid">
                            <div>
                                <label class="label">Form Name *</label>
                                <input id="ed-name" class="input" value="${editorDraft.name || ""}">
                            </div>
                            <div>
                                <label class="label">Department *</label>
                                <select id="ed-department" class="input" ${currentUser.role === "superadmin" ? "" : "disabled"}>
                                    <option value="">Select</option>
                                    ${depts.map(d => `<option value="${d.id}" ${editorDraft.department === d.id ? "selected" : ""}>${d.name}</option>`).join("")}
                                </select>
                            </div>
                            <div style="grid-column:1/-1">
                                <label class="label">Description</label>
                                <textarea id="ed-description" class="input" style="min-height:80px">${editorDraft.description || ""}</textarea>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;gap:4px;margin-bottom:20px;background:var(--white);border-radius:10px;padding:4px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
                        ${[
                            ["fields","Form Fields"],
                            ["approval","Approval Flow"],
                            ["settings","Dependencies"],
                            ["preview","Preview"],
                        ].map(([id,label]) => `
                            <button class="btn-editor-tab" data-tab="${id}" style="flex:1;padding:10px 16px;border-radius:8px;border:none;background:${editorTab === id ? "var(--primary)" : "transparent"};color:${editorTab === id ? "var(--white)" : "var(--gray)"};font-weight:600;cursor:pointer;font-size:14px;">${label}</button>
                        `).join("")}
                    </div>
@include('formbuilder::formbuilder.partials.scripts.pages.admin.editor.tabs.fields')
@include('formbuilder::formbuilder.partials.scripts.pages.admin.editor.tabs.approval')
@include('formbuilder::formbuilder.partials.scripts.pages.admin.editor.tabs.settings')
@include('formbuilder::formbuilder.partials.scripts.pages.admin.editor.tabs.preview')
                `;

