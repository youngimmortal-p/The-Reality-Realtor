<?php

require_once '../config/auth.php';
require_once '../config/database.php';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| Get all properties
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT
        p.id,
        p.title,
        p.location,
        p.category,
        p.status,
        p.featured,
        p.created_at,

        (
            SELECT pi.image_path
            FROM property_images pi
            WHERE pi.property_id = p.id
            ORDER BY pi.is_main DESC, pi.id ASC
            LIMIT 1
        ) AS main_image,

        (
            SELECT COUNT(*)
            FROM property_images pi2
            WHERE pi2.property_id = p.id
        ) AS image_count,

        (
            SELECT COUNT(*)
            FROM property_pricing pp
            WHERE pp.property_id = p.id
        ) AS pricing_count

    FROM properties p
    ORDER BY p.created_at DESC
");

$properties = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Properties | The Reality Realtor</title>

    <style>
        :root {
            --red: #C8102E;
            --red-dark: #A00D24;
            --black: #0A0A0A;
            --gray: #6B6B6B;
            --light: #F8F8F8;
            --white: #FFFFFF;
            --border: #E5E5E5;
            --green: #198754;
            --orange: #d97706;
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
        }
h1, h2, h3 {
  font-family: 'Playfair Display', serif;
  line-height: 1.2;
}
        a {
            text-decoration: none;
        }

        /* HEADER */

        header {
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    background: #c90000;
    padding: 20px;
    position: relative; /* Crucial anchor for the mobile menu dropdown */
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

/* NAVIGATION DEFAULT STYLE */
nav {
    margin-top: 15px;
    display: flex; /* Kept aligned on desktop */
}

nav a {
    color: #fff;
    text-decoration: none;
    margin-right: 20px;
}

nav a:hover,
nav a.active {
    background: var(--red);
    color: var(--white);
}

/* HAMBURGER TOGGLE BUTTON STYLE (Hidden by default on desktop) */
.menu-toggle {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.menu-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: #ffffff; /* White lines to match your layout header text */
    border-radius: 2px;
}

        /* MAIN */

        main {
            max-width: 1250px;
            margin: 0 auto;
            padding: 30px 20px 50px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 28px;
        }

        .page-heading p {
            color: var(--gray);
            margin-top: 5px;
            font-size: 14px;
        }

        .add-button {
            display: inline-block;
            background: var(--red);
            color: var(--white);
            padding: 12px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
        }

        .add-button:hover {
            background: var(--red-dark);
        }

        /* SUCCESS MESSAGE */

        .message {
            background: #e8f7ee;
            color: #146c43;
            border: 1px solid #b7e4c7;
            padding: 13px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* EMPTY */

        .empty {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 50px 20px;
            text-align: center;
        }

        .empty h2 {
            margin-bottom: 10px;
        }

        .empty p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        /* PROPERTY LIST */

        .property-list {
            display: grid;
            gap: 16px;
        }

        .property-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 190px 1fr auto;
            min-height: 170px;
        }

        .property-image {
            width: 190px;
            height: 100%;
            min-height: 170px;
            background: #eee;
            overflow: hidden;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .no-image {
            width: 100%;
            height: 100%;
            min-height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 13px;
            text-align: center;
            padding: 10px;
        }

        .property-info {
            padding: 18px 20px;
        }

        .property-info h2 {
            font-size: 19px;
            margin-bottom: 8px;
        }

        .property-location {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 12px;
        }

        .property-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 13px;
        }

        .badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-category {
            background: #f1f1f1;
            color: var(--black);
        }

        .badge-available {
            background: #e8f7ee;
            color: var(--green);
        }

        .badge-sold {
            background: #fce8ec;
            color: var(--red);
        }

        .badge-reserved {
            background: #fff3df;
            color: var(--orange);
        }

        .badge-featured {
            background: var(--red);
            color: var(--white);
        }

        .small-info {
            color: var(--gray);
            font-size: 12px;
        }

        .property-actions {
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 9px;
            min-width: 125px;
        }

        .action-button {
            display: block;
            text-align: center;
            padding: 9px 13px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }

        .edit-button {
            background: var(--red);
            color: var(--white);
        }

        .edit-button:hover {
            background: var(--red-dark);
        }

        .delete-button {
            background: #fce8ec;
            color: var(--red);
        }

        .delete-button:hover {
            background: var(--red);
            color: var(--white);
        }

        .view-button {
            background: var(--black);
            color: var(--white);
        }

        .view-button:hover {
            background: #333;
        }

        /* FOOTER */

        footer {
            text-align: center;
            padding: 25px 15px;
            color: var(--gray);
            font-size: 12px;
        }

        /* MOBILE */

        @media (max-width: 700px) {

            header {
        padding: 14px 16px;
    }

    .brand {
        font-size: 17px;
    }

    .brand img {
        height: 32px; /* Scale down logo slightly for compact screen */
    }

    /* Reveal the hamburger element on mobile viewports */
    .menu-toggle {
        display: flex;
    }

    /* Transform navigation block into a hidden vertical drawer tray */
    nav.nav-menu {
        display: none; 
        position: absolute;
        top: 100%; /* Positions dropdown box right below header line */
        left: 0;
        width: 100%;
        background: #c90000; /* Matching header red color fill */
        flex-direction: column;
        padding: 12px 16px;
        gap: 5px;
        margin-top: 0; /* Clear desktop margin shift */
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        z-index: 999;
    }

    /* When JavaScript pushes the active class, display the full stack */
    nav.nav-menu.active {
        display: flex;
    }

    nav a {
        font-size: 12px;
        padding: 8px 10px;
        margin-right: 0; /* Clear desktop inline margins */
        width: 100%;
    }


            main {
                padding: 22px 12px 40px;
            }

            .page-heading {
                align-items: flex-start;
                flex-direction: column;
                margin-bottom: 20px;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .page-heading p {
                font-size: 12px;
            }

            .add-button {
                width: 100%;
                text-align: center;
                padding: 11px;
            }

            .property-card {
                display: block;
            }

            .property-image {
                width: 100%;
                height: 190px;
                min-height: 190px;
            }

            .no-image {
                min-height: 190px;
            }

            .property-info {
                padding: 14px;
            }

            .property-info h2 {
                font-size: 17px;
            }

            .property-location {
                font-size: 12px;
            }

            .property-actions {
                padding: 0 14px 14px;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                min-width: 0;
            }

            .action-button {
                font-size: 11px;
                padding: 9px 5px;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="brand">
        <img src="/images/logo1.png" alt="The Reality Realtor">
    </div>

    <!-- The Hamburger 3-Line Button -->
    <button class="menu-toggle" aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <!-- Added nav-menu class to your navigation -->
    <nav class="nav-menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="properties.php" class="active">Manage Properties</a>
        <a href="add-property.php">Add Property</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>



<main>

    <div class="page-heading">
        <div>
            <h1>Manage Properties</h1>
            <p>View, edit and manage all properties on your website.</p>
        </div>

        <a href="add-property.php" class="add-button">
            + Add Property
        </a>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="message">
            Property added successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="message">
            Property updated successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="message">
            Property deleted successfully.
        </div>
    <?php endif; ?>


    <?php if (empty($properties)): ?>

        <div class="empty">
            <h2>No Properties Yet</h2>

            <p>
                You have not added any properties yet.
            </p>

            <a href="add-property.php" class="add-button">
                Add Your First Property
            </a>
        </div>

    <?php else: ?>

        <div class="property-list">

            <?php foreach ($properties as $property): ?>

                <article class="property-card">

                    <!-- IMAGE -->

                    <div class="property-image">

                        <?php if (!empty($property['main_image'])): ?>

                            <img
                                src="../<?= e($property['main_image']) ?>"
                                alt="<?= e($property['title']) ?>"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                No property image
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- INFORMATION -->

                    <div class="property-info">

                        <h2>
                            <?= e($property['title']) ?>
                        </h2>

                        <div class="property-location">
                            <?= e($property['location']) ?>
                        </div>

                        <div class="property-meta">

                            <span class="badge badge-category">
                                <?= e($property['category']) ?>
                            </span>


                            <?php if ($property['status'] === 'Available'): ?>

                                <span class="badge badge-available">
                                    Available
                                </span>

                            <?php elseif ($property['status'] === 'Sold'): ?>

                                <span class="badge badge-sold">
                                    Sold
                                </span>

                            <?php else: ?>

                                <span class="badge badge-reserved">
                                    Reserved
                                </span>

                            <?php endif; ?>


                            <?php if ((int)$property['featured'] === 1): ?>

                                <span class="badge badge-featured">
                                    Featured
                                </span>

                            <?php endif; ?>

                        </div>


                        <div class="small-info">

                            <?= (int)$property['image_count'] ?>
                            image<?= ((int)$property['image_count'] === 1 ? '' : 's') ?>

                            &nbsp; • &nbsp;

                            <?= (int)$property['pricing_count'] ?>
                            pricing option<?= ((int)$property['pricing_count'] === 1 ? '' : 's') ?>

                        </div>

                    </div>


                    <!-- ACTIONS -->

                    <div class="property-actions">

                        <a
                            href="../property.php?id=<?= (int)$property['id'] ?>"
                            class="action-button view-button"
                            target="_blank"
                        >
                            View
                        </a>

                        <a
                            href="edit-property.php?id=<?= (int)$property['id'] ?>"
                            class="action-button edit-button"
                        >
                            Edit
                        </a>

                        <form
                            method="POST"
                            action="delete-property.php"
                            onsubmit="return confirm('Are you sure you want to delete this property? This action cannot be undone.');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= (int)$property['id'] ?>"
                            >

                            <button
                                type="submit"
                                class="action-button delete-button"
                            >
                                Delete
                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</main>

<footer>
    &copy; <?= date('Y') ?> The Reality Realtor. All rights reserved.
</footer>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    // Click handler to reveal or collapse navigation stack
    menuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
});
</script>

</body>
</html>