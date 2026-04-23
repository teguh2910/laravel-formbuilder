        async function searchTrack() {
            const id = document.getElementById("track-id").value.trim().toLowerCase();
            const resultEl = document.getElementById("track-result");
            if (!id) {
                resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Please enter submission ID.</div>`;
                return;
            }

            let found = null;
            try {
                const res = await apiRequest(`/submissions/${encodeURIComponent(id.toUpperCase())}`);
                found = res.submission || null;
            } catch (e) {
                found = submissions.find(s => s.id.toLowerCase() === id);
            }

            if (!found) {
                resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Submission not found.</div>`;
                return;
            }

            resultEl.innerHTML = renderTrackingResultHtml(found, { showEmployee: true });
        }
