                function syncEditorMetaFields() {
                    const nameEl = document.getElementById("ed-name");
                    const descEl = document.getElementById("ed-description");
                    const deptEl = document.getElementById("ed-department");

                    if (nameEl) editorDraft.name = nameEl.value;
                    if (descEl) editorDraft.description = descEl.value;
                    if (deptEl && currentUser.role === "superadmin") editorDraft.department = deptEl.value;
                }

                const nameInput = document.getElementById("ed-name");
                if (nameInput) {
                    nameInput.addEventListener("input", syncEditorMetaFields);
                }

                const descInput = document.getElementById("ed-description");
                if (descInput) {
                    descInput.addEventListener("input", syncEditorMetaFields);
                }

                const deptInput = document.getElementById("ed-department");
                if (deptInput) {
                    deptInput.addEventListener("change", syncEditorMetaFields);
                }

                document.getElementById("btn-editor-back").addEventListener("click", () => {
                    editorDraft = null;
                    adminPage = "forms";
                    renderAdmin();
                });
                document.getElementById("btn-editor-cancel").addEventListener("click", () => {
                    editorDraft = null;
                    adminPage = "forms";
                    renderAdmin();
                });
                document.getElementById("btn-editor-save").addEventListener("click", () => {
                    syncEditorMetaFields();
                    editorDraft.name = (editorDraft.name || "").trim();
                    editorDraft.description = (editorDraft.description || "").trim();
                    const prereq = document.getElementById("ed-prereq");
                    if (prereq) editorDraft.prerequisiteFormId = prereq.value || null;
                    saveTemplateEditor();
                });
                content.querySelectorAll(".btn-editor-tab").forEach(btn => {
                    btn.addEventListener("click", () => {
                        syncEditorMetaFields();
                        editorTab = btn.dataset.tab;
                        renderAdmin();
                    });
                });
