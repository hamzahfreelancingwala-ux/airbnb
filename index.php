<?php
// Include the database connection file
include 'db.php';

// --- PHP Logic for Featured Listings ---
$featured_listings = [];
$sql_featured = "SELECT id, title, location_city, price_per_night, image_url, rating, total_reviews FROM properties ORDER BY rating DESC, total_reviews DESC LIMIT 6";
$result_featured = $conn->query($sql_featured);

if ($result_featured && $result_featured->num_rows > 0) {
    while ($row = $result_featured->fetch_assoc()) {
        $featured_listings[] = $row;
    }
}

// --- PHP Logic for Search/Filter Submission ---
// This part handles the search form submission and redirects to the listing page
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $destination = $conn->real_escape_string($_POST['destination'] ?? '');
    $check_in = $conn->real_escape_string($_POST['check_in'] ?? '');
    $check_out = $conn->real_escape_string($_POST['check_out'] ?? '');

    // Redirect to the listing page with search parameters using JavaScript
    // Note: PHP header redirection is better, but you requested JS for redirection.
    // The query string will be passed to the hypothetical 'listing.php'
    $redirect_url = "listing.php?dest=" . urlencode($destination) . "&in=" . urlencode($check_in) . "&out=" . urlencode($check_out);
    
    echo "<script>";
    echo "window.location.href = '" . $redirect_url . "';";
    echo "</script>";
    exit();
}

// Placeholder for filters - these would be applied on the listing.php page
// (For simplicity, the homepage only handles the main search bar)

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airbnb Clone - Home</title>
    <style>
        /* --- GLOBAL CSS --- */
        :root {
            --primary-color: #FF385C; /* Airbnb Red */
            --text-dark: #222222;
            --text-light: #717171;
            --bg-light: #F7F7F7;
            --border-color: #DDDDDD;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background-color: white;
            color: var(--text-dark);
            line-height: 1.6;
        }
        a {
            text-decoration: none;
            color: var(--primary-color);
        }
        .container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* --- HEADER & NAVIGATION --- */
        .header {
            border-bottom: 1px solid var(--border-color);
            padding: 16px 0;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
            position: sticky;
            top: 0;
            background: white;
            z-index: 1000;
        }
        .nav-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            padding: 8px 12px;
            cursor: pointer;
            transition: box-shadow 0.2s;
        }
        .user-menu:hover {
            box-shadow: var(--box-shadow);
        }
        .menu-icon, .user-avatar {
            font-size: 18px;
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            background-color: var(--text-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 600;
        }

        /* --- SEARCH BAR DESIGN (THE CENTERPIECE) --- */
        .search-bar-container {
            position: relative;
            z-index: 500;
            margin: 30px auto;
            width: fit-content;
            border: 1px solid var(--border-color);
            border-radius: 40px;
            box-shadow: var(--box-shadow);
            background: white;
        }
        .search-form {
            display: flex;
            align-items: center;
            padding: 0;
        }
        .search-group {
            padding: 10px 20px;
            cursor: pointer;
            border-right: 1px solid var(--border-color);
            transition: background-color 0.2s;
            border-radius: inherit;
        }
        .search-group:hover {
            background-color: var(--bg-light);
        }
        .search-group:last-child {
            border-right: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-right: 10px;
        }
        .search-group label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            color: var(--text-dark);
            margin-bottom: 2px;
        }
        .search-group input[type="text"],
        .search-group input[type="date"] {
            border: none;
            outline: none;
            width: 150px;
            font-size: 14px;
            color: var(--text-light);
            padding: 0;
            background: transparent;
        }
        .search-group input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .search-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-left: 10px;
            margin-right: 8px;
        }
        .search-button:hover {
            background-color: #E0354E;
        }
        .search-icon {
            font-size: 18px;
        }

        /* --- FEATURED LISTINGS --- */
        .featured-section {
            padding: 40px 0;
        }
        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--text-dark);
            text-align: center;
        }
        .listing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .listing-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .listing-card:hover {
            transform: scale(1.02);
        }
        .listing-card a {
            color: inherit;
        }
        .listing-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        .listing-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 15px;
        }
        .listing-location {
            font-weight: 600;
            line-height: 1.3;
        }
        .listing-info {
            color: var(--text-light);
            font-weight: 400;
        }
        .listing-price {
            margin-top: 4px;
        }
        .listing-price strong {
            font-weight: 700;
            color: var(--text-dark);
        }
        .listing-rating {
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
        }
        .star-icon {
            color: var(--text-dark); /* or a gold color */
            margin-right: 4px;
        }

        /* --- FOOTER --- */
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: var(--text-light);
        }

        /* --- RESPONSIVENESS (Mobile First) --- */
        @media (max-width: 900px) {
            .search-bar-container {
                width: 100%;
                border: none;
                box-shadow: none;
                padding: 0 10px;
            }
            .search-form {
                flex-direction: column;
                border: 1px solid var(--border-color);
                border-radius: 15px;
                padding: 10px;
            }
            .search-group {
                border-right: none;
                width: 100%;
                padding: 10px 15px;
                border-bottom: 1px solid var(--border-color);
            }
            .search-group:last-child {
                border-bottom: none;
                padding-right: 15px;
                justify-content: flex-end;
            }
            .search-group input[type="text"],
            .search-group input[type="date"] {
                width: 100%;
            }
            .search-button {
                margin: 0;
                border-radius: 8px;
                padding: 12px 16px;
                width: 100%;
            }
            .search-icon {
                margin-right: 5px;
            }
            .search-group:hover {
                background-color: transparent;
            }
            .listing-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <header class="header">
        <div class="container nav-content">
            <div class="logo">airbnb</div>
            <div class="user-menu">
                <span class="menu-icon"><i class="fas fa-bars"></i></span>
                <span class="user-avatar">A</span>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="search-bar-container">
            <form action="index.php" method="POST" class="search-form">
                <div class="search-group" onclick="document.getElementById('destination').focus()">
                    <label for="destination">Where</label>
                    <input type="text" id="destination" name="destination" placeholder="Search destinations" required>
                </div>
                <div class="search-group" onclick="document.getElementById('check_in').showPicker()">
                    <label for="check_in">Check in</label>
                    <input type="date" id="check_in" name="check_in" required>
                </div>
                <div class="search-group" onclick="document.getElementById('check_out').showPicker()">
                    <label for="check_out">Check out</label>
                    <input type="date" id="check_out" name="check_out" required>
                </div>
                <div class="search-group">
                    <label for="guests">Guests</label>
                    <input type="text" id="guests" name="guests" placeholder="Add guests">
                    <button type="submit" name="search" class="search-button">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                        <span class="desktop-only">Search</span>
                    </button>
                </div>
            </form>
        </div>

        <section class="featured-section">
            <h2 class="section-title">✨ Top-Rated Stays & Featured Listings</h2>
            <div class="listing-grid">
                <?php if (empty($featured_listings)): ?>
                    <p style="text-align: center; grid-column: 1 / -1; color: var(--text-light);">No featured properties found in the database.</p>
                <?php else: ?>
                    <?php foreach ($featured_listings as $listing): ?>
                        <div class="listing-card" onclick="window.location.href='property_details.php?id=<?php echo $listing['id']; ?>'">
                            <img src="<?php echo htmlspecialchars($listing['image_url']); ?>" alt="Image of <?php echo htmlspecialchars($listing['title']); ?>" class="listing-image">
                            <div class="listing-details">
                                <div class="listing-info">
                                    <div class="listing-location"><?php echo htmlspecialchars($listing['location_city'] . ', ' . $listing['location_country']); ?></div>
                                    <div class="listing-price">
                                        <strong>$<?php echo number_format($listing['price_per_night'], 0); ?></strong> per night
                                    </div>
                                </div>
                                <div class="listing-rating">
                                    <span class="star-icon"><i class="fas fa-star"></i></span>
                                    <span><?php echo number_format($listing['rating'], 1); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer class="footer">
        &copy; <?php echo date("Y"); ?> Airbnb Clone by Pro AI. All rights reserved.
    </footer>

    <?php $conn->close(); ?>

</body>
</html>
