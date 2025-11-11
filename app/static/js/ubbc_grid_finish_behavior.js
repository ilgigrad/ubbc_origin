
const finishData = {};
const cancelFinish = {};

function clearFinishClasses(button) {
    const toRemove = Array.from(button.classList).filter(cls =>
        cls.startsWith("fl-bd-") ||
        cls.startsWith("fl-txt-") ||
        cls.startsWith("fl-bg-") ||
        cls.startsWith("fl-bg-hov-") ||
        cls.startsWith("fl-txt-hov-") ||
        cls.startsWith("fl-bd-hov-")
    );
    toRemove.forEach(cls => button.classList.remove(cls));
}

function applyFinishColor(button, state) {
    clearFinishClasses(button);
    if (state === "finished") {
        button.classList.add("fl-bd-blood", "fl-txt-white", "fl-bg-blood", "fl-bg-hov-apricot");
    } else {
        button.classList.add("fl-bd-blood", "fl-txt-blood", "fl-bg-white", "fl-txt-hov-white", "fl-bg-hov-peach");
    }
}

function toggleFinish(bib) {
    const button = document.getElementById(`bib-${bib}`);
    fetch(`toggle_finish.php?bib=${bib}`)
        .then(res => res.json())
        .then(data => {
            if (data.action === "added") {
                finishData[bib] = true;
                cancelFinish[bib] = false;
            } else if (data.action === "removed") {
                finishData[bib] = false;
            }
            applyFinishColor(button, finishData[bib] ? "finished" : "default");

            document.getElementById("runner-name").innerHTML = `
                <h1 class="lead text-center fl-txt-blood fw-bold pt-2">${data.bib} • ${data.firstname.toUpperCase()} • ${data.race.toUpperCase()}</h1>
            `;
        });
}

document.addEventListener("DOMContentLoaded", () => {
    fetch("get_last_finish.php")
        .then(res => res.json())
        .then(data => {
            data.forEach(({ bib, is_canceled }) => {
                const button = document.getElementById(`bib-${bib}`);
                if (!button) return;
                if (!is_canceled) finishData[bib] = true;
                applyFinishColor(button, finishData[bib] ? "finished" : "default");
            });

            document.querySelectorAll("button[id^='bib-']").forEach(button => {
                const bib = button.id.replace("bib-", "");
                applyFinishColor(button, finishData[bib] ? "finished" : "default");
            });
        });
});
