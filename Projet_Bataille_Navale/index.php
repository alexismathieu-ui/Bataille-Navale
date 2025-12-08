<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'battleship';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Initialisation de la base de données si nécessaire
if (isset($_GET['init_db'])) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS game_board (
        id INT AUTO_INCREMENT PRIMARY KEY,
        row_pos INT NOT NULL,
        col_pos INT NOT NULL,
        bateau_id INT NOT NULL,
        is_hit BOOLEAN DEFAULT FALSE,
        game_id VARCHAR(50) NOT NULL
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_name VARCHAR(100) NOT NULL,
        victories INT DEFAULT 0,
        games_played INT DEFAULT 0
    )");
    
    echo "<script>alert('Base de données initialisée !'); window.location.href='?';</script>";
    exit;
}

// Grille de jeu par défaut
$grid = [
    [3, 0, 0, 0, 0, 0, 0, 2, 2, 0],
    [3, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [3, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 2, 2, 0, 0, 0],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 4],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 4],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 4],
    [3, 3, 3, 0, 0, 5, 0, 0, 0, 4],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
];

// Nouvelle partie
if (isset($_POST['new_game'])) {
    $gameId = uniqid();
    $_SESSION['game_id'] = $gameId;
    $_SESSION['player_name'] = $_POST['player_name'] ?? 'Joueur';
    
    // Supprimer l'ancienne partie
    $pdo->exec("DELETE FROM game_board WHERE game_id = '{$_SESSION['game_id']}'");
    
    // Initialiser la grille dans la base de données
    $stmt = $pdo->prepare("INSERT INTO game_board (row_pos, col_pos, bateau_id, game_id) VALUES (?, ?, ?, ?)");
    for ($i = 0; $i < count($grid); $i++) {
        for ($j = 0; $j < count($grid[$i]); $j++) {
            if ($grid[$i][$j] > 0) {
                $stmt->execute([$i, $j, $grid[$i][$j], $gameId]);
            }
        }
    }
    
    header("Location: ?");
    exit;
}

// API pour les clics
if (isset($_POST['action']) && $_POST['action'] === 'check_cell') {
    header('Content-Type: application/json');
    
    $row = intval($_POST['row']);
    $col = intval($_POST['col']);
    $gameId = $_SESSION['game_id'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM game_board WHERE row_pos = ? AND col_pos = ? AND game_id = ?");
    $stmt->execute([$row, $col, $gameId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !$result['is_hit']) {
        // Marquer comme touché
        $updateStmt = $pdo->prepare("UPDATE game_board SET is_hit = TRUE WHERE id = ?");
        $updateStmt->execute([$result['id']]);
        
        // Vérifier si le bateau est coulé
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_hit) as hits FROM game_board WHERE bateau_id = ? AND game_id = ?");
        $checkStmt->execute([$result['bateau_id'], $gameId]);
        $shipStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        $sunk = ($shipStatus['total'] == $shipStatus['hits']);
        
        // Vérifier la victoire
        $allShipsStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_hit) as hits FROM game_board WHERE game_id = ?");
        $allShipsStmt->execute([$gameId]);
        $allShips = $allShipsStmt->fetch(PDO::FETCH_ASSOC);
        
        $victory = ($allShips['total'] == $allShips['hits']);
        
        if ($victory) {
            // Mettre à jour les scores
            $playerName = $_SESSION['player_name'] ?? 'Joueur';
            $scoreStmt = $pdo->prepare("SELECT * FROM scores WHERE player_name = ?");
            $scoreStmt->execute([$playerName]);
            $player = $scoreStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($player) {
                $pdo->prepare("UPDATE scores SET victories = victories + 1, games_played = games_played + 1 WHERE id = ?")
                    ->execute([$player['id']]);
            } else {
                $pdo->prepare("INSERT INTO scores (player_name, victories, games_played) VALUES (?, 1, 1)")
                    ->execute([$playerName]);
            }
        }
        
        echo json_encode([
            'hit' => true,
            'bateau_id' => $result['bateau_id'],
            'sunk' => $sunk,
            'victory' => $victory
        ]);
    } else {
        echo json_encode(['hit' => false]);
    }
    exit;
}

// Récupérer les scores
$scoresStmt = $pdo->query("SELECT * FROM scores ORDER BY victories DESC LIMIT 10");
$scores = $scoresStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle Ships Crews</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            text-align: center;
            font-size: 1.2em;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .game-area {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .board-container {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .grid {
            display: grid;
            grid-template-columns: 40px repeat(10, 50px);
            grid-template-rows: 40px repeat(10, 50px);
            gap: 2px;
            margin-top: 20px;
        }
        
        .label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
            font-size: 14px;
        }
        
        .cell {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .cell:hover:not(.hit):not(.miss) {
            background: rgba(255,255,255,0.4);
            transform: scale(1.05);
        }
        
        .cell.hit {
            background: #e74c3c;
            cursor: not-allowed;
            animation: explosion 0.5s ease;
        }
        
        .cell.miss {
            background: #3498db;
            cursor: not-allowed;
        }
        
        .cell.sunk {
            background: #c0392b;
        }
        
        @keyframes explosion {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .info-panel {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            min-width: 300px;
        }
        
        .ships-status {
            margin-top: 20px;
        }
        
        .ship-item {
            background: rgba(255,255,255,0.15);
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .ship-item.sunk {
            background: rgba(231, 76, 60, 0.3);
            text-decoration: line-through;
        }
        
        .victory-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 50px;
            border-radius: 20px;
            text-align: center;
            font-size: 2em;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
            animation: bounce 0.5s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.1); }
        }
        
        .new-game-form {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        input, button {
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            margin: 5px;
        }
        
        button {
            background: #27ae60;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .scores-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        
        .scores-table th, .scores-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .scores-table th {
            background: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚓ Battle Ships Crews ⚓</h1>
        <p class="subtitle">Coulez tous les navires ennemis pour remporter la victoire !</p>
        
        <?php if (!isset($_SESSION['game_id'])): ?>
        <div class="new-game-form">
            <h2>Nouvelle Partie</h2>
            <form method="POST">
                <input type="text" name="player_name" placeholder="Votre nom" required>
                <button type="submit" name="new_game">Commencer</button>
            </form>
            <br>
            <a href="?init_db" style="color: #fff; text-decoration: underline;">Initialiser la base de données</a>
        </div>
        <?php else: ?>
        <div class="game-area">
            <div class="board-container">
                <h2>Grille de Combat</h2>
                <p>Joueur: <strong><?= htmlspecialchars($_SESSION['player_name']) ?></strong></p>
                <div class="grid" id="gameGrid">
                    <div class="label"></div>
                    <?php for($i = 0; $i < 10; $i++): ?>
                        <div class="label"><?= $i ?></div>
                    <?php endfor; ?>
                    
                    <?php 
                    $letters = ['A','B','C','D','E','F','G','H','I','J'];
                    for($i = 0; $i < 10; $i++): 
                    ?>
                        <div class="label"><?= $letters[$i] ?></div>
                        <?php for($j = 0; $j < 10; $j++): ?>
                            <div class="cell" data-row="<?= $i ?>" data-col="<?= $j ?>"></div>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="info-panel">
                <h2>🎯 Flotte Ennemie</h2>
                <div class="ships-status" id="shipsStatus">
                    <div class="ship-item" data-ship="2">
                        <span>🚤 Sous-marin</span>
                        <span id="ship-2">0/3</span>
                    </div>
                    <div class="ship-item" data-ship="3">
                        <span>⛴️ Croiseur</span>
                        <span id="ship-3">0/4</span>
                    </div>
                    <div class="ship-item" data-ship="4">
                        <span>🛳️ Cuirassé</span>
                        <span id="ship-4">0/5</span>
                    </div>
                    <div class="ship-item" data-ship="5">
                        <span>🚢 Porte-avions</span>
                        <span id="ship-5">0/5</span>
                    </div>
                </div>
                
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" name="new_game">Nouvelle Partie</button>
                    <input type="hidden" name="player_name" value="<?= htmlspecialchars($_SESSION['player_name']) ?>">
                </form>
                
                <h3 style="margin-top: 30px;">🏆 Scores</h3>
                <table class="scores-table">
                    <tr>
                        <th>Joueur</th>
                        <th>Victoires</th>
                    </tr>
                    <?php foreach($scores as $score): ?>
                    <tr>
                        <td><?= htmlspecialchars($score['player_name']) ?></td>
                        <td><?= $score['victories'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="victory-message" id="victoryMessage">
            🎉 VICTOIRE ! 🎉<br>
            <span style="font-size: 0.6em;">Tous les navires ont été coulés !</span>
        </div>
    </div>

    <script>
        const shipHits = {2: 0, 3: 0, 4: 0, 5: 0};
        const shipSizes = {2: 3, 3: 4, 4: 5, 5: 5};
        const shipNames = {2: 'Sous-marin', 3: 'Croiseur', 4: 'Cuirassé', 5: 'Porte-avions'};
        
        document.querySelectorAll('.cell').forEach(cell => {
            cell.addEventListener('click', async function() {
                if (this.classList.contains('hit') || this.classList.contains('miss')) {
                    return;
                }
                
                const row = this.dataset.row;
                const col = this.dataset.col;
                
                const formData = new FormData();
                formData.append('action', 'check_cell');
                formData.append('row', row);
                formData.append('col', col);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.hit) {
                    this.classList.add('hit');
                    shipHits[result.bateau_id]++;
                    
                    const shipEl = document.getElementById(`ship-${result.bateau_id}`);
                    shipEl.textContent = `${shipHits[result.bateau_id]}/${shipSizes[result.bateau_id]}`;
                    
                    if (result.sunk) {
                        document.querySelector(`[data-ship="${result.bateau_id}"]`).classList.add('sunk');
                        alert(`🔥 ${shipNames[result.bateau_id]} coulé !`);
                        
                        document.querySelectorAll('.cell.hit').forEach(c => {
                            if (!c.classList.contains('sunk')) {
                                // Marquer les cellules du bateau coulé
                            }
                        });
                    }
                    
                    if (result.victory) {
                        document.getElementById('victoryMessage').style.display = 'block';
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                } else {
                    this.classList.add('miss');
                }
            });
        });
    </script>
</body>
</html>