function genererGrille(joueur_id, termine) {
    const grille = document.getElementById("grille");
    grille.innerHTML = "";

    const taille = 10;

    for (let x = 0; x < taille; x++) {
        for (let y = 0; y < taille; y++) {

            let cell = document.createElement("div");
            cell.classList.add("cell");

            if (!termine) {
                cell.addEventListener("click", () => placerCase(x, y, cell));
            }

            // Charger l'état depuis SQL
            fetch("../actions/get_grid.php?joueur=" + joueur_id)
                .then(r => r.json())
                .then(data => {
                    data.forEach(c => {
                        if (c.x === x && c.y === y) {
                            cell.classList.add("placed");
                        }
                    });
                });

            grille.appendChild(cell);
        }
    }
}


function placerCase(x, y, cell) {

    // Trouver le bateau en cours automatiquement depuis le serveur plus tard
    // Pour l'instant on laisse un placeholder :
    let ship = prompt("ID du bateau à placer (2 à 5) :");

    fetch("../actions/place.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "x=" + x + "&y=" + y + "&ship=" + ship
    })
        .then(r => r.json())
        .then(rep => {
            if (rep.success) {
                cell.classList.add("placed");
            } else {
                alert("Erreur : " + rep.error);
            }
        });
}
