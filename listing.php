<?php
include 'db.php';

// --- PHP Logic for Search, Filter, and Sort ---

// Get search parameters from the URL
$destination = $_GET['dest'] ?? '';
$check_in = $_GET['in'] ?? '';
$check_out = $_GET['out'] ?? '';

// Get filter/sort parameters
$sort = $_GET['sort'] ?? 'best_rated'; // Default sort
$property_type = $_GET['type'] ?? 'All';

// Build the WHERE clause for the SQL query
$where_clauses = [];
$search_title = "All Available Properties";

if (!empty($destination)) {
    // Search for destination in city, country, or title
    $safe_dest = $conn->real_escape_string($destination);
    $where_clauses[] = "(location_city LIKE '%$safe_dest%' OR location_country LIKE '%$safe_dest%' OR title LIKE '%$safe_dest%')";
    $search_title = "Stays in " . htmlspecialchars($destination);
}

if ($property_type !== 'All') {
    $safe_type = $conn->real_escape_string($property_type);
    $where_clauses[] = "property_type = '$safe_type'";
}

// NOTE: Booking availability check is complex and omitted for this initial version.
// A real system would check the `bookings` table for date conflicts.

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Build the ORDER BY clause
$order_sql = " ORDER BY ";
switch ($sort) {
    case 'price_low':
        $order_sql .= "price_per_night ASC";
        break;
    case 'price_high':
        $order_sql .= "price_per_night DESC";
        break;
    case 'best_rated':
    default:
        $order_sql .= "rating DESC, total_reviews DESC";
        break;
}

// Final SQL Query
$sql_listings = "SELECT id, title, location_city, location_country, price_per_night, image_url, rating, total_reviews FROM properties" . $where_sql . $order_sql;
$result_listings = $conn->query($sql_listings);

$listings = [];
if ($result_listings) {
    while ($row = $result_listings->fetch_assoc()) {
        $listings[] = $row;
    }
}

// Get all unique property types for the filter dropdown
$property_types_query = $conn->query("SELECT DISTINCT property_type FROM properties");
$available_types = ['All'];
if ($property_types_query) {
    while ($row = $property_types_query->fetch_assoc()) {
        $available_types[] = $row['property_type'];
    }
}

// Helper function to reconstruct URL query string for filters/sort
function build_query_string($new_params = []) {
    $current_params = $_GET;
    $final_params = array_merge($current_params, $new_params);
    return http_build_query($final_params);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($search_title); ?> | Listings</title>
    <style>
        /* --- GLOBAL CSS (Abridged/Same as other files) --- */
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
            cursor: pointer;
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--border-color);
            border-radius: 25px;
            padding: 8px 12px;
            cursor: pointer;
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
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: var(--text-light);
        }

        /* --- LISTING PAGE SPECIFIC CSS --- */
        .listing-header {
            padding: 30px 0 20px;
        }
        .listing-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .search-info {
            font-size: 16px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        /* Filters and Sorting Bar */
        .filters-bar {
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            padding: 15px 0;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .filter-group label {
            font-weight: 600;
            margin-right: 10px;
            color: var(--text-dark);
        }
        .filter-group select {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: white;
            font-size: 14px;
            cursor: pointer;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%20viewBox%3D%220%200%20292.4%20292.4%22%3E%3Cpath%20fill%3D%22%23717171%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13.2-5.4H18.6c-5%200-9.6%202-13.2%205.4A17.6%2017.6%200%200%200%200%2082.5c0%205%202%209.6%205.4%2013.2l128.2%20128.2a17.6%2017.6%200%200%200%2025.2%200L287%2095.7c3.4-3.6%205.4-8.2%205.4-13.2.1-5-1.9-9.6-5.5-13.1z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 10px;
        }

        /* --- LISTING GRID (Same as index.php) --- */
        .listing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            padding-top: 40px;
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
            color: var(--text-dark);
            margin-right: 4px;
        }

        /* --- RESPONSIVENESS --- */
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .filter-group select {
                width: 100%;
            }
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        // Simple JS for redirection back to homepage
        function goToHome() {
            window.location.href = 'index.php';
        }
        
        // JS for handling filter/sort changes
        function applyFilter(elementId) {
            const element = document.getElementById(elementId);
            const value = element.value;
            const param = element.name;
            
            // Build new URL with the selected parameter and value
            const url = new URL(window.location.href);
            url.searchParams.set(param, value);
            
            // Redirect using JavaScript
            window.location.href = url.toString();
        }
    </script>
</head>
<body>

    <header class="header">
        <div class="container nav-content">
            <div class="logo" onclick="goToHome()">airbnb</div>
            <div class="user-menu">
                <span class="menu-icon"><i class="fas fa-bars"></i></span>
                <span class="user-avatar">A</span>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="listing-header">
            <h1 class="listing-title"><?php echo htmlspecialchars($search_title); ?></h1>
            <p class="search-info">
                <?php 
                    $info_parts = [];
                    if (!empty($check_in) && !empty($check_out)) {
                        $info_parts[] = date('M j', strtotime($check_in)) . ' - ' . date('M j', strtotime($check_out));
                    }
                    if (!empty($_GET['guests'])) {
                        $info_parts[] = htmlspecialchars($_GET['guests']) . ' Guests';
                    }
                    if (empty($info_parts)) {
                         $info_parts[] = 'No specific dates/guests provided';
                    }
                    echo implode(' | ', $info_parts);
                ?>
            </p>
        </section>

        <div class="filters-bar">
            <div class="filter-group">
                <label for="type_filter">Property Type:</label>
                <select id="type_filter" name="type" onchange="applyFilter('type_filter')">
                    <?php foreach ($available_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $property_type === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort_by">Sort By:</label>
                <select id="sort_by" name="sort" onchange="applyFilter('sort_by')">
                    <option value="best_rated" <?php echo $sort === 'best_rated' ? 'selected' : ''; ?>>Best Rated</option>
                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
            
            <div class="filter-group" style="color: var(--text-light);">
                Price Range Filter * (Not implemented)
            </div>
        </div>
        
        <section class="listing-results">
            <div class="listing-grid">
                <?php if (empty($listings)): ?>
                    <p style="text-align: center; grid-column: 1 / -1; color: var(--text-light); padding: 50px 0;">
                        Sorry, no properties found matching your search criteria.
                    </p>
                <?php else: ?>
                    <?php foreach ($listings as $listing): ?>
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
