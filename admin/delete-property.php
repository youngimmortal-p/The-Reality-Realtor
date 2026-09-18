<?php

require_once '../config/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: properties.php');
    exit;
}

verify_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    exit('Invalid property ID.');
}

/*
|--------------------------------------------------------------------------
| Get property images before deleting the property
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT image_path
    FROM property_images
    WHERE property_id = ?
");

$stmt->execute([$id]);

$images = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Confirm property exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM properties
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

if (!$stmt->fetch()) {
    header('Location: properties.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Delete property
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Because the child tables use ON DELETE CASCADE,
    | deleting the property also deletes:
    |
    | property_pricing
    | property_features
    | property_landmarks
    | property_documents
    | property_images
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM properties
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $pdo->commit();

    /*
    |--------------------------------------------------------------------------
    | Delete physical image files
    |--------------------------------------------------------------------------
    */

    foreach ($images as $image) {

        $file = dirname(__DIR__) . '/' . $image['image_path'];

        if (is_file($file)) {
            @unlink($file);
        }
    }

    header('Location: properties.php?deleted=1');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    exit('Could not delete the property.');
}