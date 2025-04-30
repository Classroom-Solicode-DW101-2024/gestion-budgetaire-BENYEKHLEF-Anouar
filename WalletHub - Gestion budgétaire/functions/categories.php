<?php
// Categories array
$categories = [
    'revenu' => ['Salaire', 'Bourse', 'Ventes', 'Autres'],
    'depense' => ['Logement', 'Transport', 'Alimentation', 'Santé', 'Divertissement', 'Éducation', 'Autres']
];

// Check if categories table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$count = $stmt->fetchColumn();

if ($count == 0) {
    // Loop through categories and insert them if the table is empty
    foreach ($categories as $type => $categoryArray) {
        foreach ($categoryArray as $categoryName) {
            // Prepare and execute the insert query
            $stmt = $pdo->prepare("INSERT INTO categories (nom, type) VALUES (:nom, :type)");
            $stmt->bindParam(':nom', $categoryName, PDO::PARAM_STR);
            $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    // echo "Categories inserted successfully!";
} 
// else {
//     echo "Categories already exist in the table.";
// }
?>