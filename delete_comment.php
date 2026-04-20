<?php
require 'config.php';
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
$comment_id = $_GET['id'] ?? null;
$item_id    = $_GET['item'] ?? null;

if (!$comment_id || !is_numeric($comment_id)) {
    die("Invalid comment ID");
}

if (!$item_id || !is_numeric($item_id)) {
    die("Invalid item ID");
}

/*
|--------------------------------------------------------------------------
| DELETE COMMENT
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    DELETE FROM reviews.comments
    WHERE id = :id
");

$stmt->execute([
    ':id' => $comment_id
]);

/*
|--------------------------------------------------------------------------
| OPTIONAL: recalculate item rating
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    UPDATE reviews.items
    SET rating = (
        SELECT AVG(rating)
        FROM reviews.comments
        WHERE review_item = :item
    )
    WHERE id = :item
");

$stmt->execute([
    ':item' => $item_id
]);

/*
|--------------------------------------------------------------------------
| REDIRECT BACK
|--------------------------------------------------------------------------
*/
header("Location: item.php?id=" . $item_id);
exit;
