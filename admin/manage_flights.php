<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

include '../database/dbconfig.php';

// Get destination ID from URL
$destination_id = isset($_GET['destination_id']) && is_numeric($_GET['destination_id']) ? (int) $_GET['destination_id'] : 0;

if ($destination_id === 0) {
    $_SESSION['message'] = "Invalid destination ID";
    header("Location: add_destination_on_admin.php");
    exit();
}

// Get destination info
$dest_query = $conn->prepare("SELECT name FROM destinations WHERE id = ?");
$dest_query->bind_param("i", $destination_id);
$dest_query->execute();
$dest_result = $dest_query->get_result();

if ($dest_result->num_rows === 0) {
    $_SESSION['message'] = "Destination not found";
    header("Location: add_destination_on_admin.php");
    exit();
}

$destination = $dest_result->fetch_assoc();
$destination_name = $destination['name'];

// Handle form submission for adding/editing flight
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new flight
        // Add new flight
        if ($_POST['action'] === 'add') {
            $departure_city = $conn->real_escape_string($_POST['departure_city']);
            $airline = $conn->real_escape_string($_POST['airline']);
            $flight_type = $conn->real_escape_string($_POST['flight_type']);
            $price_per_person = (float) $_POST['price_per_person'];
            $duration_hours = (float) $_POST['duration_hours'];
            $stops = (int) $_POST['stops'];
            $departure_time = $conn->real_escape_string($_POST['departure_time']);
            $arrival_time = $conn->real_escape_string($_POST['arrival_time']);
            $flight_class = $conn->real_escape_string($_POST['flight_class']);
            $baggage_allowance = $conn->real_escape_string($_POST['baggage_allowance']);
            $refundable = isset($_POST['refundable']) ? 1 : 0;
            $meal_included = isset($_POST['meal_included']) ? 1 : 0;

            // FIX: Added city_id to the INSERT statement
            $stmt = $conn->prepare("INSERT INTO flights (destination_id, city_id, from_city, to_city, airline, flight_type, price, duration, stops, departure_time, arrival_time, flight_class, baggage_allowance, refundable, meal_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                die("Prepare Error: " . $conn->error);
            }

            // FIX: Added 'i' for the extra integer, and mapped $destination_id to the city_id column
            $stmt->bind_param(
                "iissssddissssii",
                $destination_id,     // Maps to destination_id
                $destination_id,     // Maps to city_id
                $departure_city,     // Maps to from_city
                $destination_name,   // Maps to to_city
                $airline,
                $flight_type,
                $price_per_person,
                $duration_hours,
                $stops,
                $departure_time,
                $arrival_time,
                $flight_class,
                $baggage_allowance,
                $refundable,
                $meal_included
            );

            if ($stmt->execute()) {
                $_SESSION['message'] = "Flight added successfully!";
            } else {
                die("Execute Error: " . $stmt->error);
            }
        }

        // Edit flight
        elseif ($_POST['action'] === 'edit' && isset($_POST['flight_id'])) {
            $flight_id = (int) $_POST['flight_id'];
            $departure_city = $conn->real_escape_string($_POST['departure_city']);
            $airline = $conn->real_escape_string($_POST['airline']);
            $flight_type = $conn->real_escape_string($_POST['flight_type']);
            $price_per_person = (float) $_POST['price_per_person'];
            $duration_hours = (float) $_POST['duration_hours'];
            $stops = (int) $_POST['stops'];
            $departure_time = $conn->real_escape_string($_POST['departure_time']);
            $arrival_time = $conn->real_escape_string($_POST['arrival_time']);
            $flight_class = $conn->real_escape_string($_POST['flight_class']);
            $baggage_allowance = $conn->real_escape_string($_POST['baggage_allowance']);
            $refundable = isset($_POST['refundable']) ? 1 : 0;
            $meal_included = isset($_POST['meal_included']) ? 1 : 0;

            $stmt = $conn->prepare("UPDATE flights SET from_city = ?, airline = ?, flight_type = ?, price = ?, duration = ?, stops = ?, departure_time = ?, arrival_time = ?, flight_class = ?, baggage_allowance = ?, refundable = ?, meal_included = ? WHERE id = ? AND destination_id = ?");
            $stmt->bind_param(
                "sssddisssssiii",
                $departure_city,
                $airline,
                $flight_type,
                $price_per_person,
                $duration_hours,
                $stops,
                $departure_time,
                $arrival_time,
                $flight_class,
                $baggage_allowance,
                $refundable,
                $meal_included,
                $flight_id,
                $destination_id
            );
            if ($stmt->execute()) {
                $_SESSION['message'] = "Flight updated successfully!";
            } else {
                $_SESSION['message'] = "Error updating flight: " . $conn->error;
            }
        }

        header("Location: manage_flights.php?destination_id=" . $destination_id);
        exit();
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $flight_id = (int) $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM flights WHERE id = ? AND destination_id = ?");
    $stmt->bind_param("ii", $flight_id, $destination_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Flight deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting flight: " . $conn->error;
    }

    header("Location: manage_flights.php?destination_id=" . $destination_id);
    exit();
}

// Fetch all flights for this destination
$flights_query = $conn->prepare("SELECT *, from_city AS departure_city, price AS price_per_person, duration AS duration_hours FROM flights WHERE destination_id = ? ORDER BY id DESC");
$flights_query->bind_param("i", $destination_id);
$flights_query->execute();
$flights_result = $flights_query->get_result();

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_query = $conn->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin = $admin_result->fetch_assoc() ?? ['name' => 'Unknown', 'email' => '', 'profile_pic' => NULL];
?>

<?php include 'admin_header.php'; ?>

<div class="main-content page">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2.2rem; color: var(--text-main); margin-bottom: 0.5rem;">
                <i class="fas fa-plane" style="color: #3b82f6;"></i> Manage Flights
            </h1>
            <p style="color: var(--text-muted); font-size: 1.1rem;">
                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($destination_name) ?>
            </p>
        </div>
        <a href="add_destination_on_admin.php" class="btn btn-outline" style="border-radius: 50px;">
            <i class="fas fa-arrow-left"></i> Back to Destinations
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
            <div class="alert <?= strpos($_SESSION['message'], 'Error') === false ? 'alert-success' : 'alert-danger' ?> fade-in">
                <i class="fas fa-<?= strpos($_SESSION['message'], 'Error') === false ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $_SESSION['message'] ?>
            </div>
            <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="widget-card fade-in" style="margin-bottom: 2rem; background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 16px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding: 1.5rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><span style="color: #3b82f6;">Step 2:</span> Add New Flight</h2>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="departure_city">Departure City *</label>
                        <input type="text" id="departure_city" name="departure_city" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="airline">Airline *</label>
                        <input type="text" id="airline" name="airline" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="flight_type">Flight Type *</label>
                        <select id="flight_type" name="flight_type" class="form-control" required>
                            <option value="low">Low Budget</option>
                            <option value="medium">Medium Budget</option>
                            <option value="high">High Budget / Luxury</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_person">Price Per Person (₹) *</label>
                        <input type="number" id="price_per_person" name="price_per_person" step="0.01" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration_hours">Duration (hours)</label>
                        <input type="number" id="duration_hours" name="duration_hours" step="0.1" min="0" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="stops">Number of Stops</label>
                        <input type="number" id="stops" name="stops" min="0" class="form-control" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="departure_time">Departure Time</label>
                        <input type="text" id="departure_time" name="departure_time" class="form-control" placeholder="e.g., 10:30 AM">
                    </div>
                    
                    <div class="form-group">
                        <label for="arrival_time">Arrival Time</label>
                        <input type="text" id="arrival_time" name="arrival_time" class="form-control" placeholder="e.g., 02:45 PM">
                    </div>
                    
                    <div class="form-group">
                        <label for="flight_class">Flight Class</label>
                        <select id="flight_class" name="flight_class" class="form-control">
                            <option value="Economy">Economy</option>
                            <option value="Premium Economy">Premium Economy</option>
                            <option value="Business Class">Business Class</option>
                            <option value="First Class">First Class</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="baggage_allowance">Baggage Allowance</label>
                        <input type="text" id="baggage_allowance" name="baggage_allowance" class="form-control" placeholder="e.g., 15kg check-in + 7kg cabin">
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                            <input type="checkbox" name="refundable" value="1"> Refundable
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                            <input type="checkbox" name="meal_included" value="1"> Meal Included
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 50px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                        <i class="fas fa-save"></i> Add Flight
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="widget-card fade-in" style="background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 16px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--card-border); padding: 1.5rem;">
            <h2 style="font-size: 1.8rem; color: var(--text-main);"><i class="fas fa-list"></i> Existing Flights</h2>
            <span class="status-badge status-active">Total: <?= $flights_result->num_rows ?></span>
        </div>
        <div class="card-body" style="padding: 1.5rem;">
            <?php if ($flights_result->num_rows > 0): ?>
                    <div class="flights-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem;">
                        <?php while ($flight = $flights_result->fetch_assoc()): ?>
                                <div class="flight-card widget-card" style="padding: 1.5rem; background: var(--bg-surface); border: 1px solid var(--card-border); border-radius: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <h3 style="font-size: 1.3rem; color: var(--text-main); margin: 0;"><?= htmlspecialchars($flight['airline']) ?></h3>
                                        <span class="flight-type-badge" style="background: <?= $flight['flight_type'] === 'low' ? '#10b981' : ($flight['flight_type'] === 'medium' ? '#f59e0b' : '#ef4444') ?>; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                                            <?= ucfirst($flight['flight_type']) ?>
                                        </span>
                                    </div>
                            
                                    <div style="margin-bottom: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem; font-weight: 600; color: var(--text-main);">
                                            <i class="fas fa-city" style="color: #3b82f6;"></i>
                                            <?= htmlspecialchars($flight['departure_city']) ?>
                                        </div>
                                        <div style="display: flex; align-items: center; justify-content: space-between; margin: 0.5rem 0;">
                                            <span style="font-size: 0.9rem; color: var(--text-muted);">
                                                <i class="fas fa-clock"></i> <?= htmlspecialchars($flight['departure_time'] ?? 'N/A') ?>
                                            </span>
                                            <i class="fas fa-long-arrow-alt-right" style="color: var(--text-muted);"></i>
                                            <span style="font-size: 0.9rem; color: var(--text-muted);">
                                                <i class="fas fa-clock"></i> <?= htmlspecialchars($flight['arrival_time'] ?? 'N/A') ?>
                                            </span>
                                        </div>
                                    </div>
                            
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1rem; padding: 1rem 0; border-top: 1px solid var(--card-border); border-bottom: 1px solid var(--card-border);">
                                        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                            <i class="fas fa-tag"></i> Class: <?= htmlspecialchars($flight['flight_class'] ?? 'Economy') ?>
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                            <i class="fas fa-clock"></i> <?= number_format($flight['duration_hours'] ?? 0, 1) ?> hrs
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                            <i class="fas fa-map-marker-alt"></i> <?= $flight['stops'] ?> stop(s)
                                        </span>
                                        <span style="display: flex; align-items: center; gap: 5px; font-size: 0.9rem; color: var(--text-muted);">
                                            <i class="fas fa-suitcase"></i> <?= htmlspecialchars($flight['baggage_allowance'] ?? 'N/A') ?>
                                        </span>
                                    </div>
                            
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);">
                                            ₹<?= number_format($flight['price_per_person']) ?>
                                            <small style="font-size: 0.8rem; color: var(--text-muted);">/person</small>
                                        </span>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <?php if ($flight['refundable']): ?>
                                                    <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 4px 8px; border-radius: 20px; font-size: 0.7rem;">Refundable</span>
                                            <?php endif; ?>
                                            <?php if ($flight['meal_included']): ?>
                                                    <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 4px 8px; border-radius: 20px; font-size: 0.7rem;">Meal</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                            
                                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px solid var(--card-border); padding-top: 1rem;">
                                        <button onclick="editFlight(<?= htmlspecialchars(json_encode($flight)) ?>)" class="btn btn-outline" style="flex: 1; border-radius: 50px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <a href="?destination_id=<?= $destination_id ?>&delete=<?= $flight['id'] ?>" class="btn btn-danger" style="flex: 1; border-radius: 50px;" onclick="return confirm('Are you sure you want to delete this flight?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                        <?php endwhile; ?>
                    </div>
            <?php else: ?>
                    <div style="text-align: center; padding: 4rem 2rem;">
                        <i class="fas fa-plane" style="font-size: 4rem; margin-bottom: 1.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                        <h3 style="color: var(--text-main); font-size: 1.5rem; margin-bottom: 0.5rem;">No flights added yet</h3>
                        <p style="color: var(--text-muted);">Use the form above to add your first flight</p>
                    </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="editFlightModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-surface); max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; border-radius: 24px; padding: 2rem; position: relative; border: 1px solid var(--card-border);">
        <button onclick="closeEditModal()" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 style="font-size: 1.8rem; margin-bottom: 2rem; color: var(--text-main);">
            <i class="fas fa-edit" style="color: #3b82f6;"></i> Edit Flight
        </h2>
        
        <form method="POST" id="editFlightForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="flight_id" id="edit_flight_id">
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="edit_departure_city">Departure City *</label>
                    <input type="text" id="edit_departure_city" name="departure_city" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_airline">Airline *</label>
                    <input type="text" id="edit_airline" name="airline" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_flight_type">Flight Type *</label>
                    <select id="edit_flight_type" name="flight_type" class="form-control" required>
                        <option value="low">Low Budget</option>
                        <option value="medium">Medium Budget</option>
                        <option value="high">High Budget / Luxury</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_price_per_person">Price Per Person (₹) *</label>
                    <input type="number" id="edit_price_per_person" name="price_per_person" step="0.01" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_duration_hours">Duration (hours)</label>
                    <input type="number" id="edit_duration_hours" name="duration_hours" step="0.1" min="0" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_stops">Number of Stops</label>
                    <input type="number" id="edit_stops" name="stops" min="0" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_departure_time">Departure Time</label>
                    <input type="text" id="edit_departure_time" name="departure_time" class="form-control" placeholder="e.g., 10:30 AM">
                </div>
                
                <div class="form-group">
                    <label for="edit_arrival_time">Arrival Time</label>
                    <input type="text" id="edit_arrival_time" name="arrival_time" class="form-control" placeholder="e.g., 02:45 PM">
                </div>
                
                <div class="form-group">
                    <label for="edit_flight_class">Flight Class</label>
                    <select id="edit_flight_class" name="flight_class" class="form-control">
                        <option value="Economy">Economy</option>
                        <option value="Premium Economy">Premium Economy</option>
                        <option value="Business Class">Business Class</option>
                        <option value="First Class">First Class</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_baggage_allowance">Baggage Allowance</label>
                    <input type="text" id="edit_baggage_allowance" name="baggage_allowance" class="form-control" placeholder="e.g., 15kg check-in + 7kg cabin">
                </div>
                
                <div class="form-group" style="grid-column: span 2; display: flex; gap: 2rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" name="refundable" id="edit_refundable" value="1"> Refundable
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-weight: 600;">
                        <input type="checkbox" name="meal_included" id="edit_meal_included" value="1"> Meal Included
                    </label>
                </div>
            </div>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="border-radius: 50px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                    <i class="fas fa-save"></i> Update Flight
                </button>
                <button type="button" onclick="closeEditModal()" class="btn btn-outline" style="border-radius: 50px;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editFlight(flight) {
    document.getElementById('edit_flight_id').value = flight.id;
    document.getElementById('edit_departure_city').value = flight.departure_city;
    document.getElementById('edit_airline').value = flight.airline;
    document.getElementById('edit_flight_type').value = flight.flight_type;
    document.getElementById('edit_price_per_person').value = flight.price_per_person;
    document.getElementById('edit_duration_hours').value = flight.duration_hours || '';
    document.getElementById('edit_stops').value = flight.stops || 0;
    document.getElementById('edit_departure_time').value = flight.departure_time || '';
    document.getElementById('edit_arrival_time').value = flight.arrival_time || '';
    document.getElementById('edit_flight_class').value = flight.flight_class || 'Economy';
    document.getElementById('edit_baggage_allowance').value = flight.baggage_allowance || '';
    
    document.getElementById('edit_refundable').checked = flight.refundable == 1;
    document.getElementById('edit_meal_included').checked = flight.meal_included == 1;
    
    document.getElementById('editFlightModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editFlightModal').style.display = 'none';
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('editFlightModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
});
</script>

<style>
/* FULLY FIXED FORM STYLES */
.flight-card {
    transition: transform 0.3s, box-shadow 0.3s;
}

.flight-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px var(--shadow-color);
}

.form-row {
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 0.6rem;
    font-weight: 700;
    color: var(--text-main);
    font-size: 0.95rem;
}

/* Hardened Input Styles */
.form-control {
    box-sizing: border-box; /* This prevents inputs from breaking out of the grid */
    width: 100%;
    padding: 0.85rem 1.2rem;
    border: 1px solid var(--card-border);
    border-radius: 10px;
    font-size: 1rem;
    background-color: var(--bg-base);
    color: var(--text-main);
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-control:focus {
    background-color: var(--bg-surface);
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}

select.form-control {
    appearance: auto;
}

select.form-control option {
    padding: 0.5rem;
    background-color: var(--bg-surface);
    color: var(--text-main);
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 700;
}
.status-active {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}

/* Alerts */
.alert {
    padding: 1.2rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 12px;
}
.alert-success {
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.2);
}
.alert-danger {
    background-color: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem 1.8rem;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    gap: 8px;
    font-size: 1rem;
}
.btn-outline {
    background: transparent;
    border: 2px solid var(--card-border);
    color: var(--text-main);
}
.btn-outline:hover {
    background: var(--bg-base);
    border-color: #3b82f6;
    color: #3b82f6;
}
.btn-danger {
    background: transparent;
    border: 2px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
}
.btn-danger:hover {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

.page {
    max-width: 1200px;
}
</style>

<?php include 'admin_footer.php'; ?>