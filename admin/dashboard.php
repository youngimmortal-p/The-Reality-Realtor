<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM properties");
$totalProperties = $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT COUNT(*) FROM properties WHERE status = 'Available'"
);
$availableProperties = $stmt->fetchColumn();

$stmt = $pdo->query(
    "SELECT COUNT(*) FROM properties WHERE status = 'Sold'"
);
$soldProperties = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title>Dashboard - The Reality Realtor</title>

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
}

body {
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: #f8f8f8;
    color: #222;
}
h1, h2, h3 {
  font-family: 'Playfair Display', serif;
  line-height: 1.2;
}
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

main {
    padding: 25px;
    max-width: 1100px;
    margin: auto;
}

.welcome {
    margin-bottom: 25px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.card {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    border-top: 4px solid #c90000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.card h3 {
    margin-top: 0;
}

.number {
    font-size: 32px;
    font-weight: bold;
    color: #c90000;
}

.add-property {
    display: inline-block;
    margin-top: 30px;
    padding: 14px 20px;
    background: #c90000;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
}

.add-property:hover {
    background: #a80000;
}

@media (max-width: 700px) {
    .cards {
        grid-template-columns: 1fr;
    }

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

}
    </style>
</head>

<body>

<header>
    <div class="brand">
        <img src="/images/logo2.png" alt="The Reality Realtor">
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

    <div class="welcome">
        <h2>Admin Dashboard</h2>

        <p>
            Welcome,
            <?= htmlspecialchars($_SESSION['admin_username']) ?>
        </p>
    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Properties</h3>
            <div class="number">
                <?= $totalProperties ?>
            </div>
        </div>

        <div class="card">
            <h3>Available</h3>
            <div class="number">
                <?= $availableProperties ?>
            </div>
        </div>

        <div class="card">
            <h3>Sold</h3>
            <div class="number">
                <?= $soldProperties ?>
            </div>
        </div>

    </div>

    <a class="add-property" href="add-property.php">
        + Add New Property
    </a>

</main>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');


    menuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
    });
});
</script>

</body>

</html>