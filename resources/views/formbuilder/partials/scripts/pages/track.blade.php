        function searchTrack() {
            const id = document.getElementById("track-id").value.trim().toUpperCase();
            const resultEl = document.getElementById("track-result");
            if (!id) {
                resultEl.innerHTML = `<div style="padding:12px;background:#FEE2E2;color:var(--danger);border-radius:8px;">Please enter submission ID.</div>`;
                return;
            }

            window.location.assign(getUrlWithQuery(`${routePrefix}/track`, { id }));
        }
