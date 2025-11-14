<?php
session_start();

// Connexion BD
$db = new SQLite3('labyrinthe.db');

// Position initiale du joueur si non définie
if (!isset($_SESSION['couloir_joueur'])) {
    $_SESSION['couloir_joueur'] = 1; // par défaut le couloir 1
}

// Récupération du couloir actuel du joueur
$couloirActuel = $_SESSION['couloir_joueur'];

// Si le joueur demande un déplacement
if (isset($_GET['move'])) {
    $move = $_GET['move'];

    // Cherche un passage reliant le couloir actuel à un autre
    $stmt = $db->prepare("
        SELECT couloir1, couloir2 
        FROM passage
        WHERE (couloir1 = :c AND position1 = :p)
        OR    (couloir2 = :c AND position2 = :p)
    ");

    $stmt->bindValue(':c', $couloirActuel, SQLITE3_INTEGER);
    $stmt->bindValue(':p', $move, SQLITE3_TEXT);

    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        // Si le déplacement est possible, on change de couloir
        if ($row['couloir1'] == $couloirActuel) {
            $_SESSION['couloir_joueur'] = $row['couloir2'];
        } else {
            $_SESSION['couloir_joueur'] = $row['couloir1'];
        }
        $couloirActuel = $_SESSION['couloir_joueur'];
    }
}

// Infos du couloir
$stmt = $db->prepare("SELECT * FROM couloir WHERE id = :id");
$stmt->bindValue(':id', $couloirActuel, SQLITE3_INTEGER);
$couloir = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Affichage
echo "<h1>Couloir actuel : {$couloir['id']} ({$couloir['type']})</h1>";

// Boutons de mouvement (haut, bas, gauche, droite)
echo "
    <a href='?move=nord'>Nord</a><br>
    <a href='?move=sud'>Sud</a><br>
    <a href='?move=est'>Est</a><br>
    <a href='?move=ouest'>Ouest</a><br>
";

// Liste des passages disponibles
echo "<h2>Passages disponibles :</h2>";
$passages = $db->query("SELECT * FROM passage WHERE couloir1 = $couloirActuel OR couloir2 = $couloirActuel");

while ($p = $passages->fetchArray(SQLITE3_ASSOC)) {
    echo "- Passage vers couloir ";
    echo ($p['couloir1'] == $couloirActuel) ? $p['couloir2'] : $p['couloir1'];
    echo " (positions : {$p['position1']} / {$p['position2']})<br>";
}
?>