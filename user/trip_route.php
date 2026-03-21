<?php
include '../database/dbconfig.php';

$destination_id = intval($_GET['id']);

$query = $conn->prepare("SELECT name, latitude, longitude FROM destinations WHERE id=?");
$query->bind_param("i",$destination_id);
$query->execute();
$result = $query->get_result();

$spots = [];

while($row = $result->fetch_assoc()){
    $spots[] = $row;
}

?>
<!DOCTYPE html>
<html>
<head>

<title>Trip Route Map</title>

<style>

body{
font-family:Arial;
background:#f4f6fb;
margin:0;
}

#map{
height:90vh;
width:100%;
}

.header{
padding:15px;
background:#435ee2;
color:white;
font-size:20px;
font-weight:bold;
}

</style>

</head>

<body>

<div class="header">
Trip Route Visualization
</div>

<div id="map"></div>

<script>

let spots = <?php echo json_encode($spots); ?>;

function initMap(){

let map = new google.maps.Map(document.getElementById("map"),{
zoom:12,
center:{lat:parseFloat(spots[0].latitude),lng:parseFloat(spots[0].longitude)}
});

let directionsService = new google.maps.DirectionsService();
let directionsRenderer = new google.maps.DirectionsRenderer({
polylineOptions:{
strokeColor:"#1a73e8",
strokeWeight:5
}
});

directionsRenderer.setMap(map);

let waypoints=[];

for(let i=1;i<spots.length-1;i++){

waypoints.push({
location:{
lat:parseFloat(spots[i].latitude),
lng:parseFloat(spots[i].longitude)
},
stopover:true
});

}

let request = {

origin:{
lat:parseFloat(spots[0].latitude),
lng:parseFloat(spots[0].longitude)
},

destination:{
lat:parseFloat(spots[spots.length-1].latitude),
lng:parseFloat(spots[spots.length-1].longitude)
},

waypoints:waypoints,

travelMode:"DRIVING"

};

directionsService.route(request,function(result,status){

if(status=="OK"){
directionsRenderer.setDirections(result);
}

});

}

</script>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>

</body>
</html>