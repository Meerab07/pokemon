<?php
// pokemon_api/teams.php

// Allow cross-origin requests (for development only; restrict in production)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// DB connection (XAMPP default: root / empty password)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "pokemon_db";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit();
}

// GET => return all teams
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM teams ORDER BY created_at DESC");
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }
    echo json_encode($teams);
    $conn->close();
    exit();
}

// POST => save a new team
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['team_name']) || !isset($data['pokemon']) || !is_array($data['pokemon'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid payload. Expect { team_name, pokemon: [..6 names..] }"]);
        $conn->close();
        exit();
    }

    // Normalize to 6 items
    $p = $data['pokemon'];
    for ($i = 0; $i < 6; $i++) {
        $p[$i] = isset($p[$i]) ? $p[$i] : "";
    }

    $stmt = $conn->prepare("INSERT INTO teams (team_name, pokemon1, pokemon2, pokemon3, pokemon4, pokemon5, pokemon6) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        $conn->close();
        exit();
    }

    $stmt->bind_param("sssssss", $data['team_name'], $p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Team saved", "id" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Insert failed: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// Unsupported method
http_response_code(405);
echo json_encode(["error" => "Method not allowed"]);
?>
