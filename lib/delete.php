<?php
require '../config.php';
session_start();

/*
|--------------------------------------------------------------------------
| AUTH CHECK
|--------------------------------------------------------------------------
*/
$isAdmin = $_SESSION['is_admin'] ?? false;

if (!$isAdmin) {
    http_response_code(403);
    die("Forbidden");
}

/*
|--------------------------------------------------------------------------
| INPUT VALIDATION
|--------------------------------------------------------------------------
*/
$item_id = $_GET['id'] ?? null;

if (!$item_id || !is_numeric($item_id)) {
    die("Invalid item ID");
}

/*
|--------------------------------------------------------------------------
| DELETE RELATED COMMENTS FIRST (safe fallback)
|--------------------------------------------------------------------------
| Only needed if you do NOT have ON DELETE CASCADE in DB
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    DELETE FROM reviews.comments
    WHERE review_item = :id
");
$stmt->execute([':id' => $item_id]);

/*
|--------------------------------------------------------------------------
| DELETE ITEM
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    DELETE FROM reviews.items
    WHERE id = :id
");

$stmt->execute([':id' => $item_id]);

/*
|--------------------------------------------------------------------------
| REDIRECT
|--------------------------------------------------------------------------
*/
header("Location: ../index.php");
exit;
