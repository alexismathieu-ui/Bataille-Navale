// Construction de la grille HTML
const grille = document.getElementById("grille");

for (let row = 0; row < 10; row++) {
    for (let col = 0; col < 10; col++) {
        let cell = document.createElement("div");
        cell.classList.add("case");
        cell.dataset.row = row;
        cell.dataset.col = col;

        cell.addEventListener("click", () => tirer(row, col));

        grille.appendChild(cell);
    }
}

// Tir
function tirer(r, c) {
    fetch("api/tir.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ row: r, col: c })
    })
    .then(res => res.json())
    .then(updateGrille);
}

// Mise à jour de la grille
function updateGrille(data) {
    data.forEach(caseInfo => {
        let selector = `.case[data-row="${caseInfo.row}"][data-col="${caseInfo.col}"]`;
        let cell = document.querySelector(selector);

        if (caseInfo.touched == 1) {
            cell.classList.add("touche");
        } else if (caseInfo.touched == -1) {
            cell.classList.add("rate");
        }
    });
}

// Refresh automatique toutes les 500ms
setInterval(() => {
    fetch("api/etat.php")
        .then(res => res.json())
        .then(updateGrille);
}, 500);
