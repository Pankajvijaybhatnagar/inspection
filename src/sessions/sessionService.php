<?php
require_once __DIR__ . '/../config.php';

$config = new Config();
$db = $config->getDB();

function getUserSessionsPaginated($userId, $page = 1, $limit = 10) {
    global $db;

    $offset = ($page - 1) * $limit;

    // Get session records
    $stmt = $db->prepare("
        SELECT id, ip_address, user_agent, device_info, expires_at, created_at
        FROM sessions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $total = (int) $countStmt->fetchColumn();

    return [
        'sessions' => $sessions,
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ];
}
