<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $link = $_POST['link'] ?? '';
    $review = $_POST['review'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $tags = $_POST['tags'] ?? '';

    if (!$title) {
        $error = "Title is required.";
    } elseif ($rating !== '' && (!is_numeric($rating) || $rating < 1 || $rating > 5)) {
        $error = "Rating must be between 1 and 5.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews.items (title, link, review, rating, tags)
                VALUES (:title, :link, :review, :rating, :tags)
            ");

            $stmt->execute([
                ':title' => $title,
                ':link' => $link ?: null,
                ':review' => $review ?: null,
                ':rating' => $rating !== '' ? (float)$rating : null,
                ':tags' => $tags ?: null
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Add Review</h1>
        <a href="index.php" class="btn btn-outline-secondary">← Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Link</label>
                    <input type="url" name="link" class="form-control" placeholder="https://...">
                </div>

                <div class="mb-3">
                    <label class="form-label">Review</label>
                    <textarea name="review" class="form-control" rows="5"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rating (1–5)</label>
                    <input type="number" name="rating" class="form-control" min="1" max="5">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tags (comma separated)</label>
                    <input type="text" name="tags" class="form-control" placeholder="tech,food,books">
                </div>

                <button class="btn btn-primary">Save</button>

            </form>

        </div>
    </div>

</div>

</body>
</html>
