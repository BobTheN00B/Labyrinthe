<?php
session_start();
// Détruire toute la session si on revient au menu
if (isset($_SESSION['position'])) {
    session_destroy();
    session_start(); // Redémarrer une session vide
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet"href="style.css">
    <title>Jeu de Labyrinthe</title>
</head>
<div class="menu">
<body>
<border>
<div class="Titre">
    <h1>Jeu de Labyrinthe</h1>
    <h5>Par Maletchi Andrian</h5>
</div>
<div class="container">
    <a href="jeu.php"><button>Commencer une nouvelle partie</button></a>
    <a href="aide.php"><button>Comment jouer?</button></a>
</div>
</border>
</body>
</div>
</html>
