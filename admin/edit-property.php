<?php

require_once '../config/auth.php';
require_once '../config/database.php';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: properties.php');
    exit;
}

$locations = ['Enugu', 'Abuja', 'Asaba'];

$categories = [
    'Buy and Build',
    'Land Banking',
    'Luxury Homes',
    'Available Properties',
    'Distress Sales'
];

$statuses = [
    'Available',
    'Sold',
    'Reserved'
];

/*
|--------------------------------------------------------------------------
| Load property
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM properties
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$property = $stmt->fetch();

if (!$property) {
    http_response_code(404);
    exit('Property not found.');
}


/*
|--------------------------------------------------------------------------
| Handle POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $title = trim($_POST['title'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $youtubeUrl = trim($_POST['youtube_url'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');

    $featured = isset($_POST['featured']) ? 1 : 0;

    if ($title === '') {
        exit('Property title is required.');
    }

    if (!in_array($location, $locations, true)) {
        exit('Invalid location.');
    }

    if (!in_array($category, $categories, true)) {
        exit('Invalid category.');
    }

    if (!in_array($status, $statuses, true)) {
        exit('Invalid status.');
    }


    /*
    |--------------------------------------------------------------------------
    | YouTube validation
    |--------------------------------------------------------------------------
    */

    if ($youtubeUrl !== '') {

        $youtubeHost = strtolower(
            parse_url($youtubeUrl, PHP_URL_HOST) ?? ''
        );

        $allowedHosts = [
            'youtube.com',
            'www.youtube.com',
            'youtu.be',
            'www.youtu.be',
            'm.youtube.com'
        ];

        if (!in_array($youtubeHost, $allowedHosts, true)) {
            exit('Please enter a valid YouTube URL.');
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */

    $pricing = [];

    $plotSizes = $_POST['plot_size'] ?? [];
    $actualPrices = $_POST['actual_price'] ?? [];
    $presalePrices = $_POST['presale_price'] ?? [];

    if (is_array($plotSizes)) {

        foreach ($plotSizes as $index => $plotSize) {

            $plotSize = trim($plotSize);

            $actual = trim($actualPrices[$index] ?? '');
            $presale = trim($presalePrices[$index] ?? '');

            if ($plotSize === '') {
                continue;
            }

            $actualValue = null;
            $presaleValue = null;

            if ($actual !== '') {
                $actualValue = (float)str_replace(',', '', $actual);

                if ($actualValue < 0) {
                    exit('Invalid actual price.');
                }
            }

            if ($presale !== '') {
                $presaleValue = (float)str_replace(',', '', $presale);

                if ($presaleValue < 0) {
                    exit('Invalid pre-sale price.');
                }
            }

            $pricing[] = [
                'plot_size' => $plotSize,
                'actual_price' => $actualValue,
                'presale_price' => $presaleValue
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */

    $features = [];

    $featureInputs = $_POST['feature'] ?? [];

    if (is_array($featureInputs)) {

        foreach ($featureInputs as $feature) {

            $feature = trim($feature);

            if ($feature !== '') {
                $features[] = $feature;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Landmarks
    |--------------------------------------------------------------------------
    */

    $landmarks = [];

    $landmarkInputs = $_POST['landmark'] ?? [];

    if (is_array($landmarkInputs)) {

        foreach ($landmarkInputs as $landmark) {

            $landmark = trim($landmark);

            if ($landmark !== '') {
                $landmarks[] = $landmark;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */

    $documents = [];

    $documentInputs = $_POST['document'] ?? [];

    if (is_array($documentInputs)) {

        foreach ($documentInputs as $document) {

            $document = trim($document);

            if ($document !== '') {
                $documents[] = $document;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Images to delete
    |--------------------------------------------------------------------------
    */

    $deleteImages = $_POST['delete_image'] ?? [];

    if (!is_array($deleteImages)) {
        $deleteImages = [];
    }

    $deleteImages = array_values(
        array_filter(
            array_map('intval', $deleteImages),
            fn($value) => $value > 0
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Main image
    |--------------------------------------------------------------------------
    */

    $mainImageId = filter_input(
        INPUT_POST,
        'main_image',
        FILTER_VALIDATE_INT
    );


    /*
    |--------------------------------------------------------------------------
    | New image uploads
    |--------------------------------------------------------------------------
    */

    $newImages = [];

    $uploadDir = dirname(__DIR__) . '/uploads/properties/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (
        isset($_FILES['images']) &&
        isset($_FILES['images']['name']) &&
        is_array($_FILES['images']['name'])
    ) {

        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        foreach ($_FILES['images']['name'] as $index => $originalName) {

            if ($_FILES['images']['error'][$index] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($_FILES['images']['error'][$index] !== UPLOAD_ERR_OK) {
                exit('One of the images could not be uploaded.');
            }

            if ($_FILES['images']['size'][$index] > 5 * 1024 * 1024) {
                exit('Each image must be 5MB or smaller.');
            }

            $tmpName = $_FILES['images']['tmp_name'][$index];

            $mime = $finfo->file($tmpName);

            if (!isset($allowedMimeTypes[$mime])) {
                exit('Only JPG, PNG and WEBP images are allowed.');
            }

            $extension = $allowedMimeTypes[$mime];

            $filename = bin2hex(random_bytes(16)) . '.' . $extension;

            $destination = $uploadDir . $filename;

            if (!move_uploaded_file($tmpName, $destination)) {
                exit('Could not save uploaded image.');
            }

            $newImages[] = 'uploads/properties/' . $filename;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save everything
    |--------------------------------------------------------------------------
    */

    try {

        $pdo->beginTransaction();


        /*
        | Main property
        */

        $stmt = $pdo->prepare("
            UPDATE properties
            SET
                title = ?,
                location = ?,
                category = ?,
                address = ?,
                description = ?,
                youtube_url = ?,
                status = ?,
                featured = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $title,
            $location,
            $category,
            $address,
            $description,
            $youtubeUrl !== '' ? $youtubeUrl : null,
            $status,
            $featured,
            $id
        ]);


        /*
        | Pricing
        */

        $pdo->prepare("
            DELETE FROM property_pricing
            WHERE property_id = ?
        ")->execute([$id]);

        $pricingStmt = $pdo->prepare("
            INSERT INTO property_pricing
            (
                property_id,
                plot_size,
                actual_price,
                presale_price
            )
            VALUES (?, ?, ?, ?)
        ");

        foreach ($pricing as $item) {

            $pricingStmt->execute([
                $id,
                $item['plot_size'],
                $item['actual_price'],
                $item['presale_price']
            ]);
        }


        /*
        | Features
        */

        $pdo->prepare("
            DELETE FROM property_features
            WHERE property_id = ?
        ")->execute([$id]);

        $featureStmt = $pdo->prepare("
            INSERT INTO property_features
            (
                property_id,
                feature,
                sort_order
            )
            VALUES (?, ?, ?)
        ");

        foreach ($features as $index => $feature) {

            $featureStmt->execute([
                $id,
                $feature,
                $index
            ]);
        }


        /*
        | Landmarks
        */

        $pdo->prepare("
            DELETE FROM property_landmarks
            WHERE property_id = ?
        ")->execute([$id]);

        $landmarkStmt = $pdo->prepare("
            INSERT INTO property_landmarks
            (
                property_id,
                description,
                sort_order
            )
            VALUES (?, ?, ?)
        ");

        foreach ($landmarks as $index => $landmark) {

            $landmarkStmt->execute([
                $id,
                $landmark,
                $index
            ]);
        }


        /*
        | Documents
        */

        $pdo->prepare("
            DELETE FROM property_documents
            WHERE property_id = ?
        ")->execute([$id]);

        $documentStmt = $pdo->prepare("
            INSERT INTO property_documents
            (
                property_id,
                document_name,
                sort_order
            )
            VALUES (?, ?, ?)
        ");

        foreach ($documents as $index => $document) {

            $documentStmt->execute([
                $id,
                $document,
                $index
            ]);
        }


        /*
        | Delete selected images
        */

        $imagesToDelete = [];

        if (!empty($deleteImages)) {

            $placeholders = implode(
                ',',
                array_fill(0, count($deleteImages), '?')
            );

            $params = array_merge([$id], $deleteImages);

            $stmt = $pdo->prepare("
                SELECT id, image_path
                FROM property_images
                WHERE property_id = ?
                AND id IN ($placeholders)
            ");

            $stmt->execute($params);

            $imagesToDelete = $stmt->fetchAll();


            $stmt = $pdo->prepare("
                DELETE FROM property_images
                WHERE property_id = ?
                AND id IN ($placeholders)
            ");

            $stmt->execute($params);
        }


        /*
        | Add new images
        */

        if (!empty($newImages)) {

            $imageStmt = $pdo->prepare("
                INSERT INTO property_images
                (
                    property_id,
                    image_path,
                    is_main
                )
                VALUES (?, ?, 0)
            ");

            foreach ($newImages as $imagePath) {

                $imageStmt->execute([
                    $id,
                    $imagePath
                ]);
            }
        }


        /*
        | Set main image
        */

        if ($mainImageId) {

            $pdo->prepare("
                UPDATE property_images
                SET is_main = 0
                WHERE property_id = ?
            ")->execute([$id]);

            $stmt = $pdo->prepare("
                UPDATE property_images
                SET is_main = 1
                WHERE id = ?
                AND property_id = ?
            ");

            $stmt->execute([
                $mainImageId,
                $id
            ]);

        } else {

            /*
            | Make sure at least one image is main
            */

            $stmt = $pdo->prepare("
                SELECT id
                FROM property_images
                WHERE property_id = ?
                ORDER BY id ASC
                LIMIT 1
            ");

            $stmt->execute([$id]);

            $firstImage = $stmt->fetch();

            if ($firstImage) {

                $pdo->prepare("
                    UPDATE property_images
                    SET is_main = 1
                    WHERE id = ?
                ")->execute([$firstImage['id']]);
            }
        }


        /*
        | Compatibility fields
        */

        $firstPricing = $pricing[0] ?? null;

        $compatPrice = $firstPricing['actual_price'] ?? 0;
        $compatLandSize = $firstPricing['plot_size'] ?? null;

        $stmt = $pdo->prepare("
            UPDATE properties
            SET
                price = ?,
                land_size = ?,
                plot_size = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $compatPrice,
            $compatLandSize,
            $compatLandSize,
            $id
        ]);


        $pdo->commit();


        /*
        | Delete image files after successful DB commit
        */

        foreach ($imagesToDelete as $image) {

            $file = dirname(__DIR__) . '/' . $image['image_path'];

            if (is_file($file)) {
                @unlink($file);
            }
        }


        header('Location: properties.php?updated=1');
        exit;


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        foreach ($newImages as $imagePath) {

            $file = dirname(__DIR__) . '/' . $imagePath;

            if (is_file($file)) {
                @unlink($file);
            }
        }

        http_response_code(500);

        exit('Could not save changes.');
    }
}


/*
|--------------------------------------------------------------------------
| Load child data for form
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM property_pricing
    WHERE property_id = ?
    ORDER BY id ASC
");

$stmt->execute([$id]);

$pricing = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT *
    FROM property_features
    WHERE property_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$features = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT *
    FROM property_landmarks
    WHERE property_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$landmarks = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT *
    FROM property_documents
    WHERE property_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$documents = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT *
    FROM property_images
    WHERE property_id = ?
    ORDER BY is_main DESC, id ASC
");

$stmt->execute([$id]);

$images = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Property | The Reality Realtor</title>

<style>

:root {
    --red: #C8102E;
    --red-dark: #A00D24;
    --black: #0A0A0A;
    --gray: #6B6B6B;
    --light: #F8F8F8;
    --white: #FFFFFF;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: var(--light);
    color: var(--black);
}
h1, h2, h3 {
  font-family: 'Playfair Display', serif;
  line-height: 1.2;
}
nav {
            background: #c90000;
            padding: 15px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

.brand {
    display: flex;
    align-items: center;
    font-size: 21px;
    font-weight: 700;
}

.brand img {
    height: 40px;
    width: auto;
    display: block;
}

        .nav-links {
            display: flex;
            gap: 25px;
        }

        .nav-links a {
            color: var(--white);
            font-size: 14px;
        }

        .nav-links a:hover {
            color: var(--red);
        }

.menu-toggle {
    display: none;

    background: none;
    border: none;

    color: white;
    font-size: 26px;

    cursor: pointer;
}

.container {
    width: 92%;
    max-width: 1000px;
    margin: 30px auto;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 20px;
}

h1 {
    margin-top: 0;
}

h2 {
    border-bottom: 2px solid var(--red);
    padding-bottom: 8px;
    margin-top: 0;
}

label {
    display: block;
    font-weight: bold;
    margin: 15px 0 6px;
}

input,
select,
textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font: inherit;
}

textarea {
    min-height: 130px;
    resize: vertical;
}

.row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 10px;
}

.item {
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 7px;
    margin-bottom: 12px;
}

button {
    border: 0;
    border-radius: 5px;
    padding: 10px 15px;
    cursor: pointer;
    font-weight: bold;
}

.add {
    background: var(--black);
    color: white;
}

.remove {
    background: #eee;
    color: #333;
    margin-top: 8px;
}

.save {
    background: var(--red);
    color: white;
    width: 100%;
    padding: 15px;
    font-size: 16px;
}

.save:hover {
    background: var(--red-dark);
}

.images {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.image-box {
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 7px;
}

.image-box img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 5px;
}

.small {
    font-size: 12px;
    color: var(--gray);
}

@media (max-width: 700px) {

    .row {
        grid-template-columns: 1fr;
    }

    .images {
        grid-template-columns: repeat(2, 1fr);
    }

    nav {
        position: relative;
    }

    .menu-toggle {
        display: block;
    }

    .nav-links {
        display: none;

        position: absolute;

        top: 100%;
        left: 0;
        right: 0;

        background: #c90000;

        padding: 15px 5%;

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .nav-links.open {
        display: flex;
    }

    .nav-links a {
        font-size: 13px;
    }

    .card {
        padding: 17px;
    }
}

</style>

</head>

<body>

<nav>

<a href="dashboard.php" class="brand">
     <img src="/images/logo1.png" alt="The Reality Realtor">
</a>

<button
    class="menu-toggle"
    onclick="toggleMenu()"
    aria-label="Open menu"
>
    ☰
</button>

<div class="nav-links" id="navLinks">
    <a href="dashboard.php">Dashboard</a>
    <a href="properties.php">Properties</a>
    <a href="logout.php">Logout</a>
</div>

</nav>


<div class="container">

<h1>Edit Property</h1>


<form method="POST" enctype="multipart/form-data">

<input
    type="hidden"
    name="csrf_token"
    value="<?= e(csrf_token()) ?>"
>


<div class="card">

<h2>Basic Information</h2>

<label>Property Title</label>

<input
    type="text"
    name="title"
    value="<?= e($property['title']) ?>"
    required
>


<label>Location</label>

<select name="location" required>

<?php foreach ($locations as $location): ?>

<option
    value="<?= e($location) ?>"
    <?= $property['location'] === $location ? 'selected' : '' ?>
>
    <?= e($location) ?>
</option>

<?php endforeach; ?>

</select>


<label>Category</label>

<select name="category" required>

<?php foreach ($categories as $category): ?>

<option
    value="<?= e($category) ?>"
    <?= $property['category'] === $category ? 'selected' : '' ?>
>
    <?= e($category) ?>
</option>

<?php endforeach; ?>

</select>


<label>Address</label>

<input
    type="text"
    name="address"
    value="<?= e($property['address']) ?>"
>


<label>Description</label>

<textarea name="description"><?= e($property['description']) ?></textarea>


<label>YouTube URL</label>

<input
    type="url"
    name="youtube_url"
    value="<?= e($property['youtube_url']) ?>"
    placeholder="https://www.youtube.com/watch?v=..."
>


<label>Status</label>

<select name="status">

<?php foreach ($statuses as $status): ?>

<option
    value="<?= e($status) ?>"
    <?= $property['status'] === $status ? 'selected' : '' ?>
>
    <?= e($status) ?>
</option>

<?php endforeach; ?>

</select>


<label>

<input
    type="checkbox"
    name="featured"
    value="1"
    <?= !empty($property['featured']) ? 'checked' : '' ?>
    style="width:auto;"
>

 Featured Property

</label>

</div>


<!-- PRICING -->

<div class="card">

<h2>Pricing</h2>

<div id="pricingContainer">

<?php if (!empty($pricing)): ?>

<?php foreach ($pricing as $price): ?>

<div class="item pricing-item">

<div class="row">

<div>

<label>Plot Size</label>

<input
    type="text"
    name="plot_size[]"
    value="<?= e($price['plot_size']) ?>"
    placeholder="e.g. 500 sqm"
>

</div>

<div>

<label>Actual Price</label>

<input
    type="number"
    name="actual_price[]"
    value="<?= $price['actual_price'] !== null ? e(number_format((float)$price['actual_price'], 2, '.', '')) : '' ?>"
    placeholder="6500000"
    min="0"
    step="0.01"
>

</div>

<div>

<label>Pre-sale Price</label>

<input
    type="number"
    name="presale_price[]"
    value="<?= $price['presale_price'] !== null ? e(number_format((float)$price['presale_price'], 2, '.', '')) : '' ?>"
    placeholder="28000000"
    min="0"
    step="0.01"
>

</div>

</div>

<button type="button" class="remove" onclick="this.closest('.pricing-item').remove()">
Remove
</button>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="item pricing-item">

<div class="row">

<div>
<label>Plot Size</label>
<input type="text" name="plot_size[]" placeholder="e.g. 500 sqm">
</div>

<div>
<label>Actual Price</label>
<input type="number" name="actual_price[]" placeholder="35000000">
</div>

<div>
<label>Pre-sale Price</label>
<input type="number" name="presale_price[]" placeholder="28000000">
</div>

</div>

</div>

<?php endif; ?>

</div>

<button type="button" class="add" onclick="addPricing()">
+ Add Pricing Option
</button>

</div>


<!-- FEATURES -->

<div class="card">

<h2>Estate Features</h2>

<div id="featuresContainer">

<?php foreach ($features as $feature): ?>

<div class="item feature-item">

<input
    type="text"
    name="feature[]"
    value="<?= e($feature['feature']) ?>"
>

<button
    type="button"
    class="remove"
    onclick="this.closest('.feature-item').remove()"
>
Remove
</button>

</div>

<?php endforeach; ?>

</div>

<button type="button" class="add" onclick="addFeature()">
+ Add Feature
</button>

</div>


<!-- LANDMARKS -->

<div class="card">

<h2>Highlights / Landmarks</h2>

<div id="landmarksContainer">

<?php foreach ($landmarks as $landmark): ?>

<div class="item landmark-item">

<input
    type="text"
    name="landmark[]"
    value="<?= e($landmark['description']) ?>"
>

<button
    type="button"
    class="remove"
    onclick="this.closest('.landmark-item').remove()"
>
Remove
</button>

</div>

<?php endforeach; ?>

</div>

<button type="button" class="add" onclick="addLandmark()">
+ Add Highlight
</button>

</div>


<!-- DOCUMENTS -->

<div class="card">

<h2>Verified Title Documents</h2>

<div id="documentsContainer">

<?php foreach ($documents as $document): ?>

<div class="item document-item">

<input
    type="text"
    name="document[]"
    value="<?= e($document['document_name']) ?>"
>

<button
    type="button"
    class="remove"
    onclick="this.closest('.document-item').remove()"
>
Remove
</button>

</div>

<?php endforeach; ?>

</div>

<button type="button" class="add" onclick="addDocument()">
+ Add Document
</button>

</div>


<!-- EXISTING IMAGES -->

<div class="card">

<h2>Existing Images</h2>

<?php if (!empty($images)): ?>

<div class="images">

<?php foreach ($images as $image): ?>

<div class="image-box">

<img
    src="../<?= e($image['image_path']) ?>"
    alt="Property image"
>

<p class="small">

<label style="font-weight:normal;">

<input
    type="radio"
    name="main_image"
    value="<?= e($image['id']) ?>"
    <?= !empty($image['is_main']) ? 'checked' : '' ?>
    style="width:auto;"
>

 Main Image

</label>

</p>

<label style="font-weight:normal;">

<input
    type="checkbox"
    name="delete_image[]"
    value="<?= e($image['id']) ?>"
    style="width:auto;"
>

 Delete Image

</label>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<p>No images uploaded.</p>

<?php endif; ?>

</div>


<!-- NEW IMAGES -->

<div class="card">

<h2>Add More Images</h2>

<input
    type="file"
    name="images[]"
    multiple
    accept="image/jpeg,image/png,image/webp"
>

<p class="small">
JPG, PNG or WEBP. Maximum 5MB per image.
</p>

</div>


<button type="submit" class="save">
Save Changes
</button>

</form>

</div>


<script>
  
function toggleMenu()
{
    const menu = document.getElementById('navLinks');

    menu.classList.toggle('open');
}

function addPricing()
{
    const container = document.getElementById('pricingContainer');

    const div = document.createElement('div');

    div.className = 'item pricing-item';

    div.innerHTML = `
        <div class="row">

            <div>
                <label>Plot Size</label>
                <input
                    type="text"
                    name="plot_size[]"
                    placeholder="e.g. 500 sqm"
                >
            </div>

            <div>
                <label>Actual Price</label>
                <input
                    type="number"
                    name="actual_price[]"
                    placeholder="35000000"
                >
            </div>

            <div>
                <label>Pre-sale Price</label>
                <input
                    type="number"
                    name="presale_price[]"
                    placeholder="28000000"
                >
            </div>

        </div>

        <button
            type="button"
            class="remove"
            onclick="this.closest('.pricing-item').remove()"
        >
            Remove
        </button>
    `;

    container.appendChild(div);
}


function addFeature()
{
    const container = document.getElementById('featuresContainer');

    const div = document.createElement('div');

    div.className = 'item feature-item';

    div.innerHTML = `
        <input
            type="text"
            name="feature[]"
            placeholder="Estate feature"
        >

        <button
            type="button"
            class="remove"
            onclick="this.closest('.feature-item').remove()"
        >
            Remove
        </button>
    `;

    container.appendChild(div);
}


function addLandmark()
{
    const container = document.getElementById('landmarksContainer');

    const div = document.createElement('div');

    div.className = 'item landmark-item';

    div.innerHTML = `
        <input
            type="text"
            name="landmark[]"
            placeholder="Highlight or nearby landmark"
        >

        <button
            type="button"
            class="remove"
            onclick="this.closest('.landmark-item').remove()"
        >
            Remove
        </button>
    `;

    container.appendChild(div);
}


function addDocument()
{
    const container = document.getElementById('documentsContainer');

    const div = document.createElement('div');

    div.className = 'item document-item';

    div.innerHTML = `
        <input
            type="text"
            name="document[]"
            placeholder="Title document"
        >

        <button
            type="button"
            class="remove"
            onclick="this.closest('.document-item').remove()"
        >
            Remove
        </button>
    `;

    container.appendChild(div);
}

</script>

</body>
</html>