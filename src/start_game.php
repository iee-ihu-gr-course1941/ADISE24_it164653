<?php
header('Content-Type: application/json');

// Σύνδεση με τη βάση δεδομένων
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cant_stop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Επαλήθευση μεθόδου POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method. Use POST."]);
    exit;
}

// Ανάγνωση δεδομένων από το POST
$player_ids = isset($_POST['player_ids']) ? $_POST['player_ids'] : [];
if (count($player_ids) !== 2) {
    echo json_encode(["error" => "Exactly two players are required."]);
    exit;
}

// Έλεγχος για υπάρχον παιχνίδι σε κατάσταση "waiting"
$sql = "SELECT * FROM game_sessions WHERE status = 'waiting' LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Ενημέρωση κατάστασης του παιχνιδιού σε "active"
    $row = $result->fetch_assoc();
    $game_id = $row['id'];

    $update_sql = "UPDATE game_sessions SET status = 'active' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $game_id);

    if ($stmt->execute()) {
        echo json_encode([
            "game_id" => $game_id,
            "message" => "Game started successfully."
        ]);
    } else {
        echo json_encode(["error" => "Failed to start the game: " . $stmt->error]);
    }

    // Ενημέρωση των παικτών με το game_id
    $update_players_sql = "UPDATE players SET game_id = ? WHERE id = ?";
    $stmt = $conn->prepare($update_players_sql);
    foreach ($player_ids as $player_id) {
        $stmt->bind_param("ii", $game_id, $player_id);
        $stmt->execute();
    }

    $stmt->close();
} else {
    // Εισαγωγή παικτών πριν τη δημιουργία παιχνιδιού
    $insert_players_sql = "INSERT IGNORE INTO players (id) VALUES (?)";
    $stmt = $conn->prepare($insert_players_sql);

    foreach ($player_ids as $player_id) {
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
    }

    // Δημιουργία νέου παιχνιδιού
    $insert_game_sql = "INSERT INTO game_sessions (status, active_player_id) VALUES ('waiting', ?)";
    $stmt = $conn->prepare($insert_game_sql);
    $stmt->bind_param("i", $player_ids[0]); // Ο πρώτος παίκτης είναι ο ενεργός

    if ($stmt->execute()) {
        $game_id = $conn->insert_id;

        // Ενημέρωση των παικτών με το νέο game_id
        $update_players_sql = "UPDATE players SET game_id = ? WHERE id IN (?, ?)";
        $stmt = $conn->prepare($update_players_sql);
        $stmt->bind_param("iii", $game_id, $player_ids[0], $player_ids[1]);
        $stmt->execute();

        echo json_encode([
            "game_id" => $game_id,
            "message" => "Game created successfully."
        ]);
    } else {
        echo json_encode(["error" => "Failed to create game: " . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>
