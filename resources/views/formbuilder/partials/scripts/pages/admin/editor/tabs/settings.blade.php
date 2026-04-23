                    ${editorTab === "settings" ? `
                    <div class="card editor-section">
                        <h3 style="margin:0 0 12px;color:var(--primary);font-size:17px;">Form Dependency (Prerequisite)</h3>
                        <p style="margin:0 0 12px;color:var(--gray);font-size:13px;">Set a prerequisite form that must be submitted and approved before this form can be filled.</p>
                        <label class="label">Prerequisite Form</label>
                        <select id="ed-prereq" class="input">
                            <option value="">- No prerequisite (independent form) -</option>
                            ${templates.filter(t => t.id !== editorDraft.id).map(t => `<option value="${t.id}" ${editorDraft.prerequisiteFormId === t.id ? "selected" : ""}>${t.name}</option>`).join("")}
                        </select>
                    </div>
                    ` : ""}

