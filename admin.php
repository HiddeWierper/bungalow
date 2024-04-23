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
<title>admin-panel</title>
</head>

<body>
<header>
  <h1>Admin</h1>
  <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="index.php#bungalows">Bungalows</a></li>
      <li onclick="showVoorzieningen()"><a href="#">Reservations</a></li>
  </ul>
</header>

<section class="admin-bungalows">
  <div id="editVoorzieningen">
    <button onclick="showVoorzieningen()" class="close" type="button"><i class="fas fa-times"></i></button>
    <div class="editVoorzieningenContent">
      <?php
      function showVoorzieningen($conn){
        $queryVoorzieningen = "SELECT * FROM voorzieningen";
        $resultVoorzieningen = $conn->query($queryVoorzieningen);
        
        // Loop door alle voorzieningen en maak een formulier voor elke voorziening
        while ($rowVoorzieningen = $resultVoorzieningen->fetch_assoc()) {
          echo '<form action="admin.php" method="post">';
          echo '<span>';
          echo '<input type="hidden" name="voorziening_id" value="' . $rowVoorzieningen['idvoorzieningen'] . '">';
          echo '<input type="text" id="voorziening_naam_' . $rowVoorzieningen['idvoorzieningen'] . '" name="voorziening_naam" value="' . $rowVoorzieningen['naam'] . '">';
          echo '<button type="submit" name="action" value="Update Voorziening"><i class="fas fa-edit"></i></button>';
          echo '<button type="submit" name="action" value="Delete Voorziening"><i class="fas fa-trash"></i></button>';
          
          echo '</span>';

          echo '</form>';
        }
        echo '<form action="admin.php" method="post">';
        echo '<button style="margin-top: 1rem; " type="submit" name="newV"><i class="fas fa-plus"></i></button>';
        echo '</form>';
      }
      
      // Als het formulier wordt verzonden, update dan de voorziening
      session_start();
      if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['voorziening_id'])) {
          $voorzieningId = $_POST['voorziening_id'];
          $newVoorzieningNaam = $_POST['voorziening_naam'];

          if ($_POST['action'] == 'Update Voorziening') {
              $stmt = $conn->prepare("UPDATE voorzieningen SET naam = ? WHERE idvoorzieningen = ?");
              $stmt->bind_param("si", $newVoorzieningNaam, $voorzieningId);
              $stmt->execute();
          } elseif ($_POST['action'] == 'Delete Voorziening') {
              // Verwijder eerst de gerelateerde rijen in de bung_has_voorzieningen tabel
              $stmt = $conn->prepare("DELETE FROM bung_has_voorzieningen WHERE voorzieningen_idvoorzieningen = ?");
              $stmt->bind_param("i", $voorzieningId);
              if (!$stmt->execute()) {
                  $_SESSION['foutmelding'] = "Fout bij het verwijderen van de gerelateerde voorzieningen: " . $stmt->error;
              } else {
                  // Als dat succesvol is, verwijder dan de rij in de voorzieningen tabel
                  $stmt = $conn->prepare("DELETE FROM voorzieningen WHERE idvoorzieningen = ?");
                  $stmt->bind_param("i", $voorzieningId);
                  if (!$stmt->execute()) {
                      $_SESSION['foutmelding'] = "Fout bij het verwijderen van de voorziening: " . $stmt->error;
                  }
              }
          }
      }

      // Als het formulier voor het toevoegen van een nieuwe voorziening wordt ingediend
      elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['newV'])) {
          $newVoorzieningNaam = 'Nieuw'; // Vervang dit door de gewenste naam
          $stmt = $conn->prepare("INSERT INTO `voorzieningen` (`naam`) VALUES (?)");
          $stmt->bind_param("s", $newVoorzieningNaam);
          if (!$stmt->execute()) {
              echo "Fout bij het toevoegen van de nieuwe voorziening: " . $stmt->error;
          } 
      }

      // Als er een foutmelding is opgeslagen in de sessie, toon deze dan
      if (isset($_SESSION['foutmelding'])) {
          echo $_SESSION['foutmelding'];
      }

      // Toon de lijst met voorzieningen
      showVoorzieningen($conn);
      ?>
    </div>

  </div>
  <section class="edit-bungalows">
      <?php
      error_reporting(E_ALL);
      ini_set('display_errors', 1);
      $query = "SELECT b.id, b.type, b.prijs, b.foto, t.naam AS type_naam, t.personen, GROUP_CONCAT(v.naam) AS voorziening_naam, bhv.voorzieningen_idvoorzieningen
      FROM bung AS b
      INNER JOIN type AS t ON b.type = t.idtype
      LEFT JOIN bung_has_voorzieningen AS bhv ON b.id = bhv.bung_id AND b.type = bhv.bung_type
      LEFT JOIN voorzieningen AS v ON bhv.voorzieningen_idvoorzieningen = v.idvoorzieningen
      GROUP BY b.id";
      
      if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['from_generate_bungalow_form'])) {
        // Process form data and update bungalow information
        $bungalowId = $_POST['bungalow_id'];
        $newPrice = $_POST['price'];
        $newPersons = $_POST['people'];
        $type = $_POST['type'];
        
        // Update prijs en aantal personen van de bungalow
        updatePrice($conn, $newPrice, $type, $bungalowId);
        updatePers($conn, $newPersons, $bungalowId);
        updateType($conn, $type, $bungalowId);

        $uploadDir = 'img/'; // specify your upload directory
        $uploadFile = $uploadDir . basename($_FILES['image']['name']);
        
        
        if (isset($_POST['voorzieningen'])) {
          // Update voorzieningen van de bungalow
              $selectedVoorzieningen = $_POST['voorzieningen'];

              // Verwijder bestaande voorzieningen van de bungalow
              $deleteQuery = "DELETE FROM bung_has_voorzieningen WHERE bung_id = $bungalowId";
              if (!$conn->query($deleteQuery)) {
                  echo "Error deleting existing amenities: " . $conn->error;
                }
                
                // Voeg geselecteerde voorzieningen toe aan de bungalow
                foreach ($selectedVoorzieningen as $voorzieningId) {
                  $insertQuery = "INSERT INTO bung_has_voorzieningen (bung_id, bung_type, voorzieningen_idvoorzieningen) VALUES ($bungalowId, (SELECT type FROM bung WHERE id = $bungalowId), $voorzieningId)";
                  if (!$conn->query($insertQuery)) {
                      echo "Error adding amenity: " . $conn->error;
                    }
                  }
                }
              }
              
              if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new'])) {
                $stmt = $conn->prepare("INSERT INTO bung (id, type, prijs, foto, visible) VALUES (DEFAULT, DEFAULT,  DEFAULT, DEFAULT, DEFAULT)");
                $stmt->execute();
              }

              if (isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
                  // Een bestand is geüpload
                if ($_FILES['image']['error'] > 0) {
                    // Er is een fout opgetreden bij het uploaden van het bestand
                    echo "Error occurred during file upload: " . $_FILES['image']['error'];
                } else {  
                // Probeer het geüploade bestand naar de doelmap te verplaatsen
                  if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                      // Bestand succesvol geüpload
                      $newImage = basename($_FILES['image']['name']);
                      // Update de bungalow met het nieuwe afbeeldingspad
                      updateImage($conn, $newImage, $bungalowId);
                    } else {
                      // Bestand is niet verplaatst naar de doelmap
                      echo "Failed to move uploaded file to target directory.";
                    }
                  }
              }
        $visibleName = "Verberg";

      function updateImage($conn, $newImage, $bungalowId){
        echo $bungalowId;
          $stmt = $conn->prepare("UPDATE bung SET foto = ? WHERE id = ?");
          $stmt->bind_param("si", $newImage, $bungalowId);
          if ($stmt->execute() === false) {
              echo "Error updating image path: " . $stmt->error;
          }
      }

      function updatePrice($conn, $newPrice, $type, $bungalowId)
      {
          $stmt = $conn->prepare("UPDATE bung SET prijs = ? WHERE id = ?");
          $stmt->bind_param("si", $newPrice, $bungalowId);
          $stmt->execute();
      }
      function updateType($conn, $type, $bungalowId)
      {
          // Voorbereiden van de SQL-query om gerelateerde rijen te verwijderen
          $stmt_delete_related = $conn->prepare("DELETE FROM bung_has_voorzieningen WHERE bung_id = ?");
          $stmt_delete_related->bind_param("i", $bungalowId);
          
          // Uitvoeren van de verwijderquery
          if ($stmt_delete_related->execute() === false) {
              echo "Fout bij het verwijderen van gerelateerde rijen: " . $stmt_delete_related->error;
              return; // Stop de functie als er een fout optreedt
          }

          // Voorbereiden van de SQL-query om het type van de bungalow bij te werken
          $stmt_update = $conn->prepare("UPDATE bung SET type = ? WHERE id = ?");
          $stmt_update->bind_param("ii", $type, $bungalowId);
          
          // Uitvoeren van de update-query en controleren op fouten
          if ($stmt_update->execute() === false) {
              echo "Fout bij het bijwerken van het bungalowtype: " . $stmt_update->error;
              return; // Stop de functie als er een fout optreedt
          }
      }


        
        function updatePers($conn, $newPersons, $bungalowId)
        {
            $stmt = $conn->prepare("UPDATE type SET personen = ? WHERE idtype = (SELECT type FROM bung WHERE id = ?)");
            $stmt->bind_param("ii", $newPersons, $bungalowId);
            if ($stmt->execute() === false) {
                echo "Error executing SQL query: " . $stmt->error;
            }
        }

      
      // Check if the "Visible" button is clicked
      if (isset($_POST['checkVisible'])) {
          // Get the current visibility status
          $visibilityQuery = "SELECT visible FROM bung WHERE id = ?";
          $stmt = $conn->prepare($visibilityQuery);
          $stmt->bind_param("i", $bungalowId);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) {
              $row = $result->fetch_assoc();
              $currentVisibility = $row['visible'];
              // Toggle the visibility (0 to 1 or 1 to 0)
              $newVisibility = $currentVisibility == 1 ? 0 : 1;
              // Update the visibility in the database
              $updateVisibilityQuery = "UPDATE bung SET visible = ? WHERE id = ?";
              $stmt = $conn->prepare($updateVisibilityQuery);
              $stmt->bind_param("ii", $newVisibility, $bungalowId);
              if ($stmt->execute() === false) {
                echo "Error executing SQL query: " . $stmt->error;
              }
            } else {
              echo "Bungalow not found.";
            }
          }
          function getVisibilityButtonText($conn, $bungalowId) {
          $visibilityQuery = "SELECT visible FROM bung WHERE id = ?";
          $stmt = $conn->prepare($visibilityQuery);
          $stmt->bind_param("i", $bungalowId);
          $stmt->execute();
          $result = $stmt->get_result();
          if ($result->num_rows > 0) {
              $row = $result->fetch_assoc();
              return $row['visible'] == 1 ? 'Verberg' : 'Toon';
          } else {
              return 'Niet gevonden';
          }
      }
          
      generateBungalowForm($conn, $query, $visibleName);
      // Fetch all bungalow information from the database
      

      function generateBungalowForm($conn, $query, $visibleName){
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
                  echo '<form action="admin.php" method="post" enctype="multipart/form-data">';
                  echo '<input type="hidden" name="bungalow_id" value="' . $row['id'] . '">';
                  echo '<input type="hidden" name="from_generate_bungalow_form" value="1">'; // Add this line

                  echo '<span>';
                  echo '<label for="price">Prijs:</label>';
                  echo '<span class="type">';
                  echo '<select name="type">';
                  echo '<option value="1" ' . ($row['type'] == 1 ? 'selected' : '') . '>Kreeft</option>';
                  echo '<option value="2" ' . ($row['type'] == 2 ? 'selected' : '') . '>Krab</option>';
                  echo '</select>';


                  echo '<input placeholder="Prijs" type="text" name="price" value="' . $row['prijs'] . '">';
                  echo '</span>';
                  echo '</span>';
                  // Add a file upload for the image path
                  echo '<span>';
                  echo '<label for="imagePath">Image Path:</label>';
                  echo '<span class="image">';
                  echo '<label id="fileBtn" for="' . $row['id'] . '"><i class="fa-solid fa-upload"></i></label>';
                  echo '<input class="fileBtnReal" id="' . $row['id'] . '" type="file" name="image">';
                  echo '<input type="text" id="imagePath" name="imagePath" value="' . $row['foto'] . '" disabled>';
                  echo '</span>';
                  echo '</span>';

                  // Add a field for the number of people
                  echo '<span>';
                  echo '<label for="people">Number of people:</label>';
                  echo '<input type="number" name="people" value="' . $row['personen'] . '">';
                  echo '</span>';
                  echo '<span class="voorzieningen">';


                  // Fetch all amenities for the current bungalow
                  $amenitiesQuery = "SELECT voorzieningen_idvoorzieningen FROM bung_has_voorzieningen WHERE bung_id = {$row['id']}";
                  $amenitiesResult = $conn->query($amenitiesQuery);

                  // Create an array to store the IDs of the amenities for the current bungalow
                  $amenities = array();
                  if ($amenitiesResult && $amenitiesResult->num_rows > 0) {
                      while ($amenityRow = $amenitiesResult->fetch_assoc()) {
                          $amenities[] = $amenityRow['voorzieningen_idvoorzieningen'];
                      }
                  }

                  // Fetch all amenities
                  $voorzieningenQuery = "SELECT * FROM voorzieningen";
                  $voorzieningenResult = $conn->query($voorzieningenQuery);

                  // Check if the fetched amenities are in the amenities array of the current bungalow and mark them as checked
                  if ($voorzieningenResult && $voorzieningenResult->num_rows > 0) {
                      while ($voorzieningRow = $voorzieningenResult->fetch_assoc()) {
                          $isChecked = in_array($voorzieningRow['idvoorzieningen'], $amenities);
                          echo '<input type="checkbox" id="voorziening' . $voorzieningRow['idvoorzieningen'] . '" name="voorzieningen[]" value="' . $voorzieningRow['idvoorzieningen'] . '" ' . ($isChecked ? 'checked' : '') . '>';
                          echo '<label for="voorziening' . $voorzieningRow['idvoorzieningen'] . '">' . $voorzieningRow['naam'] . '</label>';
                      }
                  }

                  echo '</span>';

                  echo '<input type="submit" value="Update" name="' . $row["type_naam"] . '">';
                  echo '<form action="admin.php" method="post">';
                  echo '<input type="hidden" name="bungalow_id" value="' . $row['id'] . '">';
                  echo '<input type="submit" name="checkVisible" value="' . getVisibilityButtonText($conn, $row['id']) . '" name="visible">';                  
                  echo '</form>';
                  echo '</span>';
                  echo '<hr>';
                  echo '</div>';
              }
          } else {
              echo "No results found.";
          }
          echo '<form action="admin.php" method="post">';
          echo '<input type="submit" class="new" name="new" value="Nieuwe Bungalow">';
          echo '</form>';

      }

      ?>
  </section>
</section>

<script src="https://kit.fontawesome.com/c6d023de9c.js" crossorigin="anonymous"></script>
<script src="script.js"></script>

</body> 

</html>
