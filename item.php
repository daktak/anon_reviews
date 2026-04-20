<?php
require 'config.php';

$isAdmin = $_SESSION['is_admin'] ?? false;

$item_id = $_GET['id'] ?? null;

if (!$item_id || !is_numeric($item_id)) {
    die("Invalid ID");
}

/*
|--------------------------------------------------------------------------
| Pagination + sort
|--------------------------------------------------------------------------
*/
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$sort = $_GET['sort'] ?? 'newest';
if (!in_array($sort, ['newest','rating'], true)) {
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

        header("Location: item.php?id=$item_id");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Fetch item
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT * FROM reviews.items WHERE id = :id
");

$stmt->execute([':id' => $item_id]);
$item = $stmt->fetch();

if (!$item) {
    die("Not found");
}

/*
|--------------------------------------------------------------------------
| Delete comment (ADMIN ONLY - server side protection)
|--------------------------------------------------------------------------
*/
if ($isAdmin && isset($_GET['delete_comment'])) {

    $cid = (int)$_GET['delete_comment'];

    $del = $pdo->prepare("
        DELETE FROM reviews.comments
        WHERE id = :cid AND review_item = :item_id
    ");

    $del->execute([
        ':cid' => $cid,
        ':item_id' => $item_id
    ]);

    header("Location: item.php?id=$item_id");
    exit;
}

/*
|--------------------------------------------------------------------------
| Count comments
|--------------------------------------------------------------------------
*/
$count = $pdo->prepare("
    SELECT COUNT(*) FROM reviews.comments WHERE review_item = :id
");
$count->execute([':id' => $item_id]);
$total = (int)$count->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

/*
|--------------------------------------------------------------------------
| Sort
|--------------------------------------------------------------------------
*/
$orderBy = $sort === 'rating'
    ? "rating DESC, date_added DESC"
    : "date_added DESC";

/*
|--------------------------------------------------------------------------
| Fetch comments
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT id, username, comment, rating, date_added
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
function pageUrl($id, $page, $sort) {
    return "item.php?id=$id&page=$page&sort=" . urlencode($sort);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($item['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/rating.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center">

        <h2><?= htmlspecialchars($item['title']) ?>

<?php if (!empty($item['link'])): ?>
    <a href="<?= htmlspecialchars($item['link']) ?>"
       target="_blank"
       rel="noopener noreferrer"
       class="btn btn-lg btn-outline-primary">
        View Link
    </a>
<?php endif; ?>

</h2>
        <div class="d-flex gap-2">

            <?php if ($isAdmin): ?>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    Logout
                </a>
            <?php endif; ?>


            <a href="index.php" class="btn btn-secondary btn-sm">
                Back
            </a>

        </div>

    </div>

    <hr>

    <!-- Item -->
    <div class="card mb-3">
        <div class="card-body">

            <small class="text-muted"><?= $item['date_added'] ?></small>

            <p class="mt-2"><?= nl2br(htmlspecialchars($item['review'])) ?></p>

            <span class="badge bg-secondary">
                <?= $item['rating'] ? round($item['rating'],2) : 'N/A' ?>/5
            </span>

        </div>
    </div>

    <!-- Comments header -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">Comments</h4>

        <div class="btn-group">
            <a class="btn btn-sm <?= $sort=='newest'?'btn-primary':'btn-outline-primary' ?>"
               href="<?= pageUrl($item_id,1,'newest') ?>">
                Newest
            </a>

            <a class="btn btn-sm <?= $sort=='rating'?'btn-primary':'btn-outline-primary' ?>"
               href="<?= pageUrl($item_id,1,'rating') ?>">
                Top Rated
            </a>
        </div>

    </div>

    <!-- Pagination top -->
    <ul class="pagination">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <li class="page-item <?= $i==$page?'active':'' ?>">
                <a class="page-link" href="<?= pageUrl($item_id,$i,$sort) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>

    <!-- Comments -->
    <?php foreach ($comments as $c): ?>

        <div class="card mb-2">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <strong><?= htmlspecialchars($c['username']) ?></strong>
                        <span class="badge bg-secondary"><?= $c['rating'] ?>/5</span>
                    </div>

                    <?php if ($isAdmin): ?>
                        <a href="item.php?id=<?= $item_id ?>&delete_comment=<?= $c['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete comment?')">
                            Delete
                        </a>
                    <?php endif; ?>

                </div>

                <p class="mb-1"><?= htmlspecialchars($c['comment']) ?></p>

                <small class="text-muted"><?= $c['date_added'] ?></small>

            </div>
        </div>

    <?php endforeach; ?>

    <!-- Pagination bottom -->
    <ul class="pagination">
        <?php for ($i=1;$i<=$totalPages;$i++): ?>
            <li class="page-item <?= $i==$page?'active':'' ?>">
                <a class="page-link" href="<?= pageUrl($item_id,$i,$sort) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>

    <!-- Add comment -->
    <div class="card mt-4">
        <div class="card-body">

            <h5>Add Comment</h5>

            <form method="POST">

                <input class="form-control mb-2" name="username" placeholder="Name" required>

                <textarea class="form-control mb-2" name="comment" required></textarea>

		<div class="mb-3">
		    <label class="form-label">Rating</label>

		    <div class="star-rating">
		        <input type="hidden" name="rating" id="rating" value="0">
			<span class="star" data-value="1">★</span>
			<span class="star" data-value="2">★</span>
			<span class="star" data-value="3">★</span>
			<span class="star" data-value="4">★</span>
			<span class="star" data-value="5">★</span>
		    </div>
		</div>

                <button class="btn btn-primary">Submit</button>

            </form>

        </div>
    </div>

</div>

<script src="assets/rating.js"></script>
</body>
</html>
