<?php
require 'config.php';

/*
|--------------------------------------------------------------------------
| Fetch existing tags for autocomplete
|--------------------------------------------------------------------------
*/
$tagStmt = $pdo->query("
    SELECT DISTINCT trim(tag) AS tag
    FROM (
        SELECT unnest(string_to_array(tags, ',')) AS tag
        FROM reviews.items
    ) t
    WHERE tag IS NOT NULL AND trim(tag) <> ''
    ORDER BY tag ASC
");
$existingTags = array_column($tagStmt->fetchAll(), 'tag');

/*
|--------------------------------------------------------------------------
| Handle form submit
|--------------------------------------------------------------------------
*/
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title  = trim($_POST['title'] ?? '');
    $link   = trim($_POST['link'] ?? '');
    $review = trim($_POST['review'] ?? '');
    $rating = $_POST['rating'] !== '' ? (float)$_POST['rating'] : null;
    $tags   = trim($_POST['tags'] ?? '');

    if ($title === '') {
        $error = "Title is required.";
    } else {

        /*
        |--------------------------------------------------------------------------
        | Normalize tags (trim, lowercase, deduplicate)
        |--------------------------------------------------------------------------
        */
        if ($tags !== '') {
            $tagArray = array_map(function ($t) {
                return strtolower(trim($t));
            }, explode(',', $tags));

            $tagArray = array_filter($tagArray); // remove empty
            $tagArray = array_unique($tagArray);

            $tags = implode(', ', $tagArray);
        }

        $stmt = $pdo->prepare("
            INSERT INTO reviews.items (date_added, title, link, review, rating, tags)
            VALUES (NOW(), :title, :link, :review, :rating, :tags)
        ");

        $stmt->execute([
            ':title'  => $title,
            ':link'   => $link ?: null,
            ':review' => $review,
            ':rating' => $rating,
            ':tags'   => $tags ?: null
        ]);

        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/rating.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Add Review</h1>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST">

                <!-- Title -->
                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <!-- Link -->
                <div class="mb-3">
                    <label class="form-label">Link</label>
                    <input type="url" name="link" class="form-control">
                </div>

                <!-- Review -->
                <div class="mb-3">
                    <label class="form-label">Review</label>
                    <textarea name="review" class="form-control" rows="5"></textarea>
                </div>

                <!-- Rating -->
		<div class="mb-3">
		    <label class="form-label">Rating</label>

		    <div class="star-rating">
			<input type="hidden" name="rating" value="0">

			<span class="star" data-value="1">★</span>
			<span class="star" data-value="2">★</span>
			<span class="star" data-value="3">★</span>
			<span class="star" data-value="4">★</span>
			<span class="star" data-value="5">★</span>
		    </div>
		</div>

                <!-- Tags with autocomplete -->
                <div class="mb-3">
                    <label class="form-label">Tags (comma separated)</label>

                    <input 
                        type="text" 
                        name="tags" 
                        id="tagsInput"
                        class="form-control"
                        list="tagSuggestions"
                        placeholder="e.g. hrm, shoes, clothes"
                    >

                    <datalist id="tagSuggestions">
                        <?php foreach ($existingTags as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>">
                        <?php endforeach; ?>
                    </datalist>

                    <div class="form-text">
                        Start typing to see suggestions. You can still enter new tags.
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn btn-primary">
                    Save
                </button>

            </form>

        </div>
    </div>

</div>

<!-- Tag autocomplete logic -->
<script>
const input = document.getElementById('tagsInput');
const datalist = document.getElementById('tagSuggestions');

input.addEventListener('input', function () {
    const parts = this.value.split(',');
    const last = parts[parts.length - 1].trim().toLowerCase();

    for (const option of datalist.options) {
        option.hidden = !option.value.toLowerCase().includes(last);
    }
});

input.addEventListener('change', function () {
    const parts = this.value.split(',');
    const last = parts[parts.length - 1].trim().toLowerCase();

    for (const option of datalist.options) {
        if (option.value.toLowerCase() === last) {
            parts[parts.length - 1] = ' ' + option.value;
            this.value = parts.join(',').replace(/^ /, '');
            break;
        }
    }
});
</script>

<script src="assets/rating.js"></script>

</body>
</html>
