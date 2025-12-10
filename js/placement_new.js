console.log(">>> JS PLACEMENT + PREVIEW CHARGÉ <<<");

let selectedShip = null;
let shiporientation = "H";
let shipSize = 0;
let shipQty = 0;


// Sélection d’un bateau
document.querySelectorAll(".bateau-select").forEach(btn => {
    btn.addEventListener("click", () => {
        selectedShip = btn.dataset.id;
        shipSize = parseInt(btn.dataset.taille);
        shipQty = parseInt(btn.dataset.qty);

        document.querySelectorAll(".bateau-select").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
    });
});

// --- ROTATION AVEC 'R' ---
document.addEventListener("keydown", (e) => {
     console.log("KEY PRESSED :", e.key);
    if (e.key === "r" || e.key === "R") {
        shiporientation = (shiporientation === "H") ? "V" : "H";
        console.log("Orientation :", shiporientation);
    }
});



// === CONSTRUCTION DE LA GRILLE ===
const grid = document.getElementById("grid");
let cells = [];

for (let r = 1; r <= 10; r++) {
    for (let c = 1; c <= 10; c++) {

        let cell = document.createElement("div");
        cell.classList.add("cell");

        // Sauvegarde pour accès facile
        cells.push(cell);

        // HOVER PREVIEW
        cell.addEventListener("mouseenter", () => {
            previewShip(r, c);
        });

        cell.addEventListener("mouseleave", () => {
            clearPreview();
        });

        // CLICK = placement
        cell.addEventListener("click", () => {
            if (!selectedShip) {
                alert("Choisissez un bateau !");
                return;
            }

            if (shipQty <= 0) {
                alert("Tu as déjà placé tous les bateaux de ce type !");
                return;
            }


            fetch("../game/placement_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `r=${r}&c=${c}&bateau=${selectedShip}&orientation=${shiporientation}`
            })
            .then(res => res.text())
            .then(data => {
                console.log("Réponse brute :", data);

                try {
                    let json = JSON.parse(data);

                    if (json.success) {

                        let r = json.cell[0];
                        let c = json.cell[1];
                        let taille = json.ship;

                        // Colorer le bateau placé
                        for (let i = 0; i < taille; i++) {
                            let rr = (shiporientation === "H") ? r : r + i;
                            let cc = (shiporientation === "H") ? c + i : c;

                            let index = (rr - 1) * 10 + (cc - 1);
                            let caseHTML = cells[index];
                            caseHTML.classList.add("placed");
                        }
                        
                        shipQty--;

                        if (shipQty <= 0) {
                            document.querySelector(`[data-id="${selectedShip}"]`).classList.add("disabled");
                        }


                        clearPreview();

                    } else {
                        alert(json.error);
                    }

                } catch (e) {
                    alert("Erreur serveur : " + data);
                }
            });
        });

        grid.appendChild(cell);
    }
}


// === PREVIEW FUNCTIONS ===

// Enlever les anciennes previews
function clearPreview() {
    cells.forEach(c => c.classList.remove("preview-ok", "preview-bad"));
}

// Appliquer la prévisualisation
function previewShip(r, c) {
    clearPreview();

    if (!selectedShip) return;

    let taille = shipSize;

    let ok = true;

    let previews = [];

    for (let i = 0; i < taille; i++) {
        let rr = (shiporientation === "H") ? r : r + i;
        let cc = (shiporientation === "H") ? c + i : c;

        // Hors limite
        if (rr < 1 || rr > 10 || cc < 1 || cc > 10) {
            ok = false;
            continue;
        }

        let index = (rr - 1) * 10 + (cc - 1);
        if (cells[index].classList.contains("placed")) {
            ok = false;
        }

        previews.push(cells[index]);
    }

    // Coloration selon ok / bad
    previews.forEach(cell => {
        cell.classList.add(ok ? "preview-ok" : "preview-bad");
    });
}
