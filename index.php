<?php
require 'config.php';

$tag = $_GET['tag'] ?? null;

if ($tag) {
    $stmt = $pdo->prepare("
        SELECT id, date_added, title, link, review, rating, tags
        FROM reviews.items
        WHERE tags ILIKE :tag
        ORDER BY date_added DESC
    ");

    $stmt->execute([
        ':tag' => '%' . $tag . '%'
    ]);
} else {
    $stmt = $pdo->query("
        SELECT id, date_added, title, link, review, rating, tags
        FROM reviews.items
        ORDER BY date_added DESC
    ");
}

$items = $stmt->fetchAll();
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

    <!-- Tag Filter -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input 
                type="text" 
                name="tag" 
                class="form-control" 
                placeholder="Filter by tag..." 
                value="<?= htmlspecialchars($tag ?? '') ?>"
            >
            <button class="btn btn-outline-primary">Filter</button>
            <a href="index.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

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

                        <!-- External Link Button -->
                        <?php if (!empty($item['link'])): ?>
                            <a 
                                href="<?= htmlspecialchars($item['link']) ?>" 
                                target="_blank" 
                                class="btn btn-sm btn-outline-primary"
                            >
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
                            <?php
                            $tags = explode(',', $item['tags']);
                            foreach ($tags as $t):
                                $t = trim($t);
                            ?>
                                <a href="?tag=<?= urlencode($t) ?>" class="badge bg-primary text-decoration-none me-1">
                                    <?= htmlspecialchars($t) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No reviews found.</div>
    <?php endif; ?>

</div>

</body>
</html>
