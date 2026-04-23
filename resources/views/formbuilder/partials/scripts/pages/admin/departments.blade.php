            if (adminPage === "departments") {
                content.innerHTML = `
                    <h2 style="margin:0 0 16px;color:var(--primary)">Departments</h2>
                    <div class="grid">
                        ${depts.map(d => `
                            <div class="card" style="padding:16px">
                                <h4 style="margin:0 0 4px;color:var(--primary)">${d.name}</h4>
                                <span class="badge" style="background:var(--light);color:var(--gray-dark)">${d.code}</span>
                            </div>
                        `).join("")}
                    </div>
                `;
                return;
            }


