<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "bungalow";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <title>Bungalow park</title>
</head>
<body>
  <header>
    <h1>Bungalow park</h1>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="#bungalows">Bungalows</a></li>
        <li><a href="reservations.php">Reservations</a></li>
      </ul>
  </header>

  <section class="items" >
    <section class="introduction">
      <h2>Welcome to our bungalow park!</h2>
      <div class="introduction-content">
        <p>
          Welcome to our bungalow park. We offer a wide range of bungalows for you to enjoy. 
          Our bungalows are located in the middle of nature, so you can enjoy the peace and quiet. 
          We offer bungalows for 2, 4, 6 and 8 people. 
          You can also bring your pet with you, as we offer pet-friendly bungalows. 
          We hope to see you soon!
        </p>
      </div>
    </section>
    
    <section class="photo">
      <img src="img/bungalowPark.jpg" alt="Bungalow park De Vrije Vogel">
      <h3>Bungalow park De Vrije Vogel</h3>
    </section>
  </section>

  <section class="bungalows" id="bungalows">
  <?php
$query = "SELECT b.id, b.prijs, b.foto, t.naam AS type_naam, t.personen, GROUP_CONCAT(v.naam) AS voorziening_naam, bhv.voorzieningen_idvoorzieningen
FROM bung AS b
INNER JOIN type AS t ON b.type = t.idtype
LEFT JOIN bung_has_voorzieningen AS bhv ON b.id = bhv.bung_id AND b.type = bhv.bung_type
LEFT JOIN voorzieningen AS v ON bhv.voorzieningen_idvoorzieningen = v.idvoorzieningen
WHERE b.visible = 1
GROUP BY b.id";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    // Fetch data and generate HTML
    while ($row = $result->fetch_assoc()) {
        echo '<div class="item">';
        $imagePath = $row['foto'];
        if (strpos($imagePath, 'img/') !== 0) {
            $imagePath = 'img/' . $imagePath;
        }
        echo '<img src="' . $imagePath . '" alt="Bungalow image">';
        echo '<span>';
        echo '<h3>Bungalow ' . $row['type_naam'] . '</h3>';
        echo '<ul>';
        echo '<li>Prijs: € ' . $row['prijs'] . ' per nacht</li>';
        echo '<li>Aantal Personen: ' . $row['personen'] . '</li>';
        echo '<li>Voorzieningen:';
        echo '<ul class="voorzieningen">';
        
        // Splitting concatenated amenities and displaying each one separately
        $voorzieningen = explode(',', $row['voorziening_naam']);
        foreach ($voorzieningen as $voorziening) {
            echo '<li>' . $voorziening . '</li>';
        }
        
        echo '</ul>';
        echo '</li>';
        echo '</ul>';
        echo '</span>';
        echo '</div>';
    }
} else {
    echo "Error: " . $conn->error;
}
?>

  </section>
  <script src="https://kit.fontawesome.com/c6d023de9c.js" crossorigin="anonymous"></script>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>
