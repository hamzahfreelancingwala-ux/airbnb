<?php
include 'db.php';

// --- PHP Logic for Fetching Property Details ---
$property_id = $_GET['id'] ?? null;
$property = null;
$error_message = '';

if ($property_id) {
    // Sanitize the ID
    $safe_id = $conn->real_escape_string($property_id);
    
    // Fetch property details
    $sql_property = "SELECT * FROM properties WHERE id = '{$safe_id}'";
    $result_property = $conn->query($sql_property);

    if ($result_property && $result_property->num_rows > 0) {
        $property = $result_property->fetch_assoc();
    } else {
        $error_message = "Property not found. Invalid ID: " . htmlspecialchars($property_id);
    }
} else {
    $error_message = "No property ID provided.";
}

// --- PHP Logic for Booking Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book'])) {
    if ($property) {
        // Sanitize and validate booking data
        $check_in = $conn->real_escape_string($_POST['check_in']);
        $check_out = $conn->real_escape_string($_POST['check_out']);
        $num_guests = (int)$_POST['num_guests'];
        
        // Basic Date Validation
        $in_date = new DateTime($check_in);
        $out_date = new DateTime($check_out);
        $today = new DateTime();
        
        if ($in_date >= $out_date || $in_date < $today) {
            $booking_status = "❌ Booking Failed: Check-in date must be before check-out, and both must be in the future.";
        } elseif ($num_guests > $property['max_guests']) {
             $booking_status = "❌ Booking Failed: Number of guests exceeds the property limit of {$property['max_guests']}.";
        } else {
            // Calculate total days and price
            $interval = $in_date->diff($out_date);
            $days = $interval->days;
            $total_price = $days * $property['price_per_night'];
            
            // Simplified User ID (In a real app, this comes from a logged-in user session)
            $user_id = 1; // Placeholder User ID
            
            // Insert booking into the database
            $sql_booking = "INSERT INTO bookings (property_id, user_id, check_in_date, check_out_date, num_guests, total_price) 
                            VALUES ('{$property['id']}', '{$user_id}', '{$check_in}', '{$check_out}', '{$num_guests}', '{$total_price}')";
            
            if ($conn->query($sql_booking) === TRUE) {
                // SUCCESS
                $booking_status = "✅ Success! Your stay at **{$property['title']}** is confirmed from {$check_in} to {$check_out} for **\$$total_price** ({$days} nights).";
            } else {
                // FAILURE
                $booking_status = "❌ Booking Error: " . $conn->error;
            }
        }
    } else {
         $booking_status = "❌ Booking Failed: Cannot book, property details could not be loaded.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $property ? htmlspecialchars($property['title']) : 'Property Details'; ?> | Airbnb Clone</title>
    <style>
        /* --- GLOBAL CSS (Copy from index.php for consistency) --- */
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
            max-width: 1200px;
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
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 20px 0;
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: var(--text-light);
        }

        /* --- PROPERTY DETAILS SPECIFIC CSS --- */
        .details-header {
            padding: 40px 0 20px;
        }
        .title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .subtitle-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-dark);
            font-size: 14px;
            font-weight: 600;
        }
        .rating-info {
            display: flex;
            align-items: center;
        }
        .star-icon {
            color: var(--text-dark);
            margin-right: 4px;
        }
        .image-gallery {
            width: 100%;
            height: 500px;
            overflow: hidden;
            border-radius: 12px;
            margin: 20px 0;
            /* Simple gallery with one main image for this version */
        }
        .main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Main content vs Booking box */
            gap: 80px;
            margin-top: 30px;
        }

        /* --- LEFT COLUMN: Description & Amenities --- */
        .description-box h3 {
            font-size: 22px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        .description-box p {
            color: var(--text-dark);
            margin-bottom: 30px;
            font-size: 16px;
        }
        .amenities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            list-style: none;
            margin-top: 20px;
        }
        .amenity-item {
            font-size: 15px;
            color: var(--text-dark);
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .amenity-icon {
            margin-right: 8px;
            color: var(--text-dark);
            font-size: 18px;
        }
        
        /* --- RIGHT COLUMN: Booking Box --- */
        .booking-box {
            position: sticky;
            top: 100px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--box-shadow);
        }
        .booking-price-header {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .booking-price-header span {
            font-size: 16px;
            font-weight: 400;
            color: var(--text-light);
        }
        .booking-form-group {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        .date-input-group {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }
        .date-input-group > div {
            width: 50%;
            padding: 10px 12px;
            cursor: pointer;
        }
        .date-input-group > div:first-child {
            border-right: 1px solid var(--border-color);
        }
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .form-input, .form-select {
            border: none;
            outline: none;
            width: 100%;
            font-size: 14px;
            color: var(--text-dark);
            padding: 0;
            background: transparent;
        }
        /* Style the select dropdown */
        .form-select {
            padding: 10px 12px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%20viewBox%3D%220%200%20292.4%20292.4%22%3E%3Cpath%20fill%3D%22%23717171%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13.2-5.4H18.6c-5%200-9.6%202-13.2%205.4A17.6%2017.6%200%200%200%200%2082.5c0%205%202%209.6%205.4%2013.2l128.2%20128.2a17.6%2017.6%200%200%200%2025.2%200L287%2095.7c3.4-3.6%205.4-8.2%205.4-13.2.1-5-1.9-9.6-5.5-13.1z%22%2F%3E%3C%2Fsvg%3E');
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 10px;
        }

        .booking-button {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .booking-button:hover {
            background-color: #E0354E;
        }

        .price-summary {
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        .price-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
            color: var(--text-light);
        }
        .total-price-item {
            margin-top: 15px;
            font-weight: 700;
            font-size: 16px;
            color: var(--text-dark);
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
        }
        .alert-status {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        .alert-status.success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        .alert-status.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }


        /* --- RESPONSIVENESS --- */
        @media (max-width: 900px) {
            .details-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .booking-box {
                position: static;
                margin-top: 30px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        // Simple JS for redirection back to homepage
        function goToHome() {
            window.location.href = 'index.php';
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
        
        <?php if ($error_message): ?>
            <div style="padding: 50px; text-align: center;">
                <h1 style="color: var(--primary-color);">Oops!</h1>
                <p style="font-size: 18px; color: var(--text-light);"><?php echo htmlspecialchars($error_message); ?></p>
                <button onclick="goToHome()" style="margin-top: 20px; padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer;">Go to Homepage</button>
            </div>
        <?php else: 
            // Property details are available
            $amenities = explode(',', $property['amenities']); // Split the comma-separated amenities
            $amenity_icons = [ // A simple mapping for visual appeal
                'WiFi' => 'fas fa-wifi',
                'Pool' => 'fas fa-swimming-pool',
                'Kitchen' => 'fas fa-utensils',
                'Gym' => 'fas fa-dumbbell',
                'Parking' => 'fas fa-parking',
                'BBQ' => 'fas fa-hotdog',
                'A/C' => 'fas fa-snowflake',
                'Beach access' => 'fas fa-umbrella-beach'
            ];
            $price_per_night = $property['price_per_night'];
        ?>
            <?php if (isset($booking_status)): 
                // Determine class based on success or failure keywords
                $status_class = (strpos($booking_status, '✅') !== false) ? 'success' : 'error';
                // Basic Markdown to HTML conversion for bold text (e.g., **title** -> <strong>title</strong>)
                $display_status = str_replace(['**', '❌', '✅'], ['<strong>', '', ''], $booking_status);
            ?>
                <div class="alert-status <?php echo $status_class; ?>">
                    <?php echo $display_status; ?>
                </div>
            <?php endif; ?>

            <section class="details-header">
                <h1 class="title"><?php echo htmlspecialchars($property['title']); ?></h1>
                <div class="subtitle-bar">
                    <div class="rating-info">
                        <span class="star-icon"><i class="fas fa-star"></i></span>
                        <?php echo number_format($property['rating'], 1); ?> · 
                        <u><?php echo number_format($property['total_reviews']); ?> reviews</u>
                    </div>
                    <span>|</span>
                    <u><?php echo htmlspecialchars($property['location_city'] . ', ' . $property['location_country']); ?></u>
                </div>
            </section>
            
            <div class="image-gallery">
                <img src="<?php echo htmlspecialchars($property['image_url']); ?>" alt="Main image of <?php echo htmlspecialchars($property['title']); ?>" class="main-image">
            </div>

            <div class="details-grid">
                <div class="main-content">
                    <div class="description-box">
                        <h3><?php echo htmlspecialchars($property['property_type']); ?> hosted in <?php echo htmlspecialchars($property['location_city']); ?></h3>
                        <p><?php echo htmlspecialchars($property['description']); ?></p>
                    </div>

                    <div class="description-box">
                        <h3>What this place offers</h3>
                        <ul class="amenities-list">
                            <?php foreach ($amenities as $amenity): 
                                $amenity = trim($amenity);
                                if (!empty($amenity)):
                            ?>
                                <li class="amenity-item">
                                    <span class="amenity-icon"><i class="<?php echo htmlspecialchars($amenity_icons[$amenity] ?? 'fas fa-check'); ?>"></i></span>
                                    <?php echo htmlspecialchars($amenity); ?>
                                </li>
                            <?php endif; endforeach; ?>
                        </ul>
                    </div>

                    <div class="description-box" style="margin-top: 50px;">
                        <h3>Ratings & Reviews</h3>
                        <p style="color: var(--text-light);">*Review system not implemented in this version*</p>
                    </div>
                </div>

                <div class="booking-box-wrapper">
                    <div class="booking-box">
                        <div class="booking-price-header">
                            $<?php echo number_format($price_per_night, 0); ?> <span>per night</span>
                        </div>
                        
                        <form action="property_details.php?id=<?php echo $property['id']; ?>" method="POST" id="bookingForm">
                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                            
                            <div class="booking-form-group">
                                <div class="date-input-group">
                                    <div onclick="document.getElementById('check_in_dt').showPicker()">
                                        <label for="check_in_dt" class="form-label">CHECK-IN</label>
                                        <input type="date" id="check_in_dt" name="check_in" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div onclick="document.getElementById('check_out_dt').showPicker()">
                                        <label for="check_out_dt" class="form-label">CHECK-OUT</label>
                                        <input type="date" id="check_out_dt" name="check_out" class="form-input" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    </div>
                                </div>
                                <div style="padding: 10px 12px;">
                                    <label for="num_guests" class="form-label">GUESTS</label>
                                    <select id="num_guests" name="num_guests" class="form-select" required>
                                        <?php for ($i = 1; $i <= $property['max_guests']; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> Guest<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" name="book" class="booking-button">Reserve</button>
                            
                            <div class="price-summary">
                                <div class="price-item">
                                    <span>$<?php echo number_format($price_per_night, 2); ?> x <span id="daysDisplay">1</span> night(s)</span>
                                    <span id="subtotalDisplay">$<?php echo number_format($price_per_night, 2); ?></span>
                                </div>
                                <div class="price-item">
                                    <span>Service fee</span>
                                    <span>$20.00</span>
                                </div>
                                <div class="total-price-item">
                                    <span>Total</span>
                                    <span id="totalDisplay">$<?php echo number_format($price_per_night + 20, 2); ?></span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <footer class="footer">
        &copy; <?php echo date("Y"); ?> Airbnb Clone by Pro AI. All rights reserved.
    </footer>

    <script>
        // CLIENT-SIDE PRICE CALCULATION LOGIC
        const checkIn = document.getElementById('check_in_dt');
        const checkOut = document.getElementById('check_out_dt');
        const pricePerNight = <?php echo json_encode($price_per_night ?? 0); ?>;
        const serviceFee = 20.00;

        function calculatePrice() {
            const inDate = checkIn.value;
            const outDate = checkOut.value;

            if (inDate && outDate) {
                const date1 = new Date(inDate);
                const date2 = new Date(outDate);
                
                // Calculate difference in milliseconds
                const timeDiff = date2.getTime() - date1.getTime();
                
                // Convert to days
                const days = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (days > 0) {
                    const subtotal = days * pricePerNight;
                    const total = subtotal + serviceFee;

                    document.getElementById('daysDisplay').textContent = days;
                    document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toFixed(2);
                    document.getElementById('totalDisplay').textContent = '$' + total.toFixed(2);
                    return;
                }
            }
            // Default to 1 night if dates are invalid or incomplete
            document.getElementById('daysDisplay').textContent = 1;
            document.getElementById('subtotalDisplay').textContent = '$' + pricePerNight.toFixed(2);
            document.getElementById('totalDisplay').textContent = '$' + (pricePerNight + serviceFee).toFixed(2);
        }

        // Add event listeners
        checkIn.addEventListener('change', calculatePrice);
        checkOut.addEventListener('change', calculatePrice);

        // Initial calculation on load
        calculatePrice();
    </script>
    
    <?php $conn->close(); ?>

</body>
</html>
