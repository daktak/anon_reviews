<?php
require 'config.php';

$item_id = $_GET['id'] ?? null;

if (!$item_id || !is_numeric($item_id)) {
    die("Invalid item ID");
}

/*
|--------------------------------------------------------------------------
| Pagination + sorting
|--------------------------------------------------------------------------
*/
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$sort = $_GET['sort'] ?? 'newest';
if (!in_array($sort, ['newest', 'rating'], true)) {
    $sort = 'newest';
}

/*
|--------------------------------------------------------------------------
| Handle comment submission
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $comment  = $_POST['comment'] ?? '';
    $rating   = $_POST['rating'] ?? '';

    if ($username && $comment && is_numeric($rating)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reviews.comments (review_item, username, comment, rating)
                VALUES (:item_id, :username, :comment, :rating)
            ");

            $stmt->execute([
                ':item_id'  => $item_id,
                ':username' => $username,
                ':comment'  => $comment,
                ':rating'   => (float)$rating
            ]);

            $stmt = $pdo->prepare("
                UPDATE reviews.items
                SET rating = (
                    SELECT AVG(rating)
                    FROM reviews.comments
                    WHERE review_item = :item_id
                )
                WHERE id = :item_id
            ");

            $stmt->execute([':item_id' => $item_id]);

            header("Location: item.php?id=$item_id");
            exit;

        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fetch item
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, date_added, title, link, review, rating, tags
    FROM reviews.items
    WHERE id = :id
");

$stmt->execute([':id' => $item_id]);
$item = $stmt->fetch();

if (!$item) {
    die("Item not found");
}

/*
|--------------------------------------------------------------------------
| Count comments
|--------------------------------------------------------------------------
*/
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM reviews.comments
    WHERE review_item = :id
");

$countStmt->execute([':id' => $item_id]);
$totalComments = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalComments / $perPage));

/*
|--------------------------------------------------------------------------
| Sort logic
|--------------------------------------------------------------------------
*/
$orderBy = $sort === 'rating'
    ? 'rating DESC, date_added DESC'
    : 'date_added DESC';

/*
|--------------------------------------------------------------------------
| Fetch comments (paginated)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT username, comment, rating, date_added
    FROM reviews.comments
    WHERE review_item = :id
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':id', $item_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$comments = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Pagination helper
|--------------------------------------------------------------------------
*/
function pageUrl($item_id, $page, $sort) {
    return "item.php?id=$item_id&page=$page&sort=" . urlencode($sort);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($item['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><?= htmlspecialchars($item['title']) ?></h1>
        <a href="index.php" class="btn btn-outline-secondary">← Back</a>
    </div>

    <!-- Item -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <small class="text-muted d-block mb-2">
                <?= htmlspecialchars($item['date_added']) ?>
            </small>

            <?php if (!empty($item['link'])): ?>
                <a href="<?= htmlspecialchars($item['link']) ?>" target="_blank"
                   class="btn btn-sm btn-outline-primary mb-2">
                    View Link
                </a>
            <?php endif; ?>

            <p class="card-text">
                <?= nl2br(htmlspecialchars($item['review'])) ?>
            </p>

            <span class="badge bg-secondary">
                Rating: <?= $item['rating'] ? round($item['rating'], 2) : 'N/A' ?>/5
            </span>

            <?php if (!empty($item['tags'])): ?>
                <div class="mt-3">
                    <?php foreach (explode(',', $item['tags']) as $t): ?>
                        <?php $t = trim($t); ?>
                        <span class="badge bg-primary me-1"><?= htmlspecialchars($t) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Comments header + sort toggle -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Comments</h4>

        <div class="btn-group">
            <a href="<?= pageUrl($item_id, 1, 'newest') ?>"
               class="btn btn-sm <?= $sort === 'newest' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Newest
            </a>

            <a href="<?= pageUrl($item_id, 1, 'rating') ?>"
               class="btn btn-sm <?= $sort === 'rating' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Top Rated
            </a>
        </div>
    </div>

    <!-- Top pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mb-3">
            <ul class="pagination">

                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($item_id, $page - 1, $sort) ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= pageUrl($item_id, $i, $sort) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($item_id, $page + 1, $sort) ?>">Next</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
    <?php endif; ?>

    <!-- Comments -->
    <?php if ($comments): ?>
        <?php foreach ($comments as $c): ?>
            <div class="card mb-2">
                <div class="card-body">

                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($c['username']) ?></strong>
                        <span class="badge bg-secondary"><?= $c['rating'] ?>/5</span>
                    </div>

                    <p class="mb-1"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>

                    <small class="text-muted"><?= $c['date_added'] ?></small>

                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No comments yet.</div>
    <?php endif; ?>

    <!-- Bottom pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination">

                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($item_id, $page - 1, $sort) ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= pageUrl($item_id, $i, $sort) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($item_id, $page + 1, $sort) ?>">Next</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
    <?php endif; ?>

    <!-- Add comment -->
    <div class="card shadow-sm mt-4">
        <div class="card-body">

            <h5 class="mb-3">Add Comment</h5>

            <form method="POST">

                <div class="mb-2">
                    <input type="text" name="username" class="form-control" placeholder="Your name" required>
                </div>

                <div class="mb-2">
                    <textarea name="comment" class="form-control" placeholder="Your comment" required></textarea>
                </div>

                <div class="mb-3">
                    <input type="number" name="rating" class="form-control" min="1" max="5" placeholder="Rating (1-5)" required>
                </div>

                <button class="btn btn-primary">Submit</button>

            </form>

        </div>
    </div>

</div>

</body>
</html>
