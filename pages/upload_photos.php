<?php
require_once "../includes/config.php";

$receipt_id = $_GET['receipt_id'] ?? '';
$branch_id = $_GET['branch_id'] ?? '';

if (!$receipt_id || !$branch_id) {
    echo "Invalid parameters.";
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = __DIR__ . "/uploads/$receipt_id/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
            $name = basename($_FILES['photos']['name'][$i]);
            $target = $uploadDir . $name;
            move_uploaded_file($tmpName, $target);
        }

        echo "<div class='alert alert-success'>Photos uploaded successfully.</div>";
    }
}

// Load existing images
$existingPhotos = [];
$photoDir = __DIR__ . "/uploads/$receipt_id/";
if (is_dir($photoDir)) {
    $existingPhotos = array_diff(scandir($photoDir), ['.', '..']);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Upload Purchase Bill Images</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <h4>Upload Bill Photos for Receipt ID: <?= htmlspecialchars($receipt_id) ?></h4>

        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <input type="file" name="photos[]" multiple accept="image/*" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Upload Photos</button>
            <a href="purchase.php" class="btn btn-secondary">Back</a>
        </form>

        <?php if (!empty($existingPhotos)): ?>
            <div class="row">
                <?php foreach ($existingPhotos as $photo): ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="uploads/<?= $receipt_id ?>/<?= $photo ?>" class="card-img-top" alt="Photo">
                            <div class="card-body text-center">
                                <form method="post" action="delete_photo.php" onsubmit="return confirm('Delete this photo?')">
                                    <input type="hidden" name="photo" value="<?= $photo ?>">
                                    <input type="hidden" name="receipt_id" value="<?= $receipt_id ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">🗑 Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>