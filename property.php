<?php

require_once 'config/database.php';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function naira($amount)
{
    if ($amount === null || $amount === '') {
        return 'Price on request';
    }

    return '₦' . number_format((float)$amount, 0);
}

function youtubeEmbedUrl($url)
{
    if (!$url) {
        return null;
    }

    $url = trim($url);

    // YouTube watch URL
    if (preg_match('/youtube\.com\/watch\?v=([A-Za-z0-9_-]+)/i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    // YouTube short URL
    if (preg_match('/youtu\.be\/([A-Za-z0-9_-]+)/i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    // YouTube embed URL
    if (preg_match('/youtube\.com\/embed\/([A-Za-z0-9_-]+)/i', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Get Property ID
|--------------------------------------------------------------------------
*/

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit('Property not found.');
}


/*
|--------------------------------------------------------------------------
| Get Property
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
| Get Images
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM property_images
    WHERE property_id = ?
    ORDER BY is_main DESC, id ASC
");

$stmt->execute([$id]);

$images = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Pricing
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


/*
|--------------------------------------------------------------------------
| Get Features
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM property_features
    WHERE property_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$features = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Landmarks / Highlights
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM property_landmarks
    WHERE property_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$landmarks = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Documents
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM property_documents
    WHERE property_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$documents = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Main Image
|--------------------------------------------------------------------------
*/

$mainImage = 'images/property-placeholder.jpg';

if (!empty($images)) {
    $mainImage = $images[0]['image_path'];
}


/*
|--------------------------------------------------------------------------
| YouTube
|--------------------------------------------------------------------------
*/

$youtubeEmbed = youtubeEmbedUrl($property['youtube_url'] ?? '');


/*
|--------------------------------------------------------------------------
| Contact
|--------------------------------------------------------------------------
*/

$phone = '08069454058';
$whatsappMessage = 'Hello, I am interested in ' . $property['title'] . ' (Property ID: ' . $property['id'] . ').';

$whatsappUrl = 'https://wa.me/234' . substr($phone, 1)
    . '?text=' . rawurlencode($whatsappMessage);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($property['title']) ?> | The Reality Realtor</title>

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
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            color: var(--black);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }
  h1, h2, h3 {
  font-family: 'Playfair Display', serif;
  line-height: 1.2;
}
        /* NAV */

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
        /* PAGE */

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 35px auto;
        }

        .back {
            display: inline-block;
            color: var(--red);
            font-weight: bold;
            margin-bottom: 20px;
        }

        .property-header {
            background: var(--white);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 5px solid var(--red);
        }

        .property-header h1 {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .location {
            color: var(--gray);
        }

        .badges {
            margin-top: 15px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            background: var(--red);
            color: var(--white);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge.black {
            background: var(--black);
        }

        /* GALLERY */

        .gallery {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
        }

        .thumbnails {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            margin-top: 12px;
        }

        .thumbnails img {
            width: 90px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .thumbnails img:hover {
            border-color: var(--red);
        }

        /* CONTENT */

        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .card {
            background: var(--white);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .card h2 {
            font-size: 21px;
            margin-bottom: 18px;
            color: var(--black);
            border-bottom: 2px solid var(--red);
            padding-bottom: 8px;
        }

        .description {
            white-space: pre-line;
            color: #333;
        }

        /* PRICING */

        .pricing-list {
            display: grid;
            gap: 15px;
        }

        .price-box {
            border: 1px solid #ddd;
            border-left: 4px solid var(--red);
            padding: 16px;
            border-radius: 6px;
        }

        .plot-size {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .actual-price {
            font-size: 20px;
            font-weight: bold;
        }

        .presale {
            color: var(--red);
            font-weight: bold;
            margin-top: 4px;
        }

        /* LISTS */

        .feature-list,
        .landmark-list,
        .document-list {
            list-style: none;
        }

        .feature-list li,
        .landmark-list li,
        .document-list li {
            padding: 9px 0 9px 25px;
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .feature-list li::before {
            content: "✓";
            color: var(--red);
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .landmark-list li::before {
            content: "•";
            color: var(--red);
            font-size: 20px;
            position: absolute;
            left: 5px;
        }

        .document-list li::before {
            content: "✓";
            color: var(--red);
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* SIDE INFO */

        .info-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-label {
            color: var(--gray);
            font-size: 13px;
        }

        .info-value {
            font-weight: bold;
        }

        /* CONTACT */

        .contact-box {
            background: var(--black);
            color: var(--white);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .contact-box h2 {
            margin-bottom: 10px;
        }

        .contact-box p {
            color: #ddd;
            margin-bottom: 18px;
        }

        .buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            display: block;
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            font-weight: bold;
        }

        .btn-red {
            background: var(--red);
            color: var(--white);
        }

        .btn-red:hover {
            background: var(--red-dark);
        }

        .btn-white {
            background: var(--white);
            color: var(--black);
        }

        /* VIDEO */

        .video-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 8px;
        }

        .video-wrapper iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* FOOTER */

        footer {
            background: var(--black);
            color: var(--white);
            text-align: center;
            padding: 25px;
            margin-top: 40px;
            font-size: 13px;
        }

        footer span {
            color: var(--red);
        }

        /* MOBILE */

        @media (max-width: 768px) {

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

            .container {
                width: 94%;
                margin: 20px auto;
            }

            .property-header {
                padding: 18px;
            }

            .property-header h1 {
                font-size: 23px;
            }

            .main-image {
                height: 300px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .card {
                padding: 18px;
            }

            .card h2 {
                font-size: 19px;
            }

            .info-value {
                font-size: 14px;
            }

            .description {
                font-size: 14px;
            }

            .price-box {
                padding: 13px;
            }

            .actual-price {
                font-size: 18px;
            }

        }

    </style>

</head>

<body>


<nav>

    <a href="index.php" class="brand">
        <img src="/images/logo1.png">
    </a>
<button
    class="menu-toggle"
    onclick="toggleMenu()"
    aria-label="Open menu"
>
    ☰
</button>
    <div class="nav-links" id="navLinks">
        <a href="index.php">Home</a>
        <a href="properties.php">Properties</a>
       
    </div>

</nav>


<div class="container">

    <a href="properties.php" class="back">
        ← Back to Properties
    </a>


    <!-- HEADER -->

    <div class="property-header">

        <h1><?= e($property['title']) ?></h1>

        <div class="location">
            📍 <?= e($property['location']) ?>

            <?php if (!empty($property['address'])): ?>
                — <?= e($property['address']) ?>
            <?php endif; ?>
        </div>

        <div class="badges">

            <span class="badge">
                <?= e($property['category']) ?>
            </span>

            <span class="badge black">
                <?= e($property['status']) ?>
            </span>

            <?php if (!empty($property['featured'])): ?>
                <span class="badge">
                    Featured
                </span>
            <?php endif; ?>

        </div>

    </div>


    <!-- GALLERY -->

    <div class="gallery">

        <img
            id="mainImage"
            src="<?= e($mainImage) ?>"
            alt="<?= e($property['title']) ?>"
            class="main-image"
        >

        <?php if (count($images) > 1): ?>

            <div class="thumbnails">

                <?php foreach ($images as $image): ?>

                    <img
                        src="<?= e($image['image_path']) ?>"
                        alt="Property image"
                        onclick="changeImage(this.src)"
                    >

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>


    <div class="grid">


        <!-- MAIN COLUMN -->

        <div>


            <!-- PRICING -->

            <?php if (!empty($pricing)): ?>

                <div class="card">

                    <h2>Pricing</h2>

                    <div class="pricing-list">

                        <?php foreach ($pricing as $price): ?>

                            <div class="price-box">

                                <div class="plot-size">
                                    <?= e($price['plot_size']) ?>
                                </div>

                                <?php if ($price['actual_price'] !== null): ?>

                                    <div class="actual-price">
                                        Actual Price:
                                        <?= naira($price['actual_price']) ?>
                                    </div>

                                <?php endif; ?>


                                <?php if ($price['presale_price'] !== null): ?>

                                    <div class="presale">
                                        Pre-sale Price:
                                        <?= naira($price['presale_price']) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- DESCRIPTION -->

            <?php if (!empty($property['description'])): ?>

                <div class="card">

                    <h2>About This Property</h2>

                    <div class="description">
                        <?= e($property['description']) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- FEATURES -->

            <?php if (!empty($features)): ?>

                <div class="card">

                    <h2>Estate Features</h2>

                    <ul class="feature-list">

                        <?php foreach ($features as $feature): ?>

                            <li>
                                <?= e($feature['feature']) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- LANDMARKS -->

            <?php if (!empty($landmarks)): ?>

                <div class="card">

                    <h2>Highlights & Landmarks</h2>

                    <ul class="landmark-list">

                        <?php foreach ($landmarks as $landmark): ?>

                            <li>
                                <?= e($landmark['description']) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- DOCUMENTS -->

            <?php if (!empty($documents)): ?>

                <div class="card">

                    <h2>Verified Title Documents</h2>

                    <ul class="document-list">

                        <?php foreach ($documents as $document): ?>

                            <li>
                                <?= e($document['document_name']) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- VIDEO -->

            <?php if ($youtubeEmbed): ?>

                <div class="card">

                    <h2>Property Video</h2>

                    <div class="video-wrapper">

                        <iframe
                            src="<?= e($youtubeEmbed) ?>"
                            title="Property video"
                            loading="lazy"
                            allowfullscreen>
                        </iframe>

                    </div>

                </div>

            <?php endif; ?>


        </div>


        <!-- SIDE COLUMN -->

        <div>


            <!-- PROPERTY INFORMATION -->

            <div class="card">

                <h2>Property Information</h2>

                <?php if (!empty($property['property_use'])): ?>

                    <div class="info-row">

                        <div class="info-label">
                            Property Use
                        </div>

                        <div class="info-value">
                            <?= e($property['property_use']) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <?php if (!empty($property['land_type'])): ?>

                    <div class="info-row">

                        <div class="info-label">
                            Land Type
                        </div>

                        <div class="info-value">
                            <?= e($property['land_type']) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <?php if (!empty($property['access_road'])): ?>

                    <div class="info-row">

                        <div class="info-label">
                            Access Road
                        </div>

                        <div class="info-value">
                            <?= e($property['access_road']) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <div class="info-row">

                    <div class="info-label">
                        Location
                    </div>

                    <div class="info-value">
                        <?= e($property['location']) ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Property ID
                    </div>

                    <div class="info-value">
                        #<?= e($property['id']) ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Status
                    </div>

                    <div class="info-value">
                        <?= e($property['status']) ?>
                    </div>

                </div>

            </div>


            <!-- CONTACT -->

            <div class="contact-box">

                <h2>Interested?</h2>

                <p>
                    Contact The Reality Realtor for enquiries,
                    inspections and purchase information.
                </p>

                <div class="buttons">

                    <a
                        href="tel:+2348069454058"
                        class="btn btn-red">
                        Call 0806 945 4058
                    </a>

                    <a
                        href="<?= e($whatsappUrl) ?>"
                        target="_blank"
                        rel="noopener"
                        class="btn btn-white">
                        WhatsApp Us
                    </a>

                    <a
                        href="contact.php?property_id=<?= e($property['id']) ?>"
                        class="btn btn-white">
                        Send Enquiry
                    </a>

                </div>

            </div>


        </div>

    </div>

</div>


<footer>

    © <?= date('Y') ?> The <span>Reality</span> Realtor.
    All rights reserved.

</footer>


<script>

function changeImage(src)
{
    document.getElementById('mainImage').src = src;
}
function toggleMenu()
{
    const menu = document.getElementById('navLinks');

    menu.classList.toggle('open');
}
</script>


</body>
</html>