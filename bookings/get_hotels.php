<?php
session_start();
require_once '../database/dbconfig.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.html?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$hotel_id = isset($_GET['hotel_id']) ? intval($_GET['hotel_id']) : 0;

if (!$hotel_id) {
    header('Location: ../search/search.html');
    exit();
}

// Get hotel details
$stmt = $conn->prepare("SELECT h.*, d.name as destination_name, d.location 
                        FROM hotels h 
                        JOIN destinations d ON h.destination_id = d.id 
                        WHERE h.id = ?");
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hotel) {
    header('Location: ../search/search.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Hotel - <?php echo htmlspecialchars($hotel['hotel_name']); ?> | TripMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary: #16034f;
            --accent: #ff6600;
            --text-dark: #1f2937;
            --text-light: #6b7280;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f9fafc 0%, #f0f4ff 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(22, 3, 79, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary), #2a0a8a);
            color: white;
            padding: 1.5rem;
        }
        .card-header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--accent);
            outline: none;
        }
        .price-summary {
            background: #fff7ed;
            border-radius: 16px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        .total {
            font-size: 1.2rem;
            font-weight: 700;
            border-top: 1px solid #fed7aa;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            color: var(--accent);
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #cc5200;
            transform: translateY(-2px);
        }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--primary);
            text-decoration: none;
        }
        @media (max-width: 768px) {
            body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-hotel"></i> <?php echo htmlspecialchars($hotel['hotel_name']); ?></h1>
                <p><?php echo htmlspecialchars($hotel['destination_name'] . ', ' . $hotel['location']); ?></p>
            </div>
            <div class="card-body">
                <form id="bookingForm">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Check-in Date</label>
                        <input type="text" id="checkin" class="datepicker" placeholder="Select check-in date" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-check"></i> Check-out Date</label>
                        <input type="text" id="checkout" class="datepicker" placeholder="Select check-out date" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-users"></i> Number of Guests</label>
                        <select id="guests">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-bed"></i> Number of Rooms</label>
                        <select id="rooms">
                            <option value="1">1 Room</option>
                            <option value="2">2 Rooms</option>
                            <option value="3">3 Rooms</option>
                        </select>
                    </div>
                    
                    <div class="price-summary">
                        <div class="price-row">
                            <span>Price per night:</span>
                            <span>₹<?php echo number_format($hotel['price_per_night'], 2); ?></span>
                        </div>
                        <div class="price-row" id="nightsRow" style="display:none;">
                            <span id="nightsText">0 nights:</span>
                            <span id="subtotal">₹0</span>
                        </div>
                        <div class="price-row total" id="totalRow" style="display:none;">
                            <span>Total Amount:</span>
                            <span id="total">₹0</span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-check-circle"></i> Confirm Booking</button>
                </form>
                <a href="javascript:history.back()" class="back-link"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const pricePerNight = <?php echo $hotel['price_per_night']; ?>;
        const hotelId = <?php echo $hotel_id; ?>;
        
        flatpickr("#checkin", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function() { calculateTotal(); updateCheckoutMin(); }
        });
        
        flatpickr("#checkout", {
            minDate: "today",
            dateFormat: "Y-m-d",
            onChange: function() { calculateTotal(); }
        });
        
        function updateCheckoutMin() {
            const checkin = document.getElementById('checkin').value;
            if (checkin) {
                const checkoutPicker = flatpickr("#checkout")[0];
                checkoutPicker.set('minDate', checkin);
            }
        }
        
        function calculateTotal() {
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const guests = parseInt(document.getElementById('guests').value);
            const rooms = parseInt(document.getElementById('rooms').value);
            
            if (checkin && checkout) {
                const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
                if (nights > 0) {
                    const subtotal = pricePerNight * nights * rooms;
                    document.getElementById('nightsRow').style.display = 'flex';
                    document.getElementById('totalRow').style.display = 'flex';
                    document.getElementById('nightsText').innerHTML = `${nights} night${nights > 1 ? 's' : ''} × ${rooms} room${rooms > 1 ? 's' : ''}:`;
                    document.getElementById('subtotal').innerHTML = `₹${subtotal.toLocaleString()}`;
                    document.getElementById('total').innerHTML = `₹${subtotal.toLocaleString()}`;
                    return nights;
                }
            }
            return 0;
        }
        
        document.getElementById('bookingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            const guests = document.getElementById('guests').value;
            const rooms = document.getElementById('rooms').value;
            
            if (!checkin || !checkout) {
                alert('Please select check-in and check-out dates');
                return;
            }
            
            const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
            if (nights <= 0) {
                alert('Check-out date must be after check-in date');
                return;
            }
            
            window.location.href = `package_booking_confirmation.php?hotel_id=${hotelId}&travelers=${guests}&checkin=${checkin}&checkout=${checkout}`;
        });
    </script>
</body>
</html>