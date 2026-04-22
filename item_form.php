<?php
require 'config.php';

session_start();

$isAdmin = $_SESSION['is_admin'] ?? false;

/*
|--------------------------------------------------------------------------
| MODE
|--------------------------------------------------------------------------
*/
$id = $_GET['id'] ?? null;
$isEdit = $id && is_numeric($id);

$tagStmt = $pdo->query("
    SELECT DISTINCT trim(unnest(string_to_array(tags, ','))) AS tag
    FROM reviews.items
    WHERE tags IS NOT NULL
");

$existingTags = array_filter($tagStmt->fetchAll(PDO::FETCH_COLUMN));
/*
|--------------------------------------------------------------------------
| LOAD ITEM (EDIT MODE ONLY)
|--------------------------------------------------------------------------
*/
$item = [
    'title' => '',
    'username' => '',
    'link' => '',
    'review' => '',
    'initial_rating' => '',
    'tags' => '',
    'emoji' => ''
];

if ($isEdit) {

    // EDIT REQUIRES ADMIN
    if (!$isAdmin) {
        http_response_code(403);
        die("Forbidden (edit requires admin)");
    }

    $stmt = $pdo->prepare("
        SELECT id, username, title, link, review, initial_rating, tags, emoji
        FROM reviews.items
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch();

    if (!$item) {
        die("Item not found");
    }
}

/*
|--------------------------------------------------------------------------
| HANDLE POST (CREATE OR UPDATE)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title  = $_POST['title'] ?? '';
    $username = $_POST['username'] ?? '';
    $link   = $_POST['link'] ?? '';
    $review = $_POST['review'] ?? '';
    $rating = $_POST['rating'] ?? null;
    $initial_rating = $_POST['initial_rating'] ?? null;
    $tags   = $_POST['tags'] ?? '';
    $emoji  = $_POST['emoji'] ?? '';

    if (!$title || !$review) {
        die("Title and review required");
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE (ADMIN ONLY)
    |--------------------------------------------------------------------------
    */
    if ($isEdit) {

        if (!$isAdmin) {
            http_response_code(403);
            die("Forbidden (edit requires admin)");
        }

        $stmt = $pdo->prepare("
            UPDATE reviews.items
            SET title = :title,
	        username = :username,
                link = :link,
                review = :review,
                initial_rating = :initial_rating,
                tags = :tags,
		emoji = :emoji
            WHERE id = :id
        ");

        $stmt->execute([
            ':title' => $title,
            ':username' => $username,
            ':link' => $link,
            ':review' => $review,
            ':initial_rating' => ($rating !== '' ? (float)$rating : null),
            ':tags' => $tags,
	    ':emoji' => $emoji,
            ':id' => $id
        ]);

    /*
    |--------------------------------------------------------------------------
    | INSERT (PUBLIC ALLOWED)
    |--------------------------------------------------------------------------
    */
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO reviews.items (username, title, link, review, initial_rating, tags, emoji)
            VALUES (:username, :title, :link, :review, :initial_rating, :tags, :emoji)
        ");

        $stmt->execute([
            ':title' => $title,
            ':username' => $username,
            ':link' => $link,
            ':review' => $review,
            ':initial_rating' => ($rating !== '' ? (float)$rating : null),
            ':tags' => $tags,
	    ':emoji' => $emoji
        ]);
    }

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $isEdit ? 'Edit Item' : 'Add Item' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/rating.css" rel="stylesheet">

    <style>
        body { background:#f5f6f8; }
        .container { max-width:720px; }

        .card-feed {
            border:0;
            border-radius:12px;
        }

        .meta {
            font-size:0.8rem;
            color:#6c757d;
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

        <span class="meta">
            <?= $isEdit ? 'Edit Item (Admin)' : 'New Item' ?>
        </span>

    </div>

    <!-- FORM -->
    <div class="card card-feed shadow-sm">

        <div class="card-body">

            <form method="POST">

                <input type="text"
                       name="link"
		       id="link"
                       class="form-control mb-2"
                       placeholder="Link (optional)"
                       value="<?= htmlspecialchars($item['link']) ?>">

                <small id="linkHint" class="text-muted d-block mb-2"></small>

                <input type="text"
                       name="title"
		       id="title"
                       class="form-control mb-2"
                       placeholder="Title"
                       value="<?= htmlspecialchars($item['title']) ?>"
                       required>

                <input type="text"
                       name="username"
                       class="form-control mb-2"
                       placeholder="anon"
                       value="<?= htmlspecialchars($item['username']) ?>">


                <textarea name="review"
                          class="form-control mb-2"
                          rows="6"
                          placeholder="Review"
                          required><?= htmlspecialchars($item['review']) ?></textarea>

		<label class="form-label">Tags</label>

		<input list="tag-suggestions"
		       name="tags"
		       class="form-control mb-3"
		       placeholder="Type or select tags (comma separated)"
		       value="<?= htmlspecialchars($item['tags']) ?>">

		<datalist id="tag-suggestions">
		    <?php foreach ($existingTags as $t): ?>
			<?php if ($t): ?>
			    <option value="<?= htmlspecialchars($t) ?>"></option>
			<?php endif; ?>
		    <?php endforeach; ?>
		</datalist>

		<!-- RATING (HALF STAR UI) -->
		<div class="mb-3">

		    <label class="form-label">Rating</label>

		    <div class="star-rating" data-value="<?= htmlspecialchars($item['initial_rating'] ?? 0) ?>">
			<input type="hidden" name="rating" value="<?= htmlspecialchars($item['initial_rating'] ?? 0) ?>">

			<span class="star" data-value="1">★</span>
			<span class="star" data-value="2">★</span>
			<span class="star" data-value="3">★</span>
			<span class="star" data-value="4">★</span>
			<span class="star" data-value="5">★</span>
		    </div>

		</div>

		<label class="form-label">Emoji (optional)</label>

		<input type="hidden" name="emoji" id="emoji" value="<?= htmlspecialchars($item['emoji']) ?>">

		<div class="mb-2">
		    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearEmoji()">
			Clear
		    </button>
		</div>

		<div class="mb-2" style="font-size:28px;" id="emojiDisplay">
		    <?= htmlspecialchars($item['emoji']) ?: '—' ?>
		</div>

		<div class="mb-2">
		    <button type="button" class="btn btn-light" onclick="setEmoji('👍')">👍</button>
		    <button type="button" class="btn btn-light" onclick="setEmoji('👎')">👎</button>
		</div>

		<div id="emojiGrid" style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px;max-width:400px;"></div>

                <button class="btn btn-primary w-100">
                    <?= $isEdit ? 'Update (Admin)' : 'Create Item' ?>
                </button>

            </form>

        </div>
    </div>

</div>

<script src="assets/rating.js"></script>
<script src="assets/fetch_title.js"></script>
<script src="assets/emoji.js"></script>
</body>
</html>
