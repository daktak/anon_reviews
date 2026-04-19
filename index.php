<?php
require 'config.php';

$isAdmin = $_SESSION['is_admin'] ?? false;

$tag  = $_GET['tag']  ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/
$where = '';
$params = [];

if ($tag) {
    $where = "WHERE tags ILIKE :tag";
    $params[':tag'] = '%' . $tag . '%';
}

/*
|--------------------------------------------------------------------------
| Count
|--------------------------------------------------------------------------
*/
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM reviews.items $where
");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));

/*
|--------------------------------------------------------------------------
| Items
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, date_added, title, link, review, rating, tags
    FROM reviews.items
    $where
    ORDER BY date_added DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$items = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Tags (cloud)
|--------------------------------------------------------------------------
*/
$tagStmt = $pdo->query("
    SELECT tag, COUNT(*) AS count
    FROM (
        SELECT trim(unnest(string_to_array(tags, ','))) AS tag
        FROM reviews.items
    ) t
    WHERE tag <> ''
    GROUP BY tag
    ORDER BY count DESC, tag ASC
");

$tagCounts = $tagStmt->fetchAll();

$counts = array_column($tagCounts, 'count');
$min = $counts ? min($counts) : 0;
$max = $counts ? max($counts) : 1;

function tagSize($c, $min, $max) {
    if ($max == $min) return 1.2;
    return 1 + (($c - $min) / ($max - $min)) * 1.5;
}

function pageUrl($page, $tag) {
    return '?page=' . $page . ($tag ? '&tag=' . urlencode($tag) : '');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Reviews</h1>

        <div>
            <a href="add.php" class="btn btn-primary">+ Add</a>

            <?php if ($isAdmin): ?>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">

        <!-- Tags -->
        <div class="col-md-3">

            <div class="card mb-3">
                <div class="card-body">

                    <strong class="d-block mb-2">Tags</strong>

                    <?php if ($tag): ?>
                        <a href="index.php" class="btn btn-sm btn-danger mb-2">Reset</a>
                    <?php endif; ?>

                    <div>
                        <?php foreach ($tagCounts as $t):
                            $size = tagSize($t['count'], $min, $max);
                        ?>
                            <a href="?tag=<?= urlencode($t['tag']) ?>"
                               style="font-size: <?= $size ?>rem"
                               class="text-decoration-none me-2 d-inline-block">
                                <?= htmlspecialchars($t['tag']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

        </div>

        <!-- Items -->
        <div class="col-md-9">

            <!-- Pagination top -->
            <?php if ($totalPages > 1): ?>
                <nav class="mb-3">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= pageUrl($i, $tag) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <?php foreach ($items as $item): ?>

                <div class="card mb-3">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">

                            <h5 class="mb-1">
                                <a href="item.php?id=<?= $item['id'] ?>">
                                    <?= htmlspecialchars($item['title']) ?>
                                </a>
                            </h5>

                            <?php if ($isAdmin): ?>
                                <a href="delete.php?id=<?= $item['id'] ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Delete this item?')">
                                    Delete
                                </a>
                            <?php endif; ?>

                        </div>

                        <small class="text-muted d-block mb-2">
                            <?= htmlspecialchars($item['date_added']) ?>
                        </small>

                        <p><?= nl2br(htmlspecialchars($item['review'])) ?></p>

                        <span class="badge bg-secondary">
                            Rating: <?= $item['rating'] ? round($item['rating'], 2) : 'N/A' ?>/5
                        </span>

                        <?php if (!empty($item['tags'])): ?>
                            <div class="mt-2">
                                <?php foreach (explode(',', $item['tags']) as $t): ?>
                                    <?php $t = trim($t); ?>
                                    <a href="?tag=<?= urlencode($t) ?>"
                                       class="badge bg-primary text-decoration-none me-1">
                                        <?= htmlspecialchars($t) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>

</div>
<!-- Footer notice -->
<div class="text-center mt-5 mb-3">
    <small class="text-muted">
        Source code available at 
        <a href="https://github.com/daktak/anon_reviews" target="_blank" rel="noopener">
            https://github.com/daktak/anon_reviews
        </a>
        — licensed under GPL v3.
        &nbsp;|&nbsp;
        <a href="https://github.com/daktak/anon_reviews/issues" target="_blank" rel="noopener">
            Report an issue
        </a>
    </small>
</div>

</body>
</html>
