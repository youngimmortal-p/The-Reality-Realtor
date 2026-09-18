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


/*
|--------------------------------------------------------------------------
| Selected location
|--------------------------------------------------------------------------
*/

$locations = ['Enugu', 'Abuja', 'Asaba'];

$selectedLocation = $_GET['location'] ?? 'Enugu';

if (!in_array($selectedLocation, $locations, true)) {
    $selectedLocation = 'Enugu';
}


/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$categoryMap = [
    'Enugu' => [
        'Buy and Build',
        'Land Banking',
        'Luxury Homes'
    ],

    'Abuja' => [
        'Buy and Build',
        'Land Banking',
        'Luxury Homes'
    ],

    'Asaba' => [
        'Available Properties'
    ]
];

$selectedCategory = $_GET['category'] ?? '';

if (
    $selectedCategory !== '' &&
    !in_array($selectedCategory, $categoryMap[$selectedLocation], true)
) {
    $selectedCategory = '';
}


/*
|--------------------------------------------------------------------------
| Get properties
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.title,
        p.location,
        p.category,
        p.description,
        p.status,
        p.featured,
        p.address,
        p.created_at,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS main_image

    FROM properties p

    WHERE p.location = ?
";

$params = [$selectedLocation];

if ($selectedCategory !== '') {

    $sql .= " AND p.category = ?";

    $params[] = $selectedCategory;
}

$sql .= "
    ORDER BY p.featured DESC, p.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$properties = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get pricing for all displayed properties
|--------------------------------------------------------------------------
*/

$pricingByProperty = [];

if (!empty($properties)) {

    $propertyIds = array_column($properties, 'id');

    $placeholders = implode(
        ',',
        array_fill(0, count($propertyIds), '?')
    );

    $stmt = $pdo->prepare("
        SELECT
            property_id,
            plot_size,
            actual_price,
            presale_price

        FROM property_pricing

        WHERE property_id IN ($placeholders)

        ORDER BY id ASC
    ");

    $stmt->execute($propertyIds);

    $pricingRows = $stmt->fetchAll();

    foreach ($pricingRows as $row) {

        $pricingByProperty[$row['property_id']][] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| Get features count
|--------------------------------------------------------------------------
*/

$featureCountByProperty = [];

if (!empty($properties)) {

    $propertyIds = array_column($properties, 'id');

    $placeholders = implode(
        ',',
        array_fill(0, count($propertyIds), '?')
    );

    $stmt = $pdo->prepare("
        SELECT
            property_id,
            COUNT(*) AS total

        FROM property_features

        WHERE property_id IN ($placeholders)

        GROUP BY property_id
    ");

    $stmt->execute($propertyIds);

    foreach ($stmt->fetchAll() as $row) {

        $featureCountByProperty[$row['property_id']]
            = (int)$row['total'];
    }
}


/*
|--------------------------------------------------------------------------
| Distress Sales
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        p.id,
        p.title,
        p.location,
        p.description,
        p.status,
        p.featured,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS main_image

    FROM properties p

    WHERE p.category = 'Distress Sales'

    ORDER BY p.featured DESC, p.created_at DESC
");

$distressSales = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Pricing for distress sales
|--------------------------------------------------------------------------
*/

$distressPricing = [];

if (!empty($distressSales)) {

    $ids = array_column($distressSales, 'id');

    $placeholders = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $stmt = $pdo->prepare("
        SELECT
            property_id,
            plot_size,
            actual_price,
            presale_price

        FROM property_pricing

        WHERE property_id IN ($placeholders)

        ORDER BY id ASC
    ");

    $stmt->execute($ids);

    foreach ($stmt->fetchAll() as $row) {

        $distressPricing[$row['property_id']][] = $row;
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
    Properties | The Reality Realtor
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
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--light);
    color: var(--black);
    line-height: 1.5;
}
h1, h2, h3 {
  font-family: 'Playfair Display', serif;
  line-height: 1.2;
}
a {
    text-decoration: none;
}


/* NAVIGATION */

nav {
    background: #c90000;
    color: var(--white);
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
    align-items: center;
    gap: 25px;
}

.nav-links a {
    color: var(--white);
    font-size: 14px;
}

.nav-links a:hover {
    color: var(--red);
}


/* HAMBURGER */

.menu-toggle {
    display: none;

    background: none;
    border: none;

    color: white;
    font-size: 26px;

    cursor: pointer;
}


/* CONTAINER */

.container {
    width: 90%;
    max-width: 1200px;
    margin: 35px auto;
}


/* PAGE HEADER */

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    margin-bottom: 8px;
}

.page-header p {
    color: var(--gray);
}


/* LOCATION TABS */

.location-tabs {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 15px;
}

.location-tabs a {
    background: var(--black);
    color: var(--white);

    padding: 11px 25px;
    border-radius: 5px;

    font-weight: bold;
    font-size: 14px;
}

.location-tabs a.active,
.location-tabs a:hover {
    background: var(--red);
}


/* CATEGORY TABS */

.category-tabs {
    display: flex;
    justify-content: center;
    gap: 8px;

    margin-bottom: 35px;

    flex-wrap: wrap;
}

.category-tabs a {
    background: var(--white);
    color: var(--black);

    border: 1px solid #ddd;

    padding: 9px 18px;
    border-radius: 5px;

    font-size: 13px;
}

.category-tabs a.active,
.category-tabs a:hover {
    background: var(--red);
    color: var(--white);
    border-color: var(--red);
}


/* SECTION */

.section-title {
    margin-bottom: 20px;
}

.section-title h2 {
    font-size: 23px;
}

.section-title span {
    color: var(--red);
}


/* GRID */

.property-grid {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 22px;
}


/* CARD */

.property-card {
    background: var(--white);

    border-radius: 10px;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(0,0,0,0.07);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.property-card:hover {
    transform: translateY(-3px);

    box-shadow:
        0 7px 18px rgba(0,0,0,0.12);
}


/* IMAGE */

.card-image {
    position: relative;
}

.card-image img {
    display: block;

    width: 100%;
    height: 220px;

    object-fit: cover;
}

.featured {
    position: absolute;

    top: 12px;
    left: 12px;

    background: var(--red);
    color: var(--white);

    padding: 5px 9px;

    border-radius: 4px;

    font-size: 11px;
    font-weight: bold;
}


/* CARD CONTENT */

.card-content {
    padding: 18px;
}

.card-content h3 {
    font-size: 18px;

    margin-bottom: 6px;
}

.card-location {
    color: var(--gray);
    font-size: 13px;

    margin-bottom: 13px;
}

.card-category {
    display: inline-block;

    background: #f1f1f1;

    padding: 4px 8px;

    border-radius: 4px;

    font-size: 11px;
    font-weight: bold;

    margin-bottom: 13px;
}


/* PRICING */

.card-pricing {
    border-top: 1px solid #eee;

    padding-top: 12px;

    margin-bottom: 14px;
}

.price-row {
    display: flex;

    justify-content: space-between;

    gap: 10px;

    padding: 5px 0;

    font-size: 13px;
}

.price-row strong {
    color: var(--black);
}

.price-row .presale {
    color: var(--red);
    font-weight: bold;
}


/* CARD FOOTER */

.card-bottom {
    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 10px;
}

.details-btn {
    flex: 1;

    background: var(--red);
    color: var(--white);

    text-align: center;

    padding: 10px;

    border-radius: 5px;

    font-weight: bold;
    font-size: 13px;
}

.details-btn:hover {
    background: var(--red-dark);
}

.status {
    font-size: 11px;

    font-weight: bold;

    color: var(--gray);
}


/* EMPTY */

.empty {
    background: var(--white);

    padding: 40px 20px;

    text-align: center;

    border-radius: 8px;

    color: var(--gray);

    grid-column: 1 / -1;
}


/* DISTRESS */

.distress-section {
    margin-top: 55px;
}

.distress-header {
    background: #c90000;

    color: var(--white);

    padding: 18px 20px;

    border-radius: 8px 8px 0 0;
}

.distress-header h2 {
    color: var(--white);
}

.distress-header p {
    color: #ccc;
    font-size: 13px;
}


/* FOOTER */

footer {
    background: var(--black);

    color: var(--white);

    text-align: center;

    padding: 25px;

    margin-top: 60px;

    font-size: 13px;
}

footer span {
    color: var(--red);
}


/* MOBILE */

@media (max-width: 900px) {

    .property-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

}


@media (max-width: 700px) {

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
        margin: 25px auto;
    }


    .page-header h1 {
        font-size: 26px;
    }


    .location-tabs {
        gap: 5px;
    }

    .location-tabs a {
        padding: 8px 14px;
        font-size: 12px;
    }


    .category-tabs {
        justify-content: flex-start;

        overflow-x: auto;

        flex-wrap: nowrap;

        padding-bottom: 6px;

        -webkit-overflow-scrolling: touch;
    }

    .category-tabs a {
        white-space: nowrap;

        padding: 7px 12px;

        font-size: 11px;
    }


    .property-grid {
        grid-template-columns: 1fr;

        gap: 18px;
    }


    .card-image img {
        height: 210px;
    }


    .card-content {
        padding: 15px;
    }


    .card-content h3 {
        font-size: 16px;
    }


    .card-location {
        font-size: 12px;
    }


    .price-row {
        font-size: 12px;
    }

}

</style>

</head>

<body>


<!-- NAV -->

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

<a href="index.php">
    Home
</a>

<a href="properties.php">
    Properties
</a>


</div>

</nav>


<div class="container">


<!-- HEADER -->

<div class="page-header">

<h1>
    Our <span style="color:var(--red);">Properties</span>
</h1>

<p>
    Discover verified land and property opportunities
    in Enugu, Abuja and Asaba.
</p>

</div>


<!-- LOCATION -->

<div class="location-tabs">

<?php foreach ($locations as $location): ?>

<a
    href="properties.php?location=<?= urlencode($location) ?>"
    class="<?= $selectedLocation === $location ? 'active' : '' ?>"
>
    <?= e($location) ?>
</a>

<?php endforeach; ?>

</div>


<!-- CATEGORIES -->

<div class="category-tabs">

<a
    href="properties.php?location=<?= urlencode($selectedLocation) ?>"
    class="<?= $selectedCategory === '' ? 'active' : '' ?>"
>
    All
</a>


<?php foreach ($categoryMap[$selectedLocation] as $category): ?>

<a
    href="properties.php?location=<?= urlencode($selectedLocation) ?>&category=<?= urlencode($category) ?>"
    class="<?= $selectedCategory === $category ? 'active' : '' ?>"
>
    <?= e($category) ?>
</a>

<?php endforeach; ?>

</div>


<!-- PROPERTIES -->

<div class="section-title">

<h2>
    Properties in
    <span><?= e($selectedLocation) ?></span>
</h2>

</div>


<div class="property-grid">


<?php if (empty($properties)): ?>

<div class="empty">

    <h3>No properties available yet.</h3>

    <p>
        Please check back soon for new listings.
    </p>

</div>


<?php else: ?>


<?php foreach ($properties as $property): ?>


<?php

$image = !empty($property['main_image'])
    ? $property['main_image']
    : 'images/property-placeholder.jpg';

$propertyPricing =
    $pricingByProperty[$property['id']] ?? [];

?>


<div class="property-card">


<div class="card-image">

<img
    src="<?= e($image) ?>"
    alt="<?= e($property['title']) ?>"
    loading="lazy"
>


<?php if (!empty($property['featured'])): ?>

<div class="featured">
    FEATURED
</div>

<?php endif; ?>

</div>


<div class="card-content">


<h3>
    <?= e($property['title']) ?>
</h3>


<div class="card-location">
    📍 <?= e($property['location']) ?>

    <?php if (!empty($property['address'])): ?>
        — <?= e($property['address']) ?>
    <?php endif; ?>
</div>


<div class="card-category">
    <?= e($property['category']) ?>
</div>


<?php if (!empty($propertyPricing)): ?>

<div class="card-pricing">

<?php foreach ($propertyPricing as $price): ?>

<div class="price-row">

<strong>
    <?= e($price['plot_size']) ?>
</strong>


<div>

<?php if ($price['presale_price'] !== null): ?>

<span class="presale">
    <?= naira($price['presale_price']) ?>
</span>

<?php elseif ($price['actual_price'] !== null): ?>

<span>
    <?= naira($price['actual_price']) ?>
</span>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div class="card-bottom">

<a
    href="property.php?id=<?= e($property['id']) ?>"
    class="details-btn"
>
    View Property
</a>

<span class="status">
    <?= e($property['status']) ?>
</span>

</div>


</div>

</div>


<?php endforeach; ?>

<?php endif; ?>


</div>


<!-- DISTRESS SALES -->

<div class="distress-section">

<div class="distress-header">

<h2>
    Distress Sales
</h2>

<p>
    Special property opportunities available for
    interested buyers.
</p>

</div>


<div class="property-grid">


<?php if (empty($distressSales)): ?>

<div class="empty">

    <h3>No distress sales available.</h3>

    <p>
        New distress-sale opportunities will appear here.
    </p>

</div>


<?php else: ?>


<?php foreach ($distressSales as $property): ?>


<?php

$image = !empty($property['main_image'])
    ? $property['main_image']
    : 'images/property-placeholder.jpg';

$propertyPricing =
    $distressPricing[$property['id']] ?? [];

?>


<div class="property-card">


<div class="card-image">

<img
    src="<?= e($image) ?>"
    alt="<?= e($property['title']) ?>"
    loading="lazy"
>

<div class="featured">
    DISTRESS SALE
</div>

</div>


<div class="card-content">


<h3>
    <?= e($property['title']) ?>
</h3>


<div class="card-location">
    📍 <?= e($property['location']) ?>
</div>


<?php if (!empty($propertyPricing)): ?>

<div class="card-pricing">

<?php foreach ($propertyPricing as $price): ?>

<div class="price-row">

<strong>
    <?= e($price['plot_size']) ?>
</strong>

<div>

<?php if ($price['presale_price'] !== null): ?>

<span class="presale">
    <?= naira($price['presale_price']) ?>
</span>

<?php elseif ($price['actual_price'] !== null): ?>

<span>
    <?= naira($price['actual_price']) ?>
</span>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div class="card-bottom">

<a
    href="property.php?id=<?= e($property['id']) ?>"
    class="details-btn"
>
    View Property
</a>

<span class="status">
    <?= e($property['status']) ?>
</span>

</div>


</div>

</div>


<?php endforeach; ?>

<?php endif; ?>


</div>

</div>


</div>


<footer>

© <?= date('Y') ?> The
<span>Reality</span>
Realtor. All rights reserved.

</footer>


<script>

function toggleMenu()
{
    const menu = document.getElementById('navLinks');

    menu.classList.toggle('open');
}

</script>


</body>

</html>