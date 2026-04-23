                    ${editorTab === "preview" ? `
                    <div class="card editor-section" style="max-width:720px;">
                        <h3 style="margin:0 0 4px;color:var(--primary)">${editorDraft.name || "Untitled"}</h3>
                        <p style="color:var(--gray);margin:0 0 20px;font-size:14px;">${editorDraft.description || "No description"}</p>
                        ${editorDraft.fields.length === 0 ? `<p class="muted" style="text-align:center;padding:20px;">No fields added</p>` : editorDraft.fields.map(f => `
                            <div style="margin-bottom:16px;">
                                <label class="label">${f.label || "Untitled"} ${f.required ? `<span style="color:var(--danger)">*</span>` : ""}</label>
                                <div class="chip">${fieldTypes.find(t => t.value === f.type)?.label || f.type}</div>
                                ${f.type === "table" ? `
                                    ${renderTableFieldPreview(f)}
                                ` : ""}
                            </div>
                        `).join("")}
                    </div>
                    ` : ""}
