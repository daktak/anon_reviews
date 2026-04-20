<?php
require 'config.php';

$isAdmin = $_SESSION['is_admin'] ?? false;

$tag  = $_GET['tag']  ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| FILTER
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
| COUNT
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
| ITEMS
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
| TAG CLOUD (top bar)
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

function tagSize($c, $min, $max) {
    if ($max == $min) return 1.0;
    return 0.9 + (($c - $min) / ($max - $min)) * 0.8;
}

$counts = array_column($tagCounts, 'count');
$min = $counts ? min($counts) : 0;
$max = $counts ? max($counts) : 1;

function pageUrl($page, $tag) {
    return '?page=' . $page . ($tag ? '&tag=' . urlencode($tag) : '');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reviews</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6f8;
        }

        .feed-card {
            border: 0;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .tag-row {
            overflow-x: auto;
            white-space: nowrap;
        }

	.tag-cloud {
	    display: flex;
	    flex-wrap: wrap;
	    gap: 6px 8px;
	    max-height: 160px;     /* key reduction in scrolling */
	    overflow-y: auto;      /* keeps it compact but scrollable if needed */
	    padding: 6px;
	}

	.tag-chip {
	    display: inline-block;
	    padding: 4px 10px;
	    border-radius: 999px;
	    background: #e9ecef;
	    text-decoration: none;
	    color: #333;
	    line-height: 1.2;
	    white-space: nowrap;
	}

	.tag-chip.active {
	    background: #0d6efd;
	    color: white;
	}

        .meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .rating {
            color: #f5b301;
            font-size: 1rem;
        }

        .action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .container {
            max-width: 720px;
        }
    </style>
</head>

<body>

<div class="container py-3">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">Reviews</h4>

        <div class="d-flex gap-2">
            <a href="item_form.php" class="btn btn-primary btn-sm">+ Add</a>
            <?php if ($isAdmin): ?>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- GLOBAL TAG FILTER (horizontal scroll) -->

	<div class="tag-cloud">

	    <a href="index.php"
	       class="tag-chip <?= !$tag ? 'active' : '' ?>">
		All
	    </a>

	    <?php foreach ($tagCounts as $t): ?>
		<a href="?tag=<?= urlencode($t['tag']) ?>"
		   class="tag-chip <?= ($tag === $t['tag']) ? 'active' : '' ?>"
		   style="font-size: <?= tagSize($t['count'], $min, $max) ?>rem;">
		    <?= htmlspecialchars($t['tag']) ?> (<?= $t['count'] ?>)
		</a>
	    <?php endforeach; ?>

	</div>
    <!-- FEED -->
    <?php foreach ($items as $item): ?>

        <div class="card feed-card shadow-sm">

            <div class="card-body">

                <!-- TITLE -->
                <div class="d-flex justify-content-between align-items-start">
                    <a href="item.php?id=<?= $item['id'] ?>"
                       class="text-decoration-none fw-bold">
                        <?= htmlspecialchars($item['title']) ?>
                    </a>

                    <?php if ($isAdmin): ?>
                            <a href="item_form.php?id=<?= $item['id'] ?>"
                               class="btn btn-sm btn-warning">
                                Edit
                            </a>
                        <a href="delete.php?id=<?= $item['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete item?')">
                            Del
                        </a>
                    <?php endif; ?>
                </div>

                <!-- META -->
                <div class="meta mt-1">
                    <?= htmlspecialchars($item['date_added']) ?>
                </div>

                <!-- REVIEW -->
                <p class="mt-2 mb-2">
                    <?= nl2br(htmlspecialchars($item['review'])) ?>
                </p>

                <!-- RATING -->
                <div class="rating mb-2">
                    <?= str_repeat("★", floor($item['rating'])) ?>
                    <?= $item['rating'] - floor($item['rating']) >= 0.5 ? "½" : "" ?>
                    <?= str_repeat("☆", 5 - ceil($item['rating'])) ?>
                    <span class="text-muted ms-1">
                        (<?= $item['rating'] ? round($item['rating'], 1) : 'N/A' ?>)
                    </span>
                </div>

                <!-- TAGS -->
                <div class="mb-2">
                    <?php foreach (explode(',', $item['tags']) as $t): ?>
                        <?php $t = trim($t); ?>
                        <?php if ($t): ?>
                            <a href="?tag=<?= urlencode($t) ?>"
                               class="tag-chip">
                                <?= htmlspecialchars($t) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- ACTIONS -->
                <div class="action-row">
                    <?php if (!empty($item['link'])): ?>
                        <a href="<?= htmlspecialchars($item['link']) ?>"
                           target="_blank"
                           class="btn btn-sm btn-outline-primary">
                            Link
                        </a>
                    <?php endif; ?>

                    <a href="item.php?id=<?= $item['id'] ?>"
                       class="btn btn-sm btn-outline-secondary">
                        Comments
                    </a>
                </div>

            </div>
        </div>

    <?php endforeach; ?>

    <!-- PAGINATION -->
    <div class="d-flex justify-content-between mt-3">

        <?php if ($page > 1): ?>
            <a class="btn btn-outline-secondary"
               href="<?= pageUrl($page - 1, $tag) ?>">
                ← Prev
            </a>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

        <div class="align-self-center text-muted">
            Page <?= $page ?> / <?= $totalPages ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a class="btn btn-outline-secondary"
               href="<?= pageUrl($page + 1, $tag) ?>">
                Next →
            </a>
        <?php else: ?>
            <div></div>
        <?php endif; ?>

    </div>

    <!-- FOOTER -->
    <div class="text-center mt-4 mb-2">
        <small class="text-muted">
            Source:
            <a href="https://github.com/daktak/anon_reviews" target="_blank">
                GPLv3 on GitHub
            </a>
            |
            <a href="https://github.com/daktak/anon_reviews/issues" target="_blank">
                Report issue
            </a>
        </small>
    </div>

</div>

</body>
</html>
