<?php
require 'config.php';

$isAdmin = $_SESSION['is_admin'] ?? false;

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("Invalid item ID");
}

/*
|--------------------------------------------------------------------------
| SORT + PAGINATION PARAMS
|--------------------------------------------------------------------------
*/
$sort = $_GET['sort'] ?? 'latest'; // latest | rating
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| ITEM
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, date_added, title, link, review, rating, tags, initial_rating
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
| SORT ORDER
|--------------------------------------------------------------------------
*/
$orderBy = "date_added DESC";

if ($sort === 'rating') {
    $orderBy = "rating DESC NULLS LAST";
}

/*
|--------------------------------------------------------------------------
| TOTAL COMMENTS
|--------------------------------------------------------------------------
*/
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM reviews.comments
    WHERE review_item = :id
");
$countStmt->execute([':id' => $id]);
$totalComments = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalComments / $perPage));

/*
|--------------------------------------------------------------------------
| COMMENTS (paginated + sorted)
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, username, comment, rating, date_added
    FROM reviews.comments
    WHERE review_item = :id
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':id', $id);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$comments = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| ADD COMMENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $comment  = $_POST['comment'] ?? '';
    $rating   = $_POST['rating'] ?? 0;

    if ($username && $comment && is_numeric($rating)) {

        $stmt = $pdo->prepare("
            INSERT INTO reviews.comments (review_item, username, comment, rating)
            VALUES (:item, :user, :comment, :rating)
        ");

        $stmt->execute([
            ':item' => $id,
            ':user' => $username,
            ':comment' => $comment,
            ':rating' => (float)$rating
        ]);

        header("Location: item.php?id=$id");
        exit;
    }
}

function pageUrl($page, $sort, $id) {
    return "?id=$id&page=$page&sort=$sort";
}
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 604800) return floor($time/86400) . ' days ago';

    return date('M j, Y', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($item['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/rating.css" rel="stylesheet">

    <style>
        body { background:#f5f6f8; }
        .container { max-width:720px; }

        .card-feed {
            border:0;
            border-radius:12px;
            margin-bottom:12px;
        }

        .tag-chip {
            display:inline-block;
            padding:4px 10px;
            border-radius:999px;
            background:#e9ecef;
            margin-right:6px;
            font-size:0.85rem;
        }

        .meta {
            font-size:0.8rem;
            color:#6c757d;
        }

        .rating {
            color:#f5b301;
        }

        .pagination-sm a {
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

<div class="container py-3">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-2">

        <a href="index.php" class="btn btn-sm btn-outline-secondary">
            ← Feed
        </a>

        <div>
            <a href="?id=<?= $id ?>&sort=latest"
               class="btn btn-sm <?= $sort === 'latest' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Latest
            </a>

            <a href="?id=<?= $id ?>&sort=rating"
               class="btn btn-sm <?= $sort === 'rating' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Rating
            </a>
        </div>

    </div>

    <!-- ITEM -->
    <div class="card card-feed shadow-sm">

        <div class="card-body">

            <h5>
                    <?php if (!empty($item['link'])): ?>
                        <a href="<?= htmlspecialchars($item['link']) ?>"
                           target="_blank"
			   class="text-decoration-none fw-bold">
                    <?php endif; ?>
	    <?= htmlspecialchars($item['title']) ?>
                    <?php if (!empty($item['link'])): ?>
                        </a>
                    <?php endif; ?>
	    </h5>

            <div class="meta mb-2">
		<?= timeAgo($item['date_added']) ?>
            </div>

            <p><?= nl2br(htmlspecialchars($item['review'])) ?></p>

                <!-- ACTIONS -->
                <div class="action-row">
                    <?php if (!empty($item['link'])): ?>
                        <a href="<?= htmlspecialchars($item['link']) ?>"
                           target="_blank"
                           class="btn btn-sm btn-outline-primary">
                            Link
                        </a>
                    <?php endif; ?>
                </div>

            <div class="rating mb-2">
                <?= str_repeat("★", floor($item['initial_rating'])) ?>
                <?= ($item['rating'] - floor($item['initial_rating']) >= 0.5) ? "½" : "" ?>
                <?= str_repeat("☆", 5 - ceil($item['initial_rating'])) ?>
                <span class="text-muted ms-1">
                    <?= $item['initial_rating'] ? round($item['initial_rating'],1) : 'N/A' ?>/5
                </span>
            </div>

            <div>
                <?php foreach (explode(',', $item['tags']) as $t): ?>
                    <?php $t = trim($t); if ($t): ?>
                        <span class="tag-chip"><?= htmlspecialchars($t) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <!-- TOP PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <nav class="mb-2">
            <ul class="pagination pagination-sm">

                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($page-1,$sort,$id) ?>">Prev</a>
                    </li>
                <?php endif; ?>

                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($page+1,$sort,$id) ?>">Next</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
    <?php endif; ?>

    <!-- COMMENTS -->
    <?php foreach ($comments as $c): ?>

        <div class="card card-feed shadow-sm">

            <div class="card-body">

                <div class="d-flex justify-content-between">
                    <strong><?= htmlspecialchars($c['username']) ?></strong>

                    <div class="rating">
                        <?= str_repeat("★", floor($c['rating'])) ?>
                        <?= ($c['rating'] - floor($c['rating']) >= 0.5) ? "½" : "" ?>
                    </div>
                </div>

                <div class="meta mb-2">
                    <?= htmlspecialchars($c['date_added']) ?>
                </div>

                <div>
                    <?= nl2br(htmlspecialchars($c['comment'])) ?>
                </div>

                <?php if ($isAdmin): ?>
                    <a href="delete_comment.php?id=<?= $c['id'] ?>&item=<?= $id ?>"
                       class="btn btn-sm btn-outline-danger mt-2">
                        Delete
                    </a>
                <?php endif; ?>

            </div>
        </div>

    <?php endforeach; ?>

    <!-- BOTTOM PAGINATION -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-2">
            <ul class="pagination pagination-sm">

                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($page-1,$sort,$id) ?>">Prev</a>
                    </li>
                <?php endif; ?>

                <li class="page-item active">
                    <span class="page-link"><?= $page ?></span>
                </li>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= pageUrl($page+1,$sort,$id) ?>">Next</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
    <?php endif; ?>

    <!-- ADD COMMENT -->
    <div class="card card-feed shadow-sm mt-3">

        <div class="card-body">

            <h6>Add Comment</h6>

            <form method="POST">

                <input type="text" name="username" class="form-control mb-2" placeholder="anon" required>

                <textarea name="comment" class="form-control mb-2" required></textarea>

                <div class="star-rating mb-3">
                    <input type="hidden" name="rating" value="0">

                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>

                <button class="btn btn-primary w-100">Submit</button>

            </form>

        </div>
    </div>

</div>

<script src="assets/rating.js"></script>

</body>
</html>
