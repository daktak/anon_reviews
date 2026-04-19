<?php
require 'config.php';

if (!($_SESSION['is_admin'] ?? false)) {
    http_response_code(403);
        die("Forbidden");
	}

	$id = $_GET['id'] ?? null;

	if (!$id || !is_numeric($id)) {
	    die("Invalid ID");
	    }

	    /*
	     * |--------------------------------------------------------------------------
	     * | Delete comments first (FK safety)
	     * |--------------------------------------------------------------------------
	     * */
	    $stmt = $pdo->prepare("DELETE FROM reviews.comments WHERE review_item = :id");
	    $stmt->execute([':id' => $id]);

	    /*
	     * |--------------------------------------------------------------------------
	     * | Delete item
	     * |--------------------------------------------------------------------------
	     * */
	    $stmt = $pdo->prepare("DELETE FROM reviews.items WHERE id = :id");
	    $stmt->execute([':id' => $id]);

	    header("Location: index.php");
	    exit;
