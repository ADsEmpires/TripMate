<?php
session_start();

/* ================= AJAX HANDLER (MUST BE FIRST) ================= */
if (isset($_GET['ajax'])) {

    header('Content-Type: application/json');
    define('OPENWEATHER_API_KEY', 'b4fe517a83b0e5679af65062c7fd92cd');

    function fetchWeather($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    if(isset($_GET['lat']) && isset($_GET['lon'])){
        $lat = $_GET['lat'];
        $lon = $_GET['lon'];
        $current = fetchWeather(
            "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=" .
            OPENWEATHER_API_KEY . "&units=metric"
        );
    } else {
        $city = urlencode($_GET['city']);
        $current = fetchWeather(
            "https://api.openweathermap.org/data/2.5/weather?q=$city&appid=" .
            OPENWEATHER_API_KEY . "&units=metric"
        );
    }

    if (!$current || $current['cod'] != 200) {
        echo json_encode(['error' => 'City not found']);
        exit;
    }

    $forecast = fetchWeather(
        "https://api.openweathermap.org/data/2.5/forecast?q={$current['name']}&appid=" .
        OPENWEATHER_API_KEY . "&units=metric"
    );

    echo json_encode([
        'current' => $current,
        'forecast' => $forecast
    ]);
    exit;
}
/* ================= END AJAX ================= */
?>

<?php
require_once '../database/dbconfig.php';

if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: ../auth/login.php');
    exit();
}

$is_admin = isset($_SESSION['admin_id']) || isset($_SESSION['admin_logged_in']);
if ($is_admin) {
    include 'admin_header.php';
} else {
    include '../user/user_header.php';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.weather-wrapper { max-width:1300px; margin:auto; }

.weather-hero {
    background:linear-gradient(135deg,#1e3c72,#2a5298);
    padding:35px;
    border-radius:20px;
    color:white;
    text-align:center;
    margin-bottom:30px;
}

.weather-hero input {
    padding:12px 20px;
    width:280px;
    border-radius:50px;
    border:none;
    outline:none;
}

.weather-hero button {
    padding:12px 25px;
    border-radius:50px;
    border:none;
    background:white;
    color:#1e3c72;
    cursor:pointer;
    margin-left:10px;
    font-weight:bold;
}

.weather-card {
    background:linear-gradient(145deg,#ffffff,#f8fafc);
    padding:30px;
    border-radius:20px;
    box-shadow:0 8px 25px rgba(0,0,0,0.08);
    border:1px solid #e2e8f0;
}

.weather-main {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.weather-temp {
    font-size:60px;
    font-weight:bold;
    color:#1e3c72;
}

.details-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
    gap:15px;
    margin-top:30px;
}

.detail-box {
    background:linear-gradient(145deg,#f1f5f9,#e2e8f0);
    padding:18px;
    border-radius:15px;
    text-align:center;
    font-weight:500;
    transition:0.3s;
}

.detail-box:hover {
    background:#e2e8f0;
}
</style>

<div class="main-content">
<div class="weather-wrapper">

<div class="weather-hero">
    <h2><i class="fas fa-cloud-sun"></i> Smart Travel Weather Dashboard</h2>
    <br>
    <input type="text" id="cityInput" placeholder="Search city...">
    <button onclick="getWeather()">Search</button>
</div>

<div id="weatherResult" class="weather-card">
    Detecting your location...
</div>

</div>
</div>

<script>
window.onload = function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos){
            getWeatherByCoords(pos.coords.latitude, pos.coords.longitude);
        }, function(){
            getWeather("Bankura");
        });
    } else {
        getWeather("Bankura");
    }
};

async function getWeather(cityName=null) {
    const city = cityName || document.getElementById("cityInput").value;
    const res = await fetch("weather_dashboard.php?ajax=1&city=" + encodeURIComponent(city));
    const data = await res.json();
    processWeather(data);
}

async function getWeatherByCoords(lat, lon) {
    const res = await fetch("weather_dashboard.php?ajax=1&lat=" + lat + "&lon=" + lon);
    const data = await res.json();
    processWeather(data);
}

function processWeather(data){

    const box = document.getElementById("weatherResult");

    if(data.error){
        box.innerHTML = "City not found";
        return;
    }

    const c = data.current;
    const forecast = data.forecast.list;

    const timezone = c.timezone;
    const sunrise = new Date((c.sys.sunrise + timezone)*1000)
        .toUTCString().match(/(\d{2}:\d{2})/)[0];

    const sunset = new Date((c.sys.sunset + timezone)*1000)
        .toUTCString().match(/(\d{2}:\d{2})/)[0];

    const iconUrl = "https://openweathermap.org/img/wn/" + c.weather[0].icon + "@4x.png";

    let weeklyTemps = forecast.slice(0,7).map(f => f.main.temp);
    let weeklyAvg = (weeklyTemps.reduce((a,b)=>a+b,0) / weeklyTemps.length).toFixed(1);

    let monthlyTrend = weeklyAvg > 35 ? "Hot Month Expected"
                     : weeklyAvg < 10 ? "Cold Month Expected"
                     : "Moderate Climate Month";

    const trip = analyzeWeather(c);

    box.innerHTML = `
        <div class="weather-main">
            <div>
                <h3>${c.name}, ${c.sys.country}</h3>
                <p style="text-transform:capitalize;font-size:18px;">
                    ${c.weather[0].description}
                </p>
                <div class="weather-temp">${Math.round(c.main.temp)}°C</div>
            </div>
            <div>
                <img src="${iconUrl}" width="120">
            </div>
        </div>

        <div style="
            margin-top:20px;
            padding:20px;
            border-radius:15px;
            background:${trip.color};
            color:white;
            font-weight:bold;
            text-align:center;
            font-size:18px;
        ">
            ${trip.status}
        </div>

        <div class="details-grid">
            <div class="detail-box">Feels Like<br>${Math.round(c.main.feels_like)}°C</div>
            <div class="detail-box">Humidity<br>${c.main.humidity}%</div>
            <div class="detail-box">Wind<br>${Math.round(c.wind.speed*3.6)} km/h</div>
            <div class="detail-box">Pressure<br>${c.main.pressure} hPa</div>
            <div class="detail-box">Visibility<br>${(c.visibility/1000).toFixed(1)} km</div>
            <div class="detail-box">Cloud Cover<br>${c.clouds.all}%</div>
            <div class="detail-box">Sunrise<br>${sunrise}</div>
            <div class="detail-box">Sunset<br>${sunset}</div>
            <div class="detail-box">Weekly Avg<br>${weeklyAvg}°C</div>
            <div class="detail-box">Monthly Trend<br>${monthlyTrend}</div>
        </div>
    `;
}

function analyzeWeather(c){

    const temp = c.main.temp;
    const wind = c.wind.speed * 3.6;
    const condition = c.weather[0].main.toLowerCase();

    if(condition.includes("thunderstorm") || condition.includes("tornado")){
        return { status: "🔴 NOT SAFE FOR TRAVEL", color:"#dc2626" };
    }

    if(condition.includes("rain") || condition.includes("snow") || wind > 40){
        return { status: "🟡 TRAVEL WITH CAUTION", color:"#f59e0b" };
    }

    if(temp > 38 || temp < 5){
        return { status: "🟡 EXTREME TEMPERATURE – PLAN CAREFULLY", color:"#ea580c" };
    }

    return { status: "🟢 PERFECT WEATHER FOR TRIP", color:"#16a34a" };
}
</script>

<?php
if ($is_admin) {
    include 'admin_footer.php';
} else {
    include '../user/user_footer.php';
}
?>