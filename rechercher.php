<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <title>recherche</title>
</head>

<style>
    /* Styles CSS modifiés */
    body {
        margin: 50;
        padding: 50;
        font-family: Arial, sans-serif;
        font-size: 16px;
        background-color: #f8f9fa;
    }

    /* Barre de recherche */
    .search {
        background-color: #eee;
        padding: 50px;
        border-radius: 0;
        position: sticky;
        top: 0;
    }

    .search input {
        height: 40px;
        border: px solid #ccc;
        border-radius: 25px;
        text-align: center;
    }

    .search button {
        height: 40px;
        width: 80px;
        border: px solid #ccc;
        border-radius: 15px;
        background-color: #3498db;
        color: white;
        flex-shrink: 0;
                position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
    }
.result-container {
        margin-top: 20px;
    }

    .result-item {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        background-color: #fff;
        border-radius: 5px;
    }

    .result-title {
        color: #1a0dab;
        font-size: 1.5rem;
        margin-bottom: 10px;
    }

    .result-source {
        color: #4caf50;
    }

    .result-description {
        color: #333;
    }

    .nuage {
        width: 60%;
        background: #dfe5ed;
        color: #red;
        padding: 10px;
        border: 1px solid #55d2ff;
        text-align: center;
        border-radius: 20px;
    }


</style>


<body>

  


    <div class="container">
        <div class="row height d-flex justify-content-center align-items-center">
            <div class="col-md-8">
                <div class="search">
                    <form action="rechercher.php" method="post" id="form-id">
                        <input type="search_input" name="query" id="search" class="form-control" placeholder="Saisir votre recherche">
                        <button class="btn btn-light" style="text-align:center;"  name="submit">Find</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <div class="container result-container">
        <?php
        // Inclure la bibliothèque contenant nos fonctions
    function genererNuage( $data = array() , $source_html, $minFontSize = 15, $maxFontSize = 40 )
{

        $tab_colors=array("#3087F8", "#000080", "black", "#7F814E", "#EC1E85","#14E414","#9EA0AB", "#9EA414", "#800080");

        $minimumCount = min( array_values( $data ) );
        $maximumCount = max( array_values( $data ) );
        $spread = $maximumCount - $minimumCount;
        $cloudHTML = '';
        $cloudTags = array();

        $spread == 0 && $spread = 1;
        //Mélanger un tableau de manière aléatoire
        srand((float)microtime()*1000000);
        $mots = array_keys($data);
        shuffle($mots);

        foreach( $mots as $tag )
        {       
                $count = $data[$tag];
                //La couleur aléatoire
                $color=rand(0,count($tab_colors)-1);

                $size = $minFontSize + ( $count - $minimumCount )
                        * ( $maxFontSize - $minFontSize ) / $spread;
                $cloudTags[] ='<a style="font-size: '.
                        floor( $size ) .
                        'px' .
                        '; color:' .
                        $tab_colors[$color].
                        '; " title="' .
                        $tag .
                        ' est répété '.round($data[$tag]).' fois dans ce document " href="'.$source_html.'">' .
                        $tag .
                        '</a>';
        }
        return join( "\n", $cloudTags ) . "\n";
}       


        // Récupérer le mot saisi dans la barre de recherche
        if (isset($_POST["query"]))
            $query = $_POST["query"];

        // Récupérer le query du lien de la page actuelle
        if (isset($_GET['query']))
            $query = $_GET['query'];

        // Initialisation d'un message d'erreur si aucun mot n'est saisi 
        $error = "";

        // Vérification si y'a eu un submit de la recherche
        if (isset($query)) {

            // Vérification si query n'est pas juste un espace vide
            if (trim($query) == "") {
                $error = "Veuillez svp saisir un mot pour la recherche !!!";
            }

            // Si query est valide la recherche commence
            else {

                // Etablir une connexion avec la BDD
                $connexion = mysqli_connect("localhost", "root", "root", "tiw");

                // Récupération du numéro de page actuelle du lien en haut 
                if (isset($_GET['page'])) $page = $_GET['page'];
                // Sinon c'est la première page
                else $page = 1;

                // Requête de récupération du nombre de résultats défini à partir du début calculé pour chaque page
                $sql = "SELECT document.id, document.source, document.titre, document.descriptif, mot_document.poids 
                        FROM ((document INNER JOIN mot_document ON document.id = mot_document.idSource) INNER JOIN mot ON mot_document.idMot = mot.id) 
                        WHERE mot.mot = '$query' ORDER BY poids DESC";

                // Requête de récupération de tous les résultats de la recherche pour query
                $sql_count = "SELECT * FROM
                                ((document INNER JOIN mot_document ON document.id = mot_document.idSource) 
                                INNER JOIN mot ON mot_document.idMot = mot.id) 
                                WHERE mot.mot = '$query'";

                // Résultats à afficher dans une page
                $resultat = mysqli_query($connexion, $sql);

                // Nombre total des résultats --> utilisé pour calculer le nombre des pages nécessaires 
                $nbr_resultats = mysqli_num_rows(mysqli_query($connexion, $sql_count));

                // Affichage du nombre des résultats trouvés pour le mot recherché
                echo "<br>$nbr_resultats Résultats trouvés pour <b>$query</b> :<br><br>";

                // Afficher des attributs nécessaires pour chaque résultat 
                while ($ligne = mysqli_fetch_row($resultat)) {

                    // Affichage du titre du document et poids du query dans ce document  
                    echo '<div class="result-item">';
                    echo "<div class='result-title'><a href='$ligne[1]' target='_blank'><font color="."#294b5d".">$ligne[2] ($ligne[4])</a></div>";

                    // Affichage de la source du document + le bouton pour afficher/cacher le nuage 
                    echo "↓↓↓ce mot est ici ↓↓↓ <div class='result-source'>$ligne[1] <button class='btn btn-link' onclick='myFunction(this,$ligne[0])'>
                                <i class=''></i> VoirNuage(+)</button></div>";

                    // Affichage du descriptif du document 
                    echo " ↓↓↓Description ↓↓↓: <div class='result-description'>$ligne[3]</div>";

                    // Requête de récupération d'une liste de 35 mots aléatoires du document pour le nuage des mots clés
                    $sql_nuage = "SELECT mot.mot, mot_document.poids  
                                    FROM ((document INNER JOIN mot_document ON document.id = mot_document.idSource) 
                                    INNER JOIN mot ON mot_document.idMot = mot.id) 
                                    WHERE document.id = '$ligne[0]' ORDER BY rand() LIMIT 35";

                    // Résultats des mots pour le nuage
                    $resultat_nuage = mysqli_query($connexion, $sql_nuage);

                    // On met les résultats dans un tableau associatif pour donner en paramètres à la fonction generernuage()
                    $tab_nuage = array();
                    while ($lign = mysqli_fetch_row($resultat_nuage)) {
                        $tab_nuage += [$lign[0] => $lign[1]];
                    }

                    // On vérifie si query figure dans la liste aléatoire, sinon on l'ajoute 
                    if (!in_array($query, $tab_nuage)) {
                        $tab_nuage += [$query => $ligne[4]];
                    }

                    // Affichage du nuage
                    echo '<div class="nuage" style="display:none" id="' . $ligne[0] . '">
                            ' . genererNuage($tab_nuage, $ligne[1]) . '
                            </div>

                            <script>
                            function myFunction(bouton,id) {
                                var x = document.getElementById(id);
                                if (x.style.display === "none") {
                                    x.style.display = "block";
                                    bouton.innerHTML=" nuage(-)";
                                } else {
                                    x.style.display = "none";
                                    bouton.innerHTML="Revoirnuage(+)";
                                }
                            }
                            </script></div><br><br>';
                }
            }
        }
        ?>

    </div>

    <div>
        <!-- affichage du message d'erreur en cas de recherche sur un vide -->
        <div style="text-align: center; color: red"><a> <b> <?php echo $error; ?> </b></a></div>
    </div>

</body>

</html>
