<?php
require 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_client':
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch());
        break;
        
    case 'get_client_address':
        $stmt = $pdo->prepare("SELECT address FROM clients WHERE client_id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch());
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>