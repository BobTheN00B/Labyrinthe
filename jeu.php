<?php
// --- Configuration ---
$db = new SQLite3('labyrinthe.db');
session_start();

// --- CASE DE DÉPART FIXE ---
$CASE_DEPART = 13;

// --- GESTION DU RESET (à placer AVANT tout affichage) ---
if (isset($_GET['reset'])) {
    // Réinitialisation complète de la session
    $_SESSION['position'] = $CASE_DEPART;
    $_SESSION['cles'] = 0;
    $_SESSION['cles_ramassees'] = [];
    $_SESSION['deplacements'] = 0;
    $_SESSION['temps_debut'] = time(); // AJOUT : Réinitialiser le timer
    header("Location: jeu.php");
    exit;
}

// --- Initialisation de la position du joueur (première visite) ---
if (!isset($_SESSION['position'])) {
    $_SESSION['position'] = $CASE_DEPART;
    $_SESSION['cles'] = 0;
    $_SESSION['cles_ramassees'] = [];
    $_SESSION['deplacements'] = 0;
    $_SESSION['temps_debut'] = time();
}

// AJOUT : Forcer la réinitialisation du temps si on revient de index.php
if (!isset($_SESSION['temps_debut'])) {
    $_SESSION['temps_debut'] = time();
}

// --- Forcer le départ à la case 13 si position invalide ---
$stmt = $db->prepare("SELECT * FROM couloir WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['position'], SQLITE3_INTEGER);
$verif = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$verif) {
    // Si la position actuelle n'existe pas, retour au départ
    $_SESSION['position'] = $CASE_DEPART;
}

// --- Déplacement si demandé ---
if (isset($_GET['move'])) {
    $cible = intval($_GET['move']);

    // Vérifier si déplacement possible via la table passage
    $stmt = $db->prepare("SELECT * FROM passage WHERE (couloir1 = :p AND couloir2 = :c) OR (couloir2 = :p AND couloir1 = :c)");
    $stmt->bindValue(':p', $_SESSION['position'], SQLITE3_INTEGER);
    $stmt->bindValue(':c', $cible, SQLITE3_INTEGER);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($res) {
        $typePassage = $res['type'];

        if ($typePassage === 'libre' || $typePassage === 'vide' || $typePassage === 'depart') {
            $_SESSION['position'] = $cible;
            $_SESSION['deplacements']++;
        }
        elseif ($typePassage === 'grille') {
            if (!isset($_SESSION['cles'])) $_SESSION['cles'] = 0;
            if ($_SESSION['cles'] > 0) {
                $_SESSION['cles'] -= 1;
                $_SESSION['position'] = $cible;
                $_SESSION['deplacements']++;
                $message = "Vous utilisez une clé pour ouvrir la grille.";
            } else {
                $message = "Il vous faut une clé pour ouvrir cette grille !";
            }
        }
    }
}

// --- Récupérer les infos de la position actuelle ---
$stmt = $db->prepare("SELECT * FROM couloir WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['position'], SQLITE3_INTEGER);
$piece = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$piece) {
    die("Erreur : la case actuelle n'existe pas dans la base.");
}

// --- Gestion de la clé ---
if ($piece['type'] === 'cle') {
    if (!isset($_SESSION['cles'])) $_SESSION['cles'] = 0;
    if (!isset($_SESSION['cles_ramassees'])) $_SESSION['cles_ramassees'] = [];
    
    // Éviter de ramasser la même clé plusieurs fois
    if (!in_array($_SESSION['position'], $_SESSION['cles_ramassees'])) {
        $_SESSION['cles'] += 1;
        $_SESSION['cles_ramassees'][] = $_SESSION['position'];
        $message = "Vous avez ramassé une clé !";
    }
}

$temps_ecoule = time() - $_SESSION['temps_debut'];


// --- Vérifier si le joueur a atteint la sortie ---
if ($piece['type'] === 'sortie') {
    $score = round(100000 / ($temps_ecoule * $_SESSION['deplacements']));
    $message = "🎉 FÉLICITATIONS ! Vous avez trouvé la sortie du labyrinthe ! 🎉<br>";
    $message .= "Temps écoulé : " . $temps_ecoule . " secondes<br>";
    $message .= "Nombre de déplacements : " . $_SESSION['deplacements'] . "<br>";
    $message .= "Score : " . $score . " points";
}


// --- Récupérer les sorties ---
$stmt = $db->prepare("SELECT * FROM passage WHERE couloir1 = :id OR couloir2 = :id");
$stmt->bindValue(':id', $_SESSION['position'], SQLITE3_INTEGER);
$result = $stmt->execute();

// Organiser les sorties par direction
$directions = [
    'Nord' => null,
    'Est' => null,
    'Sud' => null,
    'Ouest' => null
];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $cible = ($row['couloir1'] == $_SESSION['position']) ? $row['couloir2'] : $row['couloir1'];
    $position = ($row['couloir1'] == $_SESSION['position']) ? $row['position1'] : $row['position2'];
    
    $directions[$position] = [
        'id' => $cible,
        'type' => $row['type']
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet"href="style.css">
    <title>Jeu de Labyrinthe</title>
</head>
<div class="jeu">
<body>

<?php if (isset($message)): ?>
    <p><strong><?php echo $message; ?></strong></p>
<?php endif; ?>

<?php 
// Calculer le temps écoulé
$temps_ecoule = time() - $_SESSION['temps_debut'];
$secondes = $temps_ecoule;
?>

<h2>Position actuelle : Case <?php echo $_SESSION['position']; ?> 
<?php if ($_SESSION['position'] == $CASE_DEPART): ?>
    (DÉPART)
<?php endif; ?>
</h2>


<p><strong>Déplacements :</strong> <?php echo $_SESSION['deplacements']; ?></p>
<p><strong>Inventaire :</strong> <?php echo (isset($_SESSION['cles']) && $_SESSION['cles']>0) ? $_SESSION['cles']." clé(s)" : "Aucune clé"; ?></p>

<h3>Directions :</h3>

<?php 
// Créer un tableau pour savoir quelles directions ont déjà été affichées
$directions_affichees = [];

// Afficher d'abord les directions disponibles (cliquables)
foreach ($directions as $direction => $sortie): 
    if ($sortie !== null && ($sortie['type'] === 'libre' || $sortie['type'] === 'vide' || $sortie['type'] === 'depart')): 
        $directions_affichees[] = $direction; ?>
    <div class='movement'>
        <a href="?move=<?php echo $sortie['id']; ?>">
            <button><?php echo substr($direction, 0, 1); ?> (libre)</button>
        </a>
    <?php elseif ($sortie !== null && $sortie['type'] === 'grille' && isset($_SESSION['cles']) && $_SESSION['cles'] > 0): 
        $directions_affichees[] = $direction; ?>
        <a href="?move=<?php echo $sortie['id']; ?>">
            <button><?php echo substr($direction, 0, 1); ?> 🔒</button>
        </a>
    </div>
    <?php endif;
endforeach;
?>

<br><br>
<a href="?reset=1" onclick="return confirm('Voulez-vous vraiment redemarrer la partie?');">
   <button> Recommencer la partie </button>
</a>
<a href="index.php" onclick="return confirm('Voulez-vous retourner au menu?');">
    <button> Retourner au menu d'acceuil </button>
</a>
</body>
</div>
</html>