<?php

$tab_mots_vides = file('mots_vides.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$nbr = 0;

// Tokenisation de la chaîne en mots
function explode_bis($separateurs,$chaine){
$separateurs =  "’. ,…][(«»)" ;
$tab =array();
$tok =strtok($chaine,"$separateurs");

if ($tok) {
    $GLOBALS["nbr"]=1;  
}

if ((strlen(trim($tok))>2) and !(in_array(trim($tok),$GLOBALS["tab_mots_vides"]))) $tab[]= $tok;
    while($tok != false)
    {       
        $tok =strtok($separateurs);
        if ($tok) {
            $GLOBALS["nbr"]=$GLOBALS["nbr"]+1;  
        }
        if ((strlen(trim($tok))>2) and !(in_array(trim($tok),$GLOBALS["tab_mots_vides"])))  $tab[]= $tok;       
    }
    return $tab;
}


// Afficher le tableau avec les indices et valeurs
function print_tab($tab)
{
    foreach ($tab  as $indice => $mot)
        echo $indice, " :  ", $mot, "<br>";
}


//extraction des keywords et description des metas html
function get_keywords($source_html)
{
    //les  metas keywords +description
    $chaine_metas = "";
    $tab_metas = get_meta_tags($source_html);
    if(isset($tab_metas["keywords"])) $chaine_metas .= $tab_metas["keywords"];
    return strtolower($chaine_metas);
}

//extraction des keywords et description des metas html
function get_description($source_html)
{
    //les  metas keywords +description
    $chaine_metas = "";
    $tab_metas = get_meta_tags($source_html);
    if(isset($tab_metas["description"])) $chaine_metas .= " ". $tab_metas["description"];
    return strtolower($chaine_metas);
}


//extraction de title de html 
function get_title($source_html)
{
    $chaine_html = implode(file ($source_html), " ") ;

    $modele ="/<title>(.*)<\/title>/si";

    if (preg_match($modele,$chaine_html,$titre))    return strtolower($titre[1]);
    else return " Document sans titre";
}

//extraction de body de html en texte 
function get_body($source_html){
    $chaine_html = implode(file ($source_html), " ") ;

    $modele_body ="/<body[^>]*>(.*)<\/body>/is";
    $modele_balises_scripts = '/<script[^>]*?>.*?<\/script>/is';

    //Remplacer les scripts par des vides dans HTML
    $html_sans_script = preg_replace($modele_balises_scripts, '', $chaine_html);
    
    //Récuperer le body sans script
    preg_match($modele_body,$html_sans_script,$body);
        
    $chaine_text = strtolower(preg_replace('#<[^>]+>#', ' ',$body[1]));
    
    return $chaine_text;
}

// Mise en bdd des resultats de l'indexation
function insertion_BDD($source_html, $titre, $descriptif, $tab_mots_poids)
{

    $connexion = mysqli_connect("localhost", "root", "root", "tiw");
    $idMot = 0;
    $idSource = 0;
    $select_document = "SELECT * FROM document WHERE source = '" . $source_html . "' and titre = '" . $titre . "' ";
    $resultats_select_document = mysqli_query($connexion, $select_document);

    // Ajouter un document à la base de données
    if (!$resultats_select_document || mysqli_num_rows($resultats_select_document) == 0) {

        $source_html = str_replace("'", " ", $source_html);
        $titre = str_replace("'", " ", $titre);
        $descriptif = str_replace("'", " ", $descriptif);

        $insert_document = " insert into document(source,titre,descriptif) values ('$source_html','$titre','$descriptif') ";
        $resultats_insert_document = mysqli_query($connexion, $insert_document);

        if ($resultats_insert_document) {
            $idSource = mysqli_insert_id($connexion);
        }
    } else {
        $idSource = mysqli_fetch_row($resultats_select_document)[0];
    }

    // Ajouter un mot à la base de données
    foreach ($tab_mots_poids as $mot => $poids) {
        $select_mot = "SELECT * FROM mot WHERE mot = '" . $mot . "' ";
        $resultats_select_mot = mysqli_query($connexion, $select_mot);
        if (!$resultats_select_mot || mysqli_num_rows($resultats_select_mot) == 0) {

            $insert_mot = " insert into mot(mot) values ('$mot') ";
            $resultats_insert_mot = mysqli_query($connexion, $insert_mot);

            if ($resultats_insert_mot) {
                $idMot = mysqli_insert_id($connexion);
            }
        } else {
            $idMot = mysqli_fetch_row($resultats_select_mot)[0];
        }

        // Insertion dans la table d'association
        $select_mot_document = "SELECT * FROM mot_document WHERE idMot = '" . $idMot . "' and idSource = '" . $idSource . "' ";
        $resultats_select_mot_document = mysqli_query($connexion, $select_mot_document);

        if (mysqli_num_rows($resultats_select_mot_document) == 0) {
            $sql = "INSERT INTO mot_document (idMot, idSource, poids) VALUES ($idMot, $idSource, $poids)";
            $resultat = mysqli_query($connexion, $sql);
        }
    }
    mysqli_close($connexion);
}

//Augmenter le coefficient des occirences
function occurences2poids ($tab, $coefficient){
    foreach ($tab as $key => $value) {
        $tab[$key] *= $coefficient;
    }
    return $tab;
}

// Fusionner deux tableaux 
function fusion_deux_tableaux ($tab_mots_occurrences_head, $tab_mots_occurrences_body){ 
    foreach ($tab_mots_occurrences_head as $mot_head => $occ_head){
        if (array_key_exists("$mot_head", $tab_mots_occurrences_body))
            $tab_mots_occurrences_body ["$mot_head"] += $occ_head;
        else
            $tab_mots_occurrences_body += [ "$mot_head" => $occ_head];
    }
return $tab_mots_occurrences_body;
}

//traduction des caractéres html en ascii
function entitiesHTML2ASCII($chaine)
{
    //HTML_ENTITIES: tous les caractères éligibles en entités HTML.

    // retourne la table de traduction des entités utilisée en interne par la htmlentities():
    $table_caracts_html = get_html_translation_table(HTML_ENTITIES); 

    // retourne un tableau dont les clés sont les valeurs du précédent $table_caracts_html, et les valeurs sont les clés. 
    $tableau_html_caracts =  array_flip($table_caracts_html);

    // retourne une chaine de caractères après avoir remplacé les éléments/clés par les éléments/valeurs  du tableau associatif de paires  $tableau_html_caracts dans la chaîne $chaine.
    $chaine  =  strtr ($chaine,$tableau_html_caracts); 

    return $chaine;
}

//Calculer le pourcentage
function calculPourcentage($total,$partiel){
    $pourcentage=($partiel/$total)*100;
    return round($pourcentage);
}

// Fonction pour générer le cloud à partir des données fournies
function genererNuage($data = array(), $source_html, $minFontSize = 15, $maxFontSize = 40)
{
    $tab_colors = array("#3087F8", "#000080", "#FF0000", "#7F814E", "#EC1E85", "#14E414", "#9EA0AB", "#9EA414", "#800080");

    $minimumCount = min(array_values($data));
    $maximumCount = max(array_values($data));
    $spread = $maximumCount - $minimumCount;
    $cloudHTML = '';
    $cloudTags = array();

    $spread == 0 && $spread = 1;
    // Mélanger un tableau de manière aléatoire
    srand((float)microtime() * 1000000);
    $mots = array_keys($data);
    shuffle($mots);

    foreach ($mots as $tag) {
        $count = $data[$tag];
        // La couleur aléatoire
        $color = rand(0, count($tab_colors) - 1);

        $size = $minFontSize + ($count - $minimumCount) * ($maxFontSize - $minFontSize) / $spread;
        $cloudTags[] = '<a style="font-size: ' .
            floor($size) .
            'px' .
            '; color:' .
            $tab_colors[$color] .
            '; " title="' .
            $tag .
            ' est répété ' . round($data[$tag]) . ' fois dans ce document " href="' . $source_html . '">' .
            $tag .
            '</a>';
    }
    return join("\n", $cloudTags) . "\n";
}


//Indexer un fichier html
function indexer($source_html){
    
    //séparateur tokenisation
    $separateurs = " ,.():!?»«\t\"\n\r\'-+/*%{}[]#0123456789";


//Traitement du Head

        //récuperation de titre
        $title = get_title($source_html);
        
        //extraction des keywords et description des metas html
        $keywords = get_keywords($source_html);
        $description = get_description($source_html);
        $text_head = $title." ".$keywords." ".$description;

        //traduction des entités html en ascii
        $chaine_head = entitiesHTML2ASCII($text_head);

        //tokenisation de la chaine en mot 
        $tab_title_metas = explode_bis($separateurs,$chaine_head);
        $tab_head_mot_occurrence = array_count_values($tab_title_metas);
        $nombreMotsHeadTotal = $GLOBALS["nbr"];
        $nombreMotsHeadSelectionnes = sizeof($tab_title_metas);

        //Appliquer le coefficient
        $coefficient = 1.5;
        $tab_head = occurences2poids ($tab_head_mot_occurrence, $coefficient);


//Traitement du body

        //extraction de body de html en texte 
        $text_body = get_body($source_html);

        //traduction des entités html en ascii
        $chaine_body = entitiesHTML2ASCII($text_body);

        //tokenisation de la chaine en mot 
        $tab_body = explode_bis($separateurs,$chaine_body);
        $tab_body_mot_occ = array_count_values($tab_body);
        $nombreMotsBodyTotal = $GLOBALS["nbr"];
        $nombreMotsBodySelectionnes = sizeof($tab_body);


//Fusion des tables du Head et Body
$tab_mots_poids = fusion_deux_tableaux ($tab_head, $tab_body_mot_occ);

if ($description == "") {
    $description = substr($text_body, 0, 100); 
}
$description = $description." . . .";

// Mise en bdd des resultats de l'indexation 
insertion_BDD($source_html, $title, $description, $tab_mots_poids);

//Synthese d'indexation
$nombreMotsTotal = $nombreMotsHeadTotal + $nombreMotsBodyTotal;
$nombreMotsSelectionnes = $nombreMotsHeadSelectionnes + $nombreMotsBodySelectionnes;
$pourcentage=calculPourcentage($nombreMotsTotal, $nombreMotsSelectionnes);
    
    echo '<h3 style="color:green">l\'indexation est bien effectuée</h3>';
    echo "</table>";


    echo "<hr>";
    }
function explode_bis_text($texte, $separateurs)
{
    $tok =  strtok($texte, $separateurs);
    if(strlen($tok) > 2)$tab_tok[] = $tok;

    while ($tok !== false) 
    {
        $tok = strtok($separateurs);
        if(strlen($tok) > 2)$tab_tok[] = $tok;
    }
    return $tab_tok;
}

// Indexer un fichier texte
function IndexationTEXTE($source_html){
    
    //séparateur tokenisation
    $separateurs = " ,.():!?»«\t\"\n\r\'-+/*%{}[]#0123456789";
    $texte = file_get_contents($source_html);
    
    $tab_toks = explode_bis_text($texte, $separateurs);
    // 4
    //filtrage les doublons par calcule les occurrences
    $tab_new_mots_occurrences = array_count_values ($tab_toks);
    
    $coefficient = 1.5;
    $tab_mots_poids = occurences2poids ($tab_new_mots_occurrences, $coefficient);
    
    $keywords = get_keywords($source_html);
    
    
    $title = substr($texte,0,35);
    
    $description = substr($texte,35, 150);

    insertion_BDD($source_html, $title, $description, $tab_mots_poids);
    


    $nombreMotsTotal = str_word_count($texte);
    $nombreMotsSelectionnes=sizeof($tab_toks);
    $pourcentage=calculPourcentage($nombreMotsTotal, $nombreMotsSelectionnes);

    echo '<h3 style="color:green">l\'indexation est bien effectuée</h3>';
    echo "</table>";


    echo "<hr>";
}

// Fonction pour indexer un répertoire
function indexerRepertoire($path)
{
    $folder = opendir($path);
    while ($entree = readdir($folder)) {
        // On ignore les entrées
        if ($entree != "." && $entree != "..") {
            // On vérifie si il s'agit d'un répertoire
            if (is_dir($path . "/" . $entree)) {
                $sav_path = $path;
                // Construction du path jusqu'au nouveau répertoire
                $path .= "/" . $entree;
                // On parcourt le nouveau répertoire
                indexerRepertoire($path);
                $path = $sav_path;
            } else {
                // C'est un fichier html ou pas
                $path_source = $path . "/" . $entree;

                if (stripos($path_source, '.htm')) {
                    echo '<h3 style="color: blue">indexation en cours... --> ';

                    echo $path_source, '<br> </h3>'; 

                    indexer($path_source);
                    echo '<a href="rechercher.php">Chercher</a>';
                }
                 if (stripos($path_source, '.txt')) {
                    echo '<h3 style="color: blue">indexation en cours... --> ';

                    echo $path_source, '<br> </h3>'; 

                    IndexationTEXTE($path_source);
                    echo '<a href="rechercher.php">Chercher</a>';
                }

            }
        }
    }
    closedir($folder);
}

// Utilisation de la fonction pour indexer un répertoire

?>