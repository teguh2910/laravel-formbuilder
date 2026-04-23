                    ${editorTab === "approval" ? `
                    <div class="card editor-section">
                        ${(() => {
                            const buildSuperiorLabel = (level) => {
                                const lvl = Math.min(8, Math.max(1, Number(level) || 1));
                                return Array.from({ length: Math.max(1, lvl) })
                                    .map((_, idx) => (idx === 0 ? "Superior" : "of Superior"))
                                    .join(" ");
                            };
                            let internalCounter = 0;
                            editorDraft.approvalFlow = (editorDraft.approvalFlow || []).map(a => {
                                const approvalType = a.approvalType === "external" ? "external" : "internal";
                                if (approvalType === "external") {
                                    return { ...a, approvalType };
                                }
                                internalCounter += 1;
                                const internalLevel = Number(a.internalLevel) || internalCounter;
                                return {
                                    ...a,
                                    approvalType,
                                    internalLevel,
                                    role: a.role || buildSuperiorLabel(internalLevel),
                                };
                            });
                            const externalUserOptions = [...new Set((users || [])
                                .map(u => (u.name || "").trim())
                                .filter(Boolean))];
                            const internalApprovers = editorDraft.approvalFlow.filter(a => a.approvalType !== "external");
                            const externalApprovers = editorDraft.approvalFlow.filter(a => a.approvalType === "external");
                            const internalLevelOptions = Array.from({ length: 8 }, (_, idx) => idx + 1);
                            const renderInternalRows = () => internalApprovers.map((a, i) => `
                                <div class="field-row">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                        <span class="chip">Internal Step ${i + 1}</span>
                                        <button class="btn btn-ghost btn-remove-approver" data-id="${a.id}" style="padding:4px 8px;color:var(--danger);">Remove</button>
                                    </div>
                                    <div class="editor-grid">
                                        <div>
                                            <label class="label">Hierarchy Level</label>
                                            <select class="input ed-approver-internal-level" data-id="${a.id}">
                                                ${internalLevelOptions.map(level => `<option value="${level}" ${Number(a.internalLevel || 1) === level ? "selected" : ""}>${buildSuperiorLabel(level)}</option>`).join("")}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            `).join("");
                            const renderExternalRows = () => externalApprovers.map((a, i) => `
                                <div class="field-row">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                                        <span class="chip">External Step ${i + 1}</span>
                                        <button class="btn btn-ghost btn-remove-approver" data-id="${a.id}" style="padding:4px 8px;color:var(--danger);">Remove</button>
                                    </div>
                                    <div class="editor-grid">
                                        <div>
                                            <label class="label">External Approver (User Name)</label>
                                            <select class="input ed-approver-role" data-id="${a.id}">
                                                ${externalUserOptions.length === 0
                                                    ? `<option value="${escapeHtml(a.role || a.title || a.name || "external_approver")}">${escapeHtml(a.role || a.title || a.name || "external_approver")}</option>`
                                                    : externalUserOptions.map(name => `<option value="${escapeHtml(name)}" ${(a.role || a.title || a.name || externalUserOptions[0]) === name ? "selected" : ""}>${escapeHtml(name)}</option>`).join("")
                                                }
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            `).join("");
                            return `
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <div>
                                <h3 style="margin:0 0 4px;color:var(--primary);font-size:17px;">Approval Flow</h3>
                                <p style="margin:0;color:var(--gray);font-size:13px;">Separate approvers into Internal and External approval flow.</p>
                            </div>
                        </div>
                        <div id="ed-approvers-wrap" style="display:grid;gap:16px;">
                            <div class="card" style="background:#fff;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                    <div style="font-weight:700;color:var(--primary);">Internal Approval</div>
                                    <button id="btn-add-approver-internal" class="btn btn-primary" style="padding:8px 12px;">Add Internal</button>
                                </div>
                                ${internalApprovers.length === 0 ? `<p class="muted">No internal approver.</p>` : renderInternalRows()}
                            </div>
                            <div class="card" style="background:#fff;">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                    <div style="font-weight:700;color:var(--primary);">External Approval</div>
                                    <button id="btn-add-approver-external" class="btn btn-outline" style="padding:8px 12px;">Add External</button>
                                </div>
                                ${externalApprovers.length === 0 ? `<p class="muted">No external approver.</p>` : renderExternalRows()}
                            </div>
                        </div>
                            `;
                        })()}
                    </div>
                    ` : ""}
