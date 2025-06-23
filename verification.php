<?php
header("Content-Type: application/json");

try {
    $pdo = new PDO(
        "pgsql:host=dpg-d1clqcadbo4c73d06t7g-a;port=5432;dbname=authentification_db_o2gl",
        "authentification_db_o2gl_user",
        "74brY4XTBJCY4W9EK20lYjRPnOPRNbPV"
    );
    
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 10");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>