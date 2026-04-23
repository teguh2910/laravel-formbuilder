            if (adminPage === "dashboard") {
                const pending = allowedSubs.filter(s => s.status === "pending" || s.status === "in_review").length;
                const approved = allowedSubs.filter(s => s.status === "approved").length;
                const rejected = allowedSubs.filter(s => s.status === "rejected").length;
                const recent = [...allowedSubs].sort((a, b) => new Date(b.submittedAt) - new Date(a.submittedAt)).slice(0, 8);
                content.innerHTML = `
                    <h2 style="margin:0 0 16px;color:var(--primary)">Dashboard</h2>
                    <div class="stats-grid">
                        <div class="stat-card"><div class="stat-num">${allowedTemplates.length}</div><div class="muted">Total Forms</div></div>
                        <div class="stat-card"><div class="stat-num">${allowedSubs.length}</div><div class="muted">Submissions</div></div>
                        <div class="stat-card"><div class="stat-num">${pending}</div><div class="muted">Pending</div></div>
                        <div class="stat-card"><div class="stat-num">${approved}</div><div class="muted">Approved</div></div>
                        <div class="stat-card"><div class="stat-num">${rejected}</div><div class="muted">Rejected</div></div>
                    </div>
                    <div class="card">
                        <h3 style="margin:0 0 12px;color:var(--primary)">Recent Submissions</h3>
                        ${recent.length === 0 ? `<p class="muted">No submissions yet.</p>` : `
                            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                <thead><tr style="border-bottom:2px solid var(--gray-light)">
                                    <th style="text-align:left;padding:8px">ID</th>
                                    <th style="text-align:left;padding:8px">Form</th>
                                    <th style="text-align:left;padding:8px">Employee</th>
                                    <th style="text-align:left;padding:8px">Status</th>
                                </tr></thead>
                                <tbody>
                                    ${recent.map(s => `
                                        <tr style="border-bottom:1px solid var(--gray-light)">
                                            <td style="padding:8px">${s.id}</td>
                                            <td style="padding:8px">${s.templateName}</td>
                                            <td style="padding:8px">${s.employeeName}</td>
                                            <td style="padding:8px"><span class="badge ${badgeClass(s.status)}">${statusLabel(s.status)}</span></td>
                                        </tr>
                                    `).join("")}
                                </tbody>
                            </table>
                        `}
                    </div>
                `;
                return;
            }


