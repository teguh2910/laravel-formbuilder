                content.querySelectorAll(".btn-add-type").forEach(btn => {
                    btn.addEventListener("click", () => {
                        editorDraft.fields.push(createField(btn.dataset.type));
                        editorTab = "fields";
                        renderAdmin();
                    });
                });

                content.querySelectorAll(".btn-remove-field").forEach(btn => {
                    btn.addEventListener("click", () => {
                        editorDraft.fields = editorDraft.fields.filter(f => f.id !== btn.dataset.id);
                        renderAdmin();
                    });
                });
                content.querySelectorAll(".btn-move-field").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const i = Number(btn.dataset.index);
                        const d = Number(btn.dataset.dir);
                        const ni = i + d;
                        if (ni < 0 || ni >= editorDraft.fields.length) return;
                        const arr = [...editorDraft.fields];
                        [arr[i], arr[ni]] = [arr[ni], arr[i]];
                        editorDraft.fields = arr;
                        renderAdmin();
                    });
                });
                content.querySelectorAll(".ed-field-label").forEach(input => {
                    input.addEventListener("input", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (f) f.label = input.value;
                    });
                });
                content.querySelectorAll(".ed-field-required").forEach(input => {
                    input.addEventListener("change", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (f) f.required = input.checked;
                    });
                });
                content.querySelectorAll(".ed-field-options").forEach(input => {
                    input.addEventListener("input", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (f) f.options = input.value.split(",").map(v => v.trim()).filter(Boolean);
                    });
                });
                content.querySelectorAll(".ed-field-formula").forEach(input => {
                    input.addEventListener("input", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (f) f.formula = input.value;
                    });
                });
                content.querySelectorAll(".ed-table-rows").forEach(input => {
                    input.addEventListener("change", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (!f || f.type !== "table") return;
                        const n = Number(input.value);
                        f.tableRows = Number.isFinite(n) && n > 0 ? Math.floor(n) : 1;
                        renderAdmin();
                    });
                });

                content.querySelectorAll(".ed-table-col-name").forEach(input => {
                    input.addEventListener("input", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (!f || f.type !== "table") return;
                        const colIndex = Number(input.dataset.colIndex);
                        const cols = getTableColumnsForPreview(f);
                        if (!cols[colIndex]) return;
                        cols[colIndex].name = input.value;
                        f.tableColumns = cols;
                    });
                });

                content.querySelectorAll(".ed-table-col-type").forEach(input => {
                    input.addEventListener("change", () => {
                        const f = editorDraft.fields.find(x => x.id === input.dataset.id);
                        if (!f || f.type !== "table") return;
                        const colIndex = Number(input.dataset.colIndex);
                        const cols = getTableColumnsForPreview(f);
                        if (!cols[colIndex]) return;
                        cols[colIndex].type = input.value || "text";
                        f.tableColumns = cols;
                        renderAdmin();
                    });
                });

                content.querySelectorAll(".btn-add-table-col").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const f = editorDraft.fields.find(x => x.id === btn.dataset.id);
                        if (!f || f.type !== "table") return;
                        const cols = getTableColumnsForPreview(f);
                        cols.push({
                            id: genFldId(),
                            name: `Column ${cols.length + 1}`,
                            type: "text",
                            formula: "",
                            options: [],
                        });
                        f.tableColumns = cols;
                        renderAdmin();
                    });
                });

                content.querySelectorAll(".btn-remove-table-col").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const f = editorDraft.fields.find(x => x.id === btn.dataset.id);
                        if (!f || f.type !== "table") return;
                        const colIndex = Number(btn.dataset.colIndex);
                        const cols = getTableColumnsForPreview(f);
                        if (cols.length <= 1) {
                            showToast("Table must have at least 1 column.", "error");
                            return;
                        }
                        cols.splice(colIndex, 1);
                        f.tableColumns = cols;
                        renderAdmin();
                    });
                });
