<?php
require 'config.php';

$tag  = $_GET['tag']  ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| WHERE clause
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
| Total count
|--------------------------------------------------------------------------
*/
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reviews.items
    $where
");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalItems / $perPage));

/*
|--------------------------------------------------------------------------
| Fetch items
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
| Tag cloud aggregation
|--------------------------------------------------------------------------
*/
$tagStmt = $pdo->query("
    SELECT tag, COUNT(*) as count
    FROM (
        SELECT trim(unnest(string_to_array(tags, ','))) AS tag
        FROM reviews.items
    ) t
    WHERE tag <> ''
    GROUP BY tag
    ORDER BY count DESC, tag ASC
");
$tagCounts = $tagStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Tag size scaling
|--------------------------------------------------------------------------
*/
$counts = array_column($tagCounts, 'count');
$minCount = $counts ? min($counts) : 0;
$maxCount = $counts ? max($counts) : 1;

function tagSize($count, $min, $max) {
    if ($max == $min) return 1.2;
    return 1 + (($count - $min) / ($max - $min)) * 1.5; // 1rem → 2.5rem
}

/*
|--------------------------------------------------------------------------
| Helper: pagination URL
|--------------------------------------------------------------------------
*/
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
        <a href="add.php" class="btn btn-primary">+ Add Review</a>
    </div>

    <div class="row">

        <!-- Sidebar: Tag Cloud -->
        <div class="col-md-3">

            <div class="card mb-3">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="card-title mb-0">Tags</h5>

                        <?php if ($tag): ?>
                            <a href="index.php" class="btn btn-sm btn-danger">
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php foreach ($tagCounts as $t):
                            $name = $t['tag'];
                            $size = tagSize($t['count'], $minCount, $maxCount);
                        ?>
                            <a href="?tag=<?= urlencode($name) ?>"
                               style="font-size: <?= $size ?>rem; line-height: 2rem;"
                               class="me-2 text-decoration-none d-inline-block">
                                <?= htmlspecialchars($name) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

        </div>

        <!-- Main Content -->
        <div class="col-md-9">

            <!-- Active filter notice -->
            <?php if ($tag): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <div>
                        Filtering by tag: <strong><?= htmlspecialchars($tag) ?></strong>
                    </div>
                    <a href="index.php" class="btn btn-sm btn-danger">Reset</a>
                </div>
            <?php endif; ?>

            <!-- Top Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mb-3">
                    <ul class="pagination">

                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= pageUrl($page - 1, $tag) ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= pageUrl($i, $tag) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= pageUrl($page + 1, $tag) ?>">Next</a>
                            </li>
                        <?php endif; ?>

                    </ul>
                </nav>
            <?php endif; ?>

            <!-- Items -->
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-body">

                            <!-- Title -->
                            <div class="d-flex justify-content-between align-items-start">
                                <h3 class="card-title mb-1">
                                    <a href="item.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </h3>

                                <?php if (!empty($item['link'])): ?>
                                    <a href="<?= htmlspecialchars($item['link']) ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        View Link
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Date -->
                            <small class="text-muted d-block mb-2">
                                <?= htmlspecialchars($item['date_added']) ?>
                            </small>

                            <!-- Review -->
                            <p class="card-text">
                                <?= nl2br(htmlspecialchars($item['review'])) ?>
                            </p>

                            <!-- Rating -->
                            <div class="mb-2">
                                <span class="badge bg-secondary">
                                    Rating: <?= $item['rating'] ? round($item['rating'], 2) : 'N/A' ?>/5
                                </span>
                            </div>

                            <!-- Tags -->
                            <?php if (!empty($item['tags'])): ?>
                                <div class="mt-2">
                                    <?php foreach (explode(',', $item['tags']) as $t): 
                                        $t = trim($t);
                                    ?>
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

                <!-- Bottom Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination">

                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= pageUrl($page - 1, $tag) ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= pageUrl($i, $tag) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= pageUrl($page + 1, $tag) ?>">Next</a>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info">No reviews found.</div>
            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
