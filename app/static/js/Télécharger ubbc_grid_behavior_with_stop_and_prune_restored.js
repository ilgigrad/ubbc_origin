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
}

function getState(bib) {
    const now = Date.now();
    const lastClick = clickData[bib] || 0;
    const lastCancel = cancelTime[bib] || 0;

    if (stopState[bib]) return "blood";
    if (!lastClick && !lastCancel) return "gray";
    if (lastCancel && now - lastCancel < 5 * 60 * 1000) return "prune";
    if (lastClick && now - lastClick < 5 * 60 * 1000) return "peach";
    if (lastClick && now - lastClick < 15 * 60 * 1000) return "apricot";
    return "electric";
}

function applyColor(button, state) {
    const styles = {
        gray: ["fl-bd-gray", "fl-txt-gray", "fl-bg-hov-prune", "fl-bg-white", "fl-txt-hov-white"],
        peach: ["fl-bd-peach", "fl-txt-peach", "fl-bg-hov-peach", "fl-bg-white", "fl-txt-hov-white"],
        blood: ["fl-bd-blood", "fl-bg-blood", "fl-txt-white", "fl-bg-hov-anis", "fl-txt-hov-prune"],
        apricot: ["fl-bd-apricot", "fl-txt-apricot", "fl-bg-hov-apricot", "fl-bg-white", "fl-txt-hov-white"],
        electric: ["fl-bd-electric", "fl-txt-electric", "fl-bg-hov-electric", "fl-bg-white", "fl-txt-hov-white"],
        prune: ["fl-bd-prune", "fl-txt-prune", "fl-bg-hov-sadsea", "fl-bg-white", "fl-txt-hov-white"]
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
                if (!canceled) {
                    clickData[bib] = timestamp;
                    cancelTime[bib] = 0;
                } else {
                    cancelTime[bib] = timestamp;
                }
                updateButtonColor(bib, button);
            });

            document.querySelectorAll("button[id^='bib-']").forEach(button => {
                const bib = button.id.replace("bib-", "");
                if (!clickData[bib] && !cancelTime[bib] && !stopState[bib]) {
                    updateButtonColor(bib, button);
                }
            });
        });
});