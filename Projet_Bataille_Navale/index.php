<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'battleship';
$username = 'root';
$password = '';

// CHANGEZ CES VALEURS SI NÉCESSAIRE :
// Par exemple : $username = 'votre_user'; $password = 'votre_password';

// Connexion à la base de données
try {
    // Tentative avec socket Unix pour éviter les problèmes d'auth
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ];
    
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password, $options);
    
    // Créer la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Créer les tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS game_board (
        id INT AUTO_INCREMENT PRIMARY KEY,
        row_pos INT NOT NULL,
        col_pos INT NOT NULL,
        bateau_id INT NOT NULL,
        is_hit BOOLEAN DEFAULT FALSE,
        game_id VARCHAR(50) NOT NULL,
        INDEX(game_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_name VARCHAR(100) NOT NULL,
        victories INT DEFAULT 0,
        games_played INT DEFAULT 0,
        UNIQUE KEY(player_name)
    )");
    
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage() . "<br>Vérifiez vos paramètres de connexion MySQL.");
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
    $gameId = uniqid('game_', true);
    $_SESSION['game_id'] = $gameId;
    $_SESSION['player_name'] = !empty($_POST['player_name']) ? $_POST['player_name'] : 'Joueur';
    
    // Supprimer l'ancienne partie si elle existe
    if (isset($_SESSION['old_game_id'])) {
        $stmt = $pdo->prepare("DELETE FROM game_board WHERE game_id = ?");
        $stmt->execute([$_SESSION['old_game_id']]);
    }
    
    // Initialiser la grille dans la base de données
    $stmt = $pdo->prepare("INSERT INTO game_board (row_pos, col_pos, bateau_id, game_id) VALUES (?, ?, ?, ?)");
    for ($i = 0; $i < count($grid); $i++) {
        for ($j = 0; $j < count($grid[$i]); $j++) {
            if ($grid[$i][$j] > 0) {
                $stmt->execute([$i, $j, $grid[$i][$j], $gameId]);
            }
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// API pour les clics
if (isset($_POST['action']) && $_POST['action'] === 'check_cell') {
    header('Content-Type: application/json');
    
    $row = intval($_POST['row']);
    $col = intval($_POST['col']);
    $gameId = isset($_SESSION['game_id']) ? $_SESSION['game_id'] : '';
    
    if (empty($gameId)) {
        echo json_encode(['error' => 'No active game']);
        exit;
    }
    
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
            $playerName = isset($_SESSION['player_name']) ? $_SESSION['player_name'] : 'Joueur';
            $scoreStmt = $pdo->prepare("SELECT * FROM scores WHERE player_name = ?");
            $scoreStmt->execute([$playerName]);
            $player = $scoreStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($player) {
                $updateScore = $pdo->prepare("UPDATE scores SET victories = victories + 1, games_played = games_played + 1 WHERE id = ?");
                $updateScore->execute([$player['id']]);
            } else {
                $insertScore = $pdo->prepare("INSERT INTO scores (player_name, victories, games_played) VALUES (?, 1, 1)");
                $insertScore->execute([$playerName]);
            }
            
            $_SESSION['old_game_id'] = $gameId;
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
try {
    $scoresStmt = $pdo->query("SELECT * FROM scores ORDER BY victories DESC LIMIT 10");
    $scores = $scoresStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $scores = [];
}
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
            color: #fff;
        }
        
        .subtitle {
            text-align: center;
            font-size: 1.1em;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .game-area {
            display: flex;
            gap: 30px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .board-container {
            background: rgba(255,255,255,0.08);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .board-container h2 {
            margin-bottom: 15px;
            color: #4fc3f7;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 40px repeat(10, 45px);
            grid-template-rows: 40px repeat(10, 45px);
            gap: 3px;
            margin-top: 20px;
            background: rgba(0,0,0,0.2);
            padding: 5px;
            border-radius: 10px;
        }
        
        .label {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #4fc3f7;
            font-size: 14px;
        }
        
        .cell {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.2) 0%, rgba(79, 195, 247, 0.1) 100%);
            border: 2px solid rgba(79, 195, 247, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 5px;
            position: relative;
        }
        
        .cell:hover:not(.hit):not(.miss) {
            background: linear-gradient(135deg, rgba(79, 195, 247, 0.4) 0%, rgba(79, 195, 247, 0.3) 100%);
            transform: scale(1.08);
            border-color: rgba(79, 195, 247, 0.6);
            box-shadow: 0 0 15px rgba(79, 195, 247, 0.4);
        }
        
        .cell.hit {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            cursor: not-allowed;
            animation: explosion 0.5s ease;
            border-color: #c0392b;
        }
        
        .cell.miss {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            cursor: not-allowed;
            border-color: #2980b9;
        }
        
        .cell.sunk {
            background: linear-gradient(135deg, #8b0000 0%, #5a0000 100%);
        }
        
        @keyframes explosion {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        
        .info-panel {
            background: rgba(255,255,255,0.08);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            min-width: 320px;
            max-width: 400px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        
        .info-panel h2, .info-panel h3 {
            color: #4fc3f7;
            margin-bottom: 15px;
        }
        
        .ships-status {
            margin-top: 20px;
        }
        
        .ship-item {
            background: rgba(79, 195, 247, 0.1);
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(79, 195, 247, 0.2);
            transition: all 0.3s ease;
        }
        
        .ship-item.sunk {
            background: rgba(231, 76, 60, 0.2);
            text-decoration: line-through;
            border-color: rgba(231, 76, 60, 0.4);
            opacity: 0.7;
        }
        
        .victory-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 60px;
            border-radius: 20px;
            text-align: center;
            font-size: 2.5em;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            z-index: 1000;
            display: none;
            animation: bounceIn 0.6s ease;
            border: 3px solid white;
        }
        
        @keyframes bounceIn {
            0% { transform: translate(-50%, -50%) scale(0.3); opacity: 0; }
            50% { transform: translate(-50%, -50%) scale(1.05); }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }
        
        .new-game-form {
            background: rgba(255,255,255,0.08);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            max-width: 500px;
            margin: 30px auto;
        }
        
        .new-game-form h2 {
            color: #4fc3f7;
            margin-bottom: 20px;
        }
        
        input[type="text"] {
            padding: 14px 20px;
            font-size: 16px;
            border: 2px solid rgba(79, 195, 247, 0.5);
            border-radius: 8px;
            margin: 10px;
            background: rgba(255,255,255,0.9);
            color: #333;
            min-width: 250px;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #4fc3f7;
            box-shadow: 0 0 10px rgba(79, 195, 247, 0.3);
        }
        
        button {
            padding: 14px 30px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            margin: 10px 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
        }
        
        button[type="submit"] {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }
        
        button[type="submit"]:hover {
            background: linear-gradient(135deg, #229954 0%, #1e8449 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4);
        }
        
        .scores-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        
        .scores-table th, .scores-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(79, 195, 247, 0.2);
        }
        
        .scores-table th {
            background: rgba(79, 195, 247, 0.15);
            color: #4fc3f7;
            font-weight: bold;
        }
        
        .scores-table tr:hover {
            background: rgba(79, 195, 247, 0.05);
        }
        
        .player-info {
            background: rgba(79, 195, 247, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid rgba(79, 195, 247, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚓ Battle Ships Crews ⚓</h1>
        <p class="subtitle">Coulez tous les navires ennemis pour remporter la victoire !</p>
        
        <?php if (!isset($_SESSION['game_id'])): ?>
        <div class="new-game-form">
            <h2>🎮 Nouvelle Partie</h2>
            <form method="POST" action="">
                <div>
                    <input type="text" name="player_name" placeholder="Entrez votre nom" required maxlength="50">
                </div>
                <button type="submit" name="new_game">⚔️ Commencer la Bataille</button>
            </form>
        </div>
        
        <?php if (!empty($scores)): ?>
        <div class="info-panel" style="max-width: 600px; margin: 30px auto;">
            <h3>🏆 Tableau des Scores</h3>
            <table class="scores-table">
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Joueur</th>
                        <th>Victoires</th>
                        <th>Parties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($scores as $index => $score): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($score['player_name']) ?></td>
                        <td><?= $score['victories'] ?></td>
                        <td><?= $score['games_played'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="game-area">
            <div class="board-container">
                <h2>🎯 Grille de Combat</h2>
                <div class="player-info">
                    <strong>Commandant:</strong> <?= htmlspecialchars($_SESSION['player_name']) ?>
                </div>
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
                        <span id="ship-2">0/2</span>
                    </div>
                    <div class="ship-item" data-ship="3">
                        <span>⛴️ Croiseur</span>
                        <span id="ship-3">0/6</span>
                    </div>
                    <div class="ship-item" data-ship="4">
                        <span>🛳️ Cuirassé</span>
                        <span id="ship-4">0/4</span>
                    </div>
                    <div class="ship-item" data-ship="5">
                        <span>🚢 Porte-avions</span>
                        <span id="ship-5">0/5</span>
                    </div>
                </div>
                
                <form method="POST" action="" style="margin-top: 25px;">
                    <button type="submit" name="new_game">🔄 Nouvelle Partie</button>
                    <input type="hidden" name="player_name" value="<?= htmlspecialchars($_SESSION['player_name']) ?>">
                </form>
                
                <?php if (!empty($scores)): ?>
                <h3 style="margin-top: 30px;">🏆 Top Scores</h3>
                <table class="scores-table">
                    <thead>
                        <tr>
                            <th>Joueur</th>
                            <th>Victoires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(array_slice($scores, 0, 5) as $score): ?>
                        <tr>
                            <td><?= htmlspecialchars($score['player_name']) ?></td>
                            <td><?= $score['victories'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="victory-message" id="victoryMessage">
            🎉 VICTOIRE ! 🎉<br>
            <span style="font-size: 0.5em; margin-top: 20px; display: block;">Tous les navires ont été coulés !</span>
        </div>
    </div>

    <?php if (isset($_SESSION['game_id'])): ?>
    <script>
        const shipHits = {2: 0, 3: 0, 4: 0, 5: 0};
        const shipSizes = {2: 0, 3: 0, 4: 0, 5: 0};
        const shipNames = {2: 'Sous-marin', 3: 'Croiseur', 4: 'Cuirassé', 5: 'Porte-avions'};
        let totalShips = 0;
        let totalHits = 0;
        
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
                
                try {
                    const response = await fetch('', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.error) {
                        alert('Erreur: ' + result.error);
                        return;
                    }
                    
                    if (result.hit) {
                        this.classList.add('hit');
                        shipHits[result.bateau_id]++;
                        
                        if (shipSizes[result.bateau_id] === 0) {
                            totalShips++;
                        }
                        shipSizes[result.bateau_id]++;
                        totalHits++;
                        
                        const shipEl = document.getElementById(`ship-${result.bateau_id}`);
                        shipEl.textContent = `${shipHits[result.bateau_id]}/${shipSizes[result.bateau_id]}`;
                        
                        if (result.sunk) {
                            const shipItem = document.querySelector(`[data-ship="${result.bateau_id}"]`);
                            shipItem.classList.add('sunk');
                            
                            setTimeout(() => {
                                alert(`🔥 ${shipNames[result.bateau_id]} coulé !`);
                            }, 100);
                        }
                        
                        if (result.victory) {
                            setTimeout(() => {
                                document.getElementById('victoryMessage').style.display = 'block';
                                setTimeout(() => {
                                    if (confirm('Félicitations ! Voulez-vous rejouer ?')) {
                                        location.reload();
                                    }
                                }, 2000);
                            }, 500);
                        }
                    } else {
                        this.classList.add('miss');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue. Veuillez réessayer.');
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>