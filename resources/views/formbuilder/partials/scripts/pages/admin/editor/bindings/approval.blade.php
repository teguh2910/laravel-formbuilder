                const addInternalApproverBtn = document.getElementById("btn-add-approver-internal");
                if (addInternalApproverBtn) {
                    addInternalApproverBtn.addEventListener("click", () => {
                        const internalCount = (editorDraft.approvalFlow || []).filter(a => (a.approvalType || "internal") !== "external").length;
                        const internalLevel = Math.min(8, internalCount + 1);
                        const buildSuperiorLabel = (level) => Array.from({ length: Math.min(8, Math.max(1, Number(level) || 1)) })
                            .map((_, idx) => (idx === 0 ? "Superior" : "of Superior"))
                            .join(" ");
                        editorDraft.approvalFlow.push({
                            id: `APR-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 5)}`,
                            role: buildSuperiorLabel(internalLevel),
                            internalLevel,
                            approvalType: "internal",
                        });
                        renderAdmin();
                    });
                }

                const addExternalApproverBtn = document.getElementById("btn-add-approver-external");
                if (addExternalApproverBtn) {
                    addExternalApproverBtn.addEventListener("click", () => {
                        const nameOptions = [...new Set((users || [])
                            .map(u => (u.name || "").trim())
                            .filter(Boolean))];
                        const defaultExternalName = nameOptions[0] || "external_approver";
                        editorDraft.approvalFlow.push({
                            id: `APR-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 5)}`,
                            role: defaultExternalName,
                            approvalType: "external",
                        });
                        renderAdmin();
                    });
                }
                content.querySelectorAll(".btn-remove-approver").forEach(btn => {
                    btn.addEventListener("click", () => {
                        editorDraft.approvalFlow = editorDraft.approvalFlow.filter(a => a.id !== btn.dataset.id);
                        renderAdmin();
                    });
                });
                content.querySelectorAll(".ed-approver-role").forEach(input => {
                    input.addEventListener("change", () => {
                        const a = editorDraft.approvalFlow.find(x => x.id === input.dataset.id);
                        if (a) a.role = input.value;
                    });
                    input.addEventListener("input", () => {
                        const a = editorDraft.approvalFlow.find(x => x.id === input.dataset.id);
                        if (a) a.role = input.value;
                    });
                });
                content.querySelectorAll(".ed-approver-internal-level").forEach(input => {
                    input.addEventListener("change", () => {
                        const a = editorDraft.approvalFlow.find(x => x.id === input.dataset.id);
                        if (!a) return;
                        const internalLevel = Math.min(8, Math.max(1, Number(input.value) || 1));
                        const roleLabel = Array.from({ length: internalLevel })
                            .map((_, idx) => (idx === 0 ? "Superior" : "of Superior"))
                            .join(" ");
                        a.internalLevel = internalLevel;
                        a.role = roleLabel;
                    });
                });
