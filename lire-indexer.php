<!DOCTYPE html>
<html>
<head>
    <title>Indexation</title>
    <style>
        /* Style pour centrer le contenu */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 90vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .content {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="content">
        <p><h2><b>Début d'indexation:</b> <?php echo date("h:i:s"); ?></h2></p>

        <?php
        include 'funtiontext.php';

        // Augmentation du temps d'exécution de ce script
        set_time_limit(500);
        $path = "info";

        // Appel à la fonction d'indexation d'un repertoire
        indexerRepertoire($path);
        ?>

        <p><h2><b>Fin d'indexation:</b> <?php echo date("h:i:s"); ?></h2></p>
    </div>
</body>
</html>
