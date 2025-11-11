
const clickData = {};
const cancelTime = {};
const stopState = {}; // true si STOP

function clearUbbcClasses(button) {
    button.classList.forEach(cls => {
        if (
            cls.startsWith("fl-bd-") ||
            cls.startsWith("fl-txt-") ||
            cls.startsWith("fl-bg-") ||
            cls.startsWith("fl-bd-hov-") ||
            cls.startsWith("fl-txt-hov-") ||
            cls.startsWith("fl-bg-hov-")
        ) {
            button.classList.remove(cls);
        }
    });
    button.classList.add("fl-bg-white");
    button.classList.add("fl-txt-hov-white");
}

function getState(bib) {
    const now = Date.now();
    const lastClick = clickData[bib] || 0;
    const lastCancel = cancelTime[bib] || 0;

    if (stopState[bib]) return "fullblood";
    if (!lastClick && !lastCancel) return "anis";
    if (lastCancel && now - lastCancel < 5 * 60 * 1000) return "sadsea";
    if (lastClick && now - lastClick < 5 * 60 * 1000) return "blood";
    if (lastClick && now - lastClick < 15 * 60 * 1000) return "apricot";
    return "electric";
}

function applyColor(button, state) {
    const styles = {
        anis: ["fl-bd-anis", "fl-txt-anis", "fl-bg-white", "fl-txt-hov-white", "fl-bg-hov-anis"],
        prune: ["fl-bd-prune", "fl-txt-prune", "fl-bg-white", "fl-txt-hov-white", "fl-bg-hov-blood"],
        blood: ["fl-bd-blood", "fl-txt-blood", "fl-bg-hov-blood", "fl-bd-hov-blood"],
        apricot: ["fl-bd-apricot", "fl-txt-apricot", "fl-bg-hov-apricot", "fl-bd-hov-blood"],
        electric: ["fl-bd-electric", "fl-txt-electric", "fl-bg-hov-electric", "fl-bd-hov-electric"],
        sadsea: ["fl-bd-sadsea", "fl-txt-sadsea", "fl-bg-hov-sadsea", "fl-bd-hov-sadsea"]
    };
    clearUbbcClasses(button);
    styles[state].forEach(cls => button.classList.add(cls));
}

function updateButtonColor(bib, button) {
    applyColor(button, getState(bib));
}

function toggleBib(bib) {
    const button = document.getElementById(`bib-${bib}`);
    fetch(`toggle_lap.php?bib=${bib}`)
        .then(response => response.json())
        .then(data => {
            const now = Date.now();
            if (data.action === 'added') {
                clickData[bib] = now;
                cancelTime[bib] = 0;
                stopState[bib] = data.control === "STOP";
            } else if (data.action === 'removed') {
                cancelTime[bib] = now;
                stopState[bib] = false;
            }

            document.getElementById("runner-name").innerHTML = `
                <h1 class="lead text-center fl-txt-prune fw-bold pt-2">${data.bib} • ${data.firstname.toUpperCase()} • ${data.race.toUpperCase()}</h1>
            `;
            updateButtonColor(bib, button);
        });
}

setInterval(() => {
    for (const bib in clickData) {
        const button = document.getElementById(`bib-${bib}`);
        if (button) updateButtonColor(bib, button);
    }
}, 30000);

document.addEventListener("DOMContentLoaded", () => {
    fetch("get_last_laps.php")
        .then(res => res.json())
        .then(data => {
            data.forEach(({ bib, timestamp, canceled, control }) => {
                const button = document.getElementById(`bib-${bib}`);
                if (!button) return;
                stopState[bib] = (control === "STOP" && !canceled);
                if (canceled) cancelTime[bib] = timestamp;
                else {
                    clickData[bib] = timestamp;
                    cancelTime[bib] = 0;
                }
                updateButtonColor(bib, button);
            });

            document.querySelectorAll("button[id^='bib-']").forEach(button => {
                const bib = button.id.replace("bib-", "");
                updateButtonColor(bib, button);
            });
        });
});
