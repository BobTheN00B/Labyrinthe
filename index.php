<?php

	//Documentation php pour sqlite : https://www.php.net/manual/en/book.sqlite3.php
	

	/* Paramètres */
	$bdd_fichier = 'labyrinthe.db';	//Fichier de la base de données
	$type = 'vide';			//Type de couloir à lister
	

	$sqlite = new SQLite3($bdd_fichier);		//On ouvre le fichier de la base de données
	
	/* Instruction SQL pour récupérer la liste des pieces adjacentes à la pièce paramétrée */
	$sql = 'SELECT couloir.id, couloir.type FROM couloir WHERE type=:type';
	
	
	/* Préparation de la requete et de ses paramètres */
	$requete = $sqlite -> prepare($sql);	
	$requete -> bindValue(':type', $type, SQLITE3_TEXT);
	
	$result = $requete -> execute();	//Execution de la requête et récupération du résultat

	/* On génère et on affiche notre page HTML avec la liste de nos films */
	echo "<!DOCTYPE html>\n";		//On demande un saut de ligne avec \n, seulement avec " et pas '
	echo "<html lang=\"fr\"><head><meta charset=\"UTF-8\">\n";	//Avec " on est obligé d'échapper les " a afficher avec \
	echo "<title>Liste des couloirs</title>\n";
	echo "</head>\n";
	
	echo "<body>\n";
	echo "<h1>Liste des couloirs</h1>\n";
	echo "<ul>";
	while($couloir = $result -> fetchArray(SQLITE3_ASSOC)) {
		echo '<li>'.$couloir['id']." (type : {$couloir['type']})</li>";
	}
	echo "</ul>";
	echo "</body>\n";
	echo "</html>\n";
	
	
	$sqlite -> close();			//On ferme bien le fichier de la base de données avant de terminer!
	
?>