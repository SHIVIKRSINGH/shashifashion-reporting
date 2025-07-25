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

        echo "<div class='alert alert-success text-center'>📸 Photos uploaded successfully.</div>";
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Purchase Bill Images</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9f9f9;
        }

        .img-preview {
            object-fit: cover;
            height: 180px;
        }

        @media (max-width: 576px) {
            .img-preview {
                height: 140px;
            }
        }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">🧾 Upload Bill Photos</h4>
            <a href="purchase.php" class="btn btn-sm btn-outline-secondary">⬅ Back</a>
        </div>
        <p class="text-muted mb-4">Receipt ID: <strong><?= htmlspecialchars($receipt_id) ?></strong></p>

        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <label for="photoUpload" class="form-label">📷 Choose or Capture Photos</label>
                <input
                    type="file"
                    name="photos[]"
                    id="photoUpload"
                    class="form-control"
                    accept="image/*"
                    capture="environment"
                    multiple
                    required>
            </div>
            <div class="d-grid gap-2 d-md-block">
                <button type="submit" class="btn btn-success">📤 Upload Photos</button>
            </div>
        </form>

        <?php if (!empty($existingPhotos)): ?>
            <div class="row">
                <?php foreach ($existingPhotos as $photo): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                        <div class="card shadow-sm h-100">
                            <img src="uploads/<?= $receipt_id ?>/<?= $photo ?>" class="card-img-top img-preview" alt="Photo">
                            <div class="card-body text-center">
                                <form method="POST" action="delete_photo.php" onsubmit="return confirm('Delete this photo?')">
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