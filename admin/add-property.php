<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/auth.php';
require_once '../config/database.php';


/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function old($key, $default = '')
{
    return e($_POST[$key] ?? $default);
}


/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/

$locations = [
    'Enugu',
    'Abuja',
    'Asaba'
];

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
| Form Variables
|--------------------------------------------------------------------------
*/

$errors = [];

$features = $_POST['features'] ?? [''];

$landmarks = $_POST['landmarks'] ?? [''];

$documents = $_POST['documents'] ?? [''];

$pricing = $_POST['pricing'] ?? [
    [
        'plot_size' => '',
        'actual_price' => '',
        'presale_price' => ''
    ]
];


/*
|--------------------------------------------------------------------------
| Process Form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');

    $location = $_POST['location'] ?? '';

    $category = $_POST['category'] ?? '';

    $description = trim($_POST['description'] ?? '');

    $address = trim($_POST['address'] ?? '');

    $youtubeUrl = trim($_POST['youtube_url'] ?? '');

    $status = $_POST['status'] ?? 'Available';

    $featured = isset($_POST['featured']) ? 1 : 0;


    /*
    |--------------------------------------------------------------------------
    | Basic Validation
    |--------------------------------------------------------------------------
    */

    if ($title === '') {
        $errors[] = 'Property or estate name is required.';
    }

    if (!in_array($location, $locations, true)) {
        $errors[] = 'Invalid location selected.';
    }

    if (!in_array($category, $categories, true)) {
        $errors[] = 'Invalid category selected.';
    }

    if (!in_array($status, $statuses, true)) {
        $errors[] = 'Invalid status selected.';
    }


    /*
    |--------------------------------------------------------------------------
    | YouTube Validation
    |--------------------------------------------------------------------------
    */

    if ($youtubeUrl !== '') {

        if (!filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid YouTube URL.';
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Clean Features
    |--------------------------------------------------------------------------
    */

    $cleanFeatures = [];

    foreach ($features as $feature) {

        $feature = trim($feature);

        if ($feature !== '') {
            $cleanFeatures[] = $feature;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Clean Landmarks
    |--------------------------------------------------------------------------
    */

    $cleanLandmarks = [];

    foreach ($landmarks as $landmark) {

        $landmark = trim($landmark);

        if ($landmark !== '') {
            $cleanLandmarks[] = $landmark;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Clean Documents
    |--------------------------------------------------------------------------
    */

    $cleanDocuments = [];

    foreach ($documents as $document) {

        $document = trim($document);

        if ($document !== '') {
            $cleanDocuments[] = $document;
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Clean Pricing
    |--------------------------------------------------------------------------
    */

    $cleanPricing = [];

    foreach ($pricing as $row) {

        $plotSize = trim($row['plot_size'] ?? '');

        $actualPrice = trim($row['actual_price'] ?? '');

        $presalePrice = trim($row['presale_price'] ?? '');


        /*
         * Completely empty pricing row
         */

        if (
            $plotSize === '' &&
            $actualPrice === '' &&
            $presalePrice === ''
        ) {
            continue;
        }


        if ($plotSize === '') {
            $errors[] = 'Every pricing option must have a plot size.';
            continue;
        }


        /*
         * Convert prices
         * Allows entries such as:
         * 35000000
         * 35,000,000
         */

        $actualPriceClean = str_replace(
            [',', '₦', ' '],
            '',
            $actualPrice
        );

        $presalePriceClean = str_replace(
            [',', '₦', ' '],
            '',
            $presalePrice
        );


        $actualPriceValue = null;

        $presalePriceValue = null;


        if ($actualPriceClean !== '') {

            if (
                !is_numeric($actualPriceClean) ||
                (float)$actualPriceClean < 0
            ) {
                $errors[] =
                    "Invalid actual price for {$plotSize}.";
            } else {
                $actualPriceValue = (float)$actualPriceClean;
            }

        }


        if ($presalePriceClean !== '') {

            if (
                !is_numeric($presalePriceClean) ||
                (float)$presalePriceClean < 0
            ) {
                $errors[] =
                    "Invalid pre-sale price for {$plotSize}.";
            } else {
                $presalePriceValue = (float)$presalePriceClean;
            }

        }


        if (
            $actualPriceValue === null &&
            $presalePriceValue === null
        ) {
            $errors[] =
                "Enter at least one price for {$plotSize}.";
        }


        $cleanPricing[] = [
            'plot_size' => $plotSize,
            'actual_price' => $actualPriceValue,
            'presale_price' => $presalePriceValue
        ];

    }


    if (empty($cleanPricing)) {
        $errors[] = 'Add at least one pricing option.';
    }


    /*
    |--------------------------------------------------------------------------
    | Image Validation
    |--------------------------------------------------------------------------
    */

    $uploadedImages = [];

    $uploadDir = __DIR__ . '/../uploads/properties/';


    if (!is_dir($uploadDir)) {

        if (!mkdir($uploadDir, 0755, true)) {
            $errors[] = 'Unable to create image upload directory.';
        }

    }


    if (
        isset($_FILES['images']) &&
        isset($_FILES['images']['name']) &&
        is_array($_FILES['images']['name'])
    ) {

        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];


        $maxFileSize = 5 * 1024 * 1024;

        $finfo = new finfo(FILEINFO_MIME_TYPE);


        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {

            $error = $_FILES['images']['error'][$index];

            $originalName =
                $_FILES['images']['name'][$index];

            $fileSize =
                $_FILES['images']['size'][$index];


            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }


            if ($error !== UPLOAD_ERR_OK) {

                $errors[] =
                    "There was a problem uploading {$originalName}.";

                continue;
            }


            if ($fileSize > $maxFileSize) {

                $errors[] =
                    "{$originalName} is larger than 5MB.";

                continue;
            }


            $mimeType = $finfo->file($tmpName);


            if (!in_array($mimeType, $allowedMimeTypes, true)) {

                $errors[] =
                    "{$originalName} is not a supported image type.";

                continue;
            }


            /*
             * Determine extension from verified MIME type
             */

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];


            $extension = $extensions[$mimeType];


            /*
             * Random filename
             */

            $filename =
                bin2hex(random_bytes(16))
                . '.'
                . $extension;


            $destination =
                $uploadDir . $filename;


            if (
                !move_uploaded_file(
                    $tmpName,
                    $destination
                )
            ) {

                $errors[] =
                    "Unable to save {$originalName}.";

                continue;
            }


            $uploadedImages[] = [
                'filename' => $filename
            ];

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Database Transaction
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();


            /*
             * Insert Property
             *
             * We keep the old columns temporarily
             * for compatibility.
             */

            $propertyStmt = $pdo->prepare("
                INSERT INTO properties
                (
                    title,
                    location,
                    category,
                    description,
                    price,
                    price_negotiable,
                    land_size,
                    land_type,
                    property_use,
                    title_document,
                    plot_size,
                    address,
                    youtube_url,
                    status,
                    featured
                )
                VALUES
                (
                    :title,
                    :location,
                    :category,
                    :description,
                    :price,
                    :price_negotiable,
                    :land_size,
                    :land_type,
                    :property_use,
                    :title_document,
                    :plot_size,
                    :address,
                    :youtube_url,
                    :status,
                    :featured
                )
            ");


            /*
             * Compatibility values
             *
             * These old fields will eventually be removed.
             */

            $firstPrice =
                $cleanPricing[0]['actual_price']
                ?? $cleanPricing[0]['presale_price']
                ?? 0;

            $firstPlotSize =
                $cleanPricing[0]['plot_size'];


            $propertyStmt->execute([

                ':title' =>
                    $title,

                ':location' =>
                    $location,

                ':category' =>
                    $category,

                ':description' =>
                    $description !== ''
                        ? $description
                        : null,

                ':price' =>
                    $firstPrice,

                ':price_negotiable' =>
                    0,

                ':land_size' =>
                    $firstPlotSize,

                ':land_type' =>
                    null,

                ':property_use' =>
                    null,

                ':title_document' =>
                    !empty($cleanDocuments)
                        ? implode(', ', $cleanDocuments)
                        : null,

                ':plot_size' =>
                    $firstPlotSize,

                ':address' =>
                    $address !== ''
                        ? $address
                        : null,

                ':youtube_url' =>
                    $youtubeUrl !== ''
                        ? $youtubeUrl
                        : null,

                ':status' =>
                    $status,

                ':featured' =>
                    $featured

            ]);


            $propertyId =
                (int)$pdo->lastInsertId();


            /*
             |--------------------------------------------------------------------------
             | Pricing
             |--------------------------------------------------------------------------
             */

            $pricingStmt = $pdo->prepare("
                INSERT INTO property_pricing
                (
                    property_id,
                    plot_size,
                    actual_price,
                    presale_price
                )
                VALUES
                (
                    :property_id,
                    :plot_size,
                    :actual_price,
                    :presale_price
                )
            ");


            foreach ($cleanPricing as $priceRow) {

                $pricingStmt->execute([

                    ':property_id' =>
                        $propertyId,

                    ':plot_size' =>
                        $priceRow['plot_size'],

                    ':actual_price' =>
                        $priceRow['actual_price'],

                    ':presale_price' =>
                        $priceRow['presale_price']

                ]);

            }


            /*
             |--------------------------------------------------------------------------
             | Features
             |--------------------------------------------------------------------------
             */

            $featureStmt = $pdo->prepare("
                INSERT INTO property_features
                (
                    property_id,
                    feature,
                    sort_order
                )
                VALUES
                (
                    :property_id,
                    :feature,
                    :sort_order
                )
            ");


            foreach ($cleanFeatures as $index => $feature) {

                $featureStmt->execute([

                    ':property_id' =>
                        $propertyId,

                    ':feature' =>
                        $feature,

                    ':sort_order' =>
                        $index

                ]);

            }


            /*
             |--------------------------------------------------------------------------
             | Landmarks / Highlights
             |--------------------------------------------------------------------------
             */

            $landmarkStmt = $pdo->prepare("
                INSERT INTO property_landmarks
                (
                    property_id,
                    description,
                    sort_order
                )
                VALUES
                (
                    :property_id,
                    :description,
                    :sort_order
                )
            ");


            foreach ($cleanLandmarks as $index => $landmark) {

                $landmarkStmt->execute([

                    ':property_id' =>
                        $propertyId,

                    ':description' =>
                        $landmark,

                    ':sort_order' =>
                        $index

                ]);

            }


            /*
             |--------------------------------------------------------------------------
             | Documents
             |--------------------------------------------------------------------------
             */

            $documentStmt = $pdo->prepare("
                INSERT INTO property_documents
                (
                    property_id,
                    document_name,
                    sort_order
                )
                VALUES
                (
                    :property_id,
                    :document_name,
                    :sort_order
                )
            ");


            foreach ($cleanDocuments as $index => $document) {

                $documentStmt->execute([

                    ':property_id' =>
                        $propertyId,

                    ':document_name' =>
                        $document,

                    ':sort_order' =>
                        $index

                ]);

            }


            /*
             |--------------------------------------------------------------------------
             | Images
             |--------------------------------------------------------------------------
             */

            if (!empty($uploadedImages)) {

                $imageStmt = $pdo->prepare("
                    INSERT INTO property_images
                    (
                        property_id,
                        image_path,
                        is_main
                    )
                    VALUES
                    (
                        :property_id,
                        :image_path,
                        :is_main
                    )
                ");


                foreach (
                    $uploadedImages
                    as $index => $image
                ) {

                    $imageStmt->execute([

                        ':property_id' =>
                            $propertyId,

                        ':image_path' =>
                            'uploads/properties/'
                            . $image['filename'],

                        ':is_main' =>
                            $index === 0 ? 1 : 0

                    ]);

                }

            }


            /*
             |--------------------------------------------------------------------------
             | Finish Transaction
             |--------------------------------------------------------------------------
             */

            $pdo->commit();


            /*
             * Redirect to manage page.
             *
             * We will build this page next.
             */

            header(
                'Location: properties.php?added=1'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            /*
             * Delete uploaded files if database
             * operation failed.
             */

            foreach ($uploadedImages as $image) {

                $file =
                    $uploadDir
                    . $image['filename'];

                if (is_file($file)) {
                    unlink($file);
                }

            }


            $errors[] =
                'The property could not be saved. Please try again.';

        }

    }


    /*
    * If validation failed before transaction,
    * remove uploaded files.
    */

    if (!empty($errors) && !empty($uploadedImages)) {

        foreach ($uploadedImages as $image) {

            $file =
                $uploadDir
                . $image['filename'];

            if (is_file($file)) {
                unlink($file);
            }

        }

    }

}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Add Property | The Reality Realtor
    </title>

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
            background: var(--light);
            color: var(--black);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }
h1, h2, h3 {
  font-family: 'Playfair Display', serif;
  line-height: 1.2;
}
        header {
            background: #c90000;
            border-bottom: 1px solid #eeeeee;
            padding: 16px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
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

        .back-link {
            text-decoration: none;
            color: var(--white);
            font-size: 12px;
            font-weight: 700;
        }

        .container {
            width: 92%;
            max-width: 900px;
            margin: 30px auto 60px;
        }

        .page-title {
            margin-bottom: 25px;
        }

        .page-title h1 {
            margin: 0 0 5px;
            font-size: 26px;
        }

        .page-title p {
            margin: 0;
            color: var(--gray);
            font-size: 12px;
        }

        .form-section {
            background: var(--white);
            border: 1px solid #eeeeee;
            border-radius: 7px;
            padding: 22px;
            margin-bottom: 18px;
        }

        .form-section h2 {
            margin: 0 0 18px;
            font-size: 17px;
        }

        .form-section h2::after {
            content: "";
            display: block;
            width: 35px;
            height: 2px;
            background: var(--red);
            margin-top: 6px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #dddddd;
            border-radius: 4px;
            padding: 11px;
            font-family: inherit;
            font-size: 13px;
            color: var(--black);
            background: var(--white);
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--red);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .repeat-row {
            display: flex;
            gap: 8px;
            margin-bottom: 9px;
        }

        .repeat-row input {
            flex: 1;
        }

        .remove-btn {
            border: none;
            background: #eeeeee;
            color: var(--black);
            padding: 0 13px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 700;
        }

        .remove-btn:hover {
            background: #dddddd;
        }

        .add-btn {
            border: 1px solid var(--red);
            color: var(--red);
            background: var(--white);
            padding: 9px 13px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        .add-btn:hover {
            background: #fff5f6;
        }

        .pricing-header,
        .pricing-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 40px;
            gap: 8px;
            align-items: center;
        }

        .pricing-header {
            margin-bottom: 7px;
            color: var(--gray);
            font-size: 10px;
            font-weight: 700;
        }

        .pricing-row {
            margin-bottom: 8px;
        }

        .pricing-row input {
            min-width: 0;
        }

        .remove-price {
            width: 40px;
            height: 40px;
            border: none;
            background: #eeeeee;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 5px;
        }

        .checkbox-row input {
            width: auto;
        }

        .checkbox-row label {
            margin: 0;
        }

        .error-box {
            background: #fff0f0;
            border: 1px solid #e5aab4;
            color: var(--red-dark);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-box ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }

        .submit-btn {
            width: 100%;
            border: none;
            background: var(--red);
            color: var(--white);
            padding: 14px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
        }

        .submit-btn:hover {
            background: var(--red-dark);
        }

        .help-text {
            color: var(--gray);
            font-size: 10px;
            margin-top: 5px;
        }

        @media (max-width: 650px) {

            body {
                font-size: 13px;
            }

            .container {
                width: 92%;
                margin-top: 22px;
            }

            .page-title h1 {
                font-size: 22px;
            }

            .form-section {
                padding: 17px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .full {
                grid-column: auto;
            }

            .pricing-header {
                display: none;
            }

            .pricing-row {
                grid-template-columns: 1fr;
                padding: 12px;
                border: 1px solid #eeeeee;
                border-radius: 5px;
                margin-bottom: 10px;
            }

            .remove-price {
                width: 100%;
            }

            .repeat-row {
                align-items: stretch;
            }

            .remove-btn {
                padding: 0 10px;
            }

        }

    </style>

</head>

<body>


<header>

    <a href="dashboard.php" class="brand">
         <img src="/images/logo1.png" alt="The Reality Realtor">
    </a>

    <a href="dashboard.php" class="back-link">
        ← Dashboard
    </a>

</header>


<main class="container">


    <div class="page-title">

        <h1>
            Add Property
        </h1>

        <p>
            Enter the property information from the client's flyer.
        </p>

    </div>


    <?php if (!empty($errors)): ?>

        <div class="error-box">

            <strong>
                Please correct the following:
            </strong>

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?= e($error) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        enctype="multipart/form-data"
    >

<input
    type="hidden"
    name="csrf_token"
    value="<?= e(csrf_token()) ?>"
>
        <!-- =====================================================
             PROPERTY INFORMATION
        ====================================================== -->

        <section class="form-section">

            <h2>
                Property Information
            </h2>

            <div class="form-grid">


                <div class="form-group full">

                    <label for="title">
                        Estate / Property Name *
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?= old('title') ?>"
                        placeholder="e.g. Sports Island Estate"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="location">
                        Location *
                    </label>

                    <select
                        id="location"
                        name="location"
                        required
                    >

                        <option value="">
                            Select location
                        </option>

                        <?php foreach ($locations as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= ($_POST['location'] ?? '') === $item
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="category">
                        Category *
                    </label>

                    <select
                        id="category"
                        name="category"
                        required
                    >

                        <option value="">
                            Select category
                        </option>

                        <?php foreach ($categories as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= ($_POST['category'] ?? '') === $item
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group full">

                    <label for="address">
                        Area / Address
                    </label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="<?= old('address') ?>"
                        placeholder="e.g. Centenary City, Enugu"
                    >

                </div>


                <div class="form-group full">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the estate or property..."
                    ><?= old('description') ?></textarea>

                </div>

            </div>

        </section>


        <!-- =====================================================
             ESTATE FEATURES
        ====================================================== -->

        <section class="form-section">

            <h2>
                Estate Features
            </h2>

            <div id="featuresContainer">

                <?php foreach ($features as $feature): ?>

                    <div class="repeat-row">

                        <input
                            type="text"
                            name="features[]"
                            value="<?= e($feature) ?>"
                            placeholder="e.g. Perimeter fencing"
                        >

                        <button
                            type="button"
                            class="remove-btn"
                            onclick="removeRow(this)"
                        >
                            ×
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

            <button
                type="button"
                class="add-btn"
                onclick="addFeature()"
            >
                + Add Feature
            </button>

        </section>


        <!-- =====================================================
             HIGHLIGHTS / LANDMARKS
        ====================================================== -->

        <section class="form-section">

            <h2>
                Highlights / Nearby Landmarks
            </h2>

            <div id="landmarksContainer">

                <?php foreach ($landmarks as $landmark): ?>

                    <div class="repeat-row">

                        <input
                            type="text"
                            name="landmarks[]"
                            value="<?= e($landmark) ?>"
                            placeholder="e.g. 3 mins drive from Nike Lake Resort"
                        >

                        <button
                            type="button"
                            class="remove-btn"
                            onclick="removeRow(this)"
                        >
                            ×
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>

            <button
                type="button"
                class="add-btn"
                onclick="addLandmark()"
            >
                + Add Highlight / Landmark
            </button>

        </section>


        <!-- =====================================================
             PRICING
        ====================================================== -->

        <section class="form-section">

            <h2>
                Pricing Options
            </h2>

            <p class="help-text">
                Add as many plot sizes as the flyer provides.
                Leave a price blank when the flyer does not provide it.
            </p>

            <br>


            <div class="pricing-header">

                <span>
                    Plot Size
                </span>

                <span>
                    Actual Price
                </span>

                <span>
                    Pre-sale Price
                </span>

                <span></span>

            </div>


            <div id="pricingContainer">

                <?php foreach ($pricing as $index => $row): ?>

                    <div class="pricing-row">

                        <input
                            type="text"
                            name="pricing[<?= $index ?>][plot_size]"
                            value="<?= e($row['plot_size'] ?? '') ?>"
                            placeholder="500 sqm"
                        >

                        <input
                            type="text"
                            name="pricing[<?= $index ?>][actual_price]"
                            value="<?= e($row['actual_price'] ?? '') ?>"
                            placeholder="35000000"
                        >

                        <input
                            type="text"
                            name="pricing[<?= $index ?>][presale_price]"
                            value="<?= e($row['presale_price'] ?? '') ?>"
                            placeholder="28000000"
                        >

                        <button
                            type="button"
                            class="remove-price"
                            onclick="removePrice(this)"
                        >
                            ×
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>


            <br>

            <button
                type="button"
                class="add-btn"
                onclick="addPricing()"
            >
                + Add Pricing Option
            </button>

        </section>


        <!-- =====================================================
             DOCUMENTATION
        ====================================================== -->

        <section class="form-section">

            <h2>
                Title / Documentation
            </h2>

            <div id="documentsContainer">

                <?php foreach ($documents as $document): ?>

                    <div class="repeat-row">

                        <input
                            type="text"
                            name="documents[]"
                            value="<?= e($document) ?>"
                            placeholder="e.g. Certificate of Occupancy"
                        >

                        <button
                            type="button"
                            class="remove-btn"
                            onclick="removeRow(this)"
                        >
                            ×
                        </button>

                    </div>

                <?php endforeach; ?>

            </div>


            <button
                type="button"
                class="add-btn"
                onclick="addDocument()"
            >
                + Add Document
            </button>

        </section>


        <!-- =====================================================
             MEDIA
        ====================================================== -->

        <section class="form-section">

            <h2>
                Property Media
            </h2>


            <div class="form-group">

                <label for="images">
                    Property Images
                </label>

                <input
                    type="file"
                    id="images"
                    name="images[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                >

                <p class="help-text">
                    JPEG, PNG or WebP. Maximum 5MB per image.
                </p>

            </div>


            <div class="form-group">

                <label for="youtube_url">
                    YouTube Video URL
                </label>

                <input
                    type="url"
                    id="youtube_url"
                    name="youtube_url"
                    value="<?= old('youtube_url') ?>"
                    placeholder="https://www.youtube.com/watch?v=..."
                >

                <p class="help-text">
                    Enter the YouTube link. Do not upload video files.
                </p>

            </div>

        </section>


        <!-- =====================================================
             STATUS
        ====================================================== -->

        <section class="form-section">

            <h2>
                Listing Status
            </h2>


            <div class="form-grid">


                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <?php foreach ($statuses as $item): ?>

                            <option
                                value="<?= e($item) ?>"
                                <?= ($_POST['status'] ?? 'Available') === $item
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($item) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Featured Property
                    </label>

                    <div class="checkbox-row">

                        <input
                            type="checkbox"
                            id="featured"
                            name="featured"
                            value="1"
                            <?= isset($_POST['featured'])
                                ? 'checked'
                                : ''
                            ?>
                        >

                        <label for="featured">
                            Show as featured
                        </label>

                    </div>

                </div>

            </div>

        </section>


        <!-- =====================================================
             SUBMIT
        ====================================================== -->

        <button
            type="submit"
            class="submit-btn"
        >
            Publish Property
        </button>


    </form>

</main>


<script>

/*
|--------------------------------------------------------------------------
| Remove Repeating Row
|--------------------------------------------------------------------------
*/

function removeRow(button) {

    const row = button.parentElement;

    const container = row.parentElement;

    /*
     * Keep at least one row.
     */

    if (container.children.length > 1) {
        row.remove();
    } else {
        row.querySelector('input').value = '';
    }

}


/*
|--------------------------------------------------------------------------
| Features
|--------------------------------------------------------------------------
*/

function addFeature() {

    const container =
        document.getElementById('featuresContainer');

    const row =
        document.createElement('div');

    row.className = 'repeat-row';

    row.innerHTML = `
        <input
            type="text"
            name="features[]"
            placeholder="e.g. Estate security"
        >

        <button
            type="button"
            class="remove-btn"
            onclick="removeRow(this)"
        >
            ×
        </button>
    `;

    container.appendChild(row);

}


/*
|--------------------------------------------------------------------------
| Landmarks
|--------------------------------------------------------------------------
*/

function addLandmark() {

    const container =
        document.getElementById('landmarksContainer');

    const row =
        document.createElement('div');

    row.className = 'repeat-row';

    row.innerHTML = `
        <input
            type="text"
            name="landmarks[]"
            placeholder="e.g. 3 mins drive from Nike Lake Resort"
        >

        <button
            type="button"
            class="remove-btn"
            onclick="removeRow(this)"
        >
            ×
        </button>
    `;

    container.appendChild(row);

}


/*
|--------------------------------------------------------------------------
| Documents
|--------------------------------------------------------------------------
*/

function addDocument() {

    const container =
        document.getElementById('documentsContainer');

    const row =
        document.createElement('div');

    row.className = 'repeat-row';

    row.innerHTML = `
        <input
            type="text"
            name="documents[]"
            placeholder="e.g. Deed of Assignment"
        >

        <button
            type="button"
            class="remove-btn"
            onclick="removeRow(this)"
        >
            ×
        </button>
    `;

    container.appendChild(row);

}


/*
|--------------------------------------------------------------------------
| Pricing
|--------------------------------------------------------------------------
*/

let pricingIndex =
    <?= count($pricing) ?>;


function addPricing() {

    const container =
        document.getElementById('pricingContainer');

    const row =
        document.createElement('div');

    row.className = 'pricing-row';

    row.innerHTML = `

        <input
            type="text"
            name="pricing[${pricingIndex}][plot_size]"
            placeholder="500 sqm"
        >

        <input
            type="text"
            name="pricing[${pricingIndex}][actual_price]"
            placeholder="35000000"
        >

        <input
            type="text"
            name="pricing[${pricingIndex}][presale_price]"
            placeholder="28000000"
        >

        <button
            type="button"
            class="remove-price"
            onclick="removePrice(this)"
        >
            ×
        </button>

    `;

    container.appendChild(row);

    pricingIndex++;

}


function removePrice(button) {

    const row = button.parentElement;

    const container = row.parentElement;

    if (container.children.length > 1) {
        row.remove();
    } else {

        row
            .querySelectorAll('input')
            .forEach(function(input) {
                input.value = '';
            });

    }

}

</script>

</body>
</html>