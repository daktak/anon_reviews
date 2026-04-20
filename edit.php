<?php
require 'config.php';

$isAdmin = $_SESSION['is_admin'] ?? false;

if (!$isAdmin) {
    die("Unauthorized");
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid ID");
}

/*
|--------------------------------------------------------------------------
| Fetch item
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, title, link, review, rating, tags
    FROM reviews.items
    WHERE id = :id
");

$stmt->execute([':id' => $id]);
$item = $stmt->fetch();

if (!$item) {
    die("Item not found");
}

/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title  = $_POST['title'] ?? '';
    $link   = $_POST['link'] ?? '';
    $review = $_POST['review'] ?? '';
    $rating = $_POST['rating'] ?? null;
    $tags   = $_POST['tags'] ?? '';

    $stmt = $pdo->prepare("
        UPDATE reviews.items
        SET title = :title,
            link = :link,
            review = :review,
            rating = :rating,
            tags = :tags
        WHERE id = :id
    ");

    $stmt->execute([
        ':title'  => $title,
        ':link'   => $link,
        ':review' => $review,
        ':rating' => $rating,
        ':tags'   => $tags,
        ':id'     => $id
    ]);

    header("Location: item.php?id=$id");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <h2>Edit Item</h2>

    <form method="POST">

        <div class="mb-2">
            <label class="form-label">Title</label>
            <input class="form-control" name="title"
                   value="<?= htmlspecialchars($item['title']) ?>" required>
        </div>

        <div class="mb-2">
            <label class="form-label">Link</label>
            <input class="form-control" name="link"
                   value="<?= htmlspecialchars($item['link']) ?>">
        </div>

        <div class="mb-2">
            <label class="form-label">Review</label>
            <textarea class="form-control" name="review" rows="6" required><?= htmlspecialchars($item['review']) ?></textarea>
        </div>

        <div class="mb-2">
            <label class="form-label">Rating</label>
            <input class="form-control" type="number" step="0.1" min="0" max="5"
                   name="rating" value="<?= htmlspecialchars($item['rating']) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Tags (comma separated)</label>
            <input class="form-control" name="tags"
                   value="<?= htmlspecialchars($item['tags']) ?>">
        </div>

        <button class="btn btn-primary">Save</button>
        <a href="item.php?id=<?= $id ?>" class="btn btn-secondary">Cancel</a>

    </form>

</div>

</body>
</html>
