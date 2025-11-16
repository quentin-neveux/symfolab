<?php
// Quentin – génération réaliste de trajets EcoRide (version à jour)

$host = '127.0.0.1';
$port = 3307;
$db   = 'ecoride_symfony';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$targetCount = 20000;

$villes = [
    'Paris','Lyon','Marseille','Toulouse','Bordeaux','Nice','Nantes','Strasbourg','Lille','Montpellier',
    'Rennes','Grenoble','Rouen','Dijon','Tours','Nancy','Angers','Le Havre','Orléans','Metz','Avignon','Annecy','Besançon',
    'Clermont-Ferrand','Amiens','Poitiers','Caen','Reims','La Rochelle','Pau','Limoges','Perpignan','Saint-Étienne',
    'Chambéry','Troyes','Colmar','Mulhouse','Valence','Bayonne','Tarbes','Lorient','Brest','Vannes','Blois','Chartres',
    'Versailles','Cannes','Antibes','Aix-en-Provence','Arles','Carcassonne','Fréjus','Gap','Menton','Grasse',
    'Biarritz','Mâcon','Chalon-sur-Saône','Albi','Narbonne','Béziers','Agen','Brive','Périgueux','Niort','Cholet','Vienne',
    'Annemasse','Thonon-les-Bains','Genève','Lausanne','Neuchâtel','Fribourg','Sion','Vevey','Montreux','Martigny',
    'Lugano','Zurich','Bâle','Berne','Luxembourg','Bruxelles','Namur','Liège','Mons','Charleroi','Anvers','Gand','Bruges',
    'Turin','Milan','Aoste','Modane','Briançon','Chamonix','Megève','Cluses','Rumilly','Albertville','Culoz'
];

$axes_principaux = [
    ['Paris','Lyon'], ['Lyon','Marseille'], ['Bordeaux','Toulouse'], ['Lille','Paris'],
    ['Annecy','Genève'], ['Genève','Annecy'], ['Lyon','Grenoble'], ['Grenoble','Chambéry'], ['Paris','Bordeaux']
];

$vehicules = ['Peugeot 208','Renault Clio','Citroën C3','Volkswagen Golf','Tesla Model 3','Toyota Yaris','BMW Série 1','Mercedes Classe A','Fiat 500','Dacia Sandero'];
$energies = ['Essence','Diesel','Electrique','Hybride'];
$commentaires = [
    'Pause café prévue ☕','Pas d’animaux svp 🐶❌','Trajet rapide sans détour 🕒',
    'Voiture confortable 🚗','Musique douce 🎵','Je peux prendre un bagage 🧳',
    'Départ ponctuel 👍','Trajet régulier chaque semaine','Arrêt possible à mi-chemin','Bonne humeur garantie 😁'
];

function randomDate() {
    $now = time();
    $future = strtotime('+60 days');
    $t = mt_rand($now, $future);
    $h = mt_rand(6, 21);
    $m = [0,15,30,45][array_rand([0,15,30,45])];
    return date('Y-m-d ', $t) . sprintf('%02d:%02d:00', $h, $m);
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $userIds = $pdo->query("SELECT id FROM user")->fetchAll(PDO::FETCH_COLUMN);
    if (!$userIds) throw new Exception("Table user vide.");

    $stmt = $pdo->prepare("
        INSERT INTO trajet (conducteur_id, ville_depart, ville_arrivee, date_depart, date_arrivee, places_disponibles, prix, commentaire, type_vehicule, energie, est_ecologique)
        VALUES (:conducteur_id, :ville_depart, :ville_arrivee, :date_depart, :date_arrivee, :places_disponibles, :prix, :commentaire, :type_vehicule, :energie, :est_ecologique)
    ");

    $pdo->beginTransaction();
    $inserted = 0;

    for ($i = 0; $i < 4000; $i++) {
        $axe = $axes_principaux[array_rand($axes_principaux)];
        [$villeDepart, $villeArrivee] = $axe;
        $conducteur = $userIds[array_rand($userIds)];
        $places = mt_rand(1, 5);
        $prix = round(mt_rand(800, 6000)/100, 2);
        $typeVehicule = $vehicules[array_rand($vehicules)];
        $energie = $energies[array_rand($energies)];
        $commentaire = $commentaires[array_rand($commentaires)];
        $dateDepart = randomDate();
        $dateArrivee = date('Y-m-d H:i:s', strtotime($dateDepart . ' +' . mt_rand(45, 240) . ' minutes'));
        $eco = ($energie === 'Electrique') ? 1 : 0;

        $stmt->execute([
            ':conducteur_id' => $conducteur,
            ':ville_depart' => $villeDepart,
            ':ville_arrivee' => $villeArrivee,
            ':date_depart' => $dateDepart,
            ':date_arrivee' => $dateArrivee,
            ':places_disponibles' => $places,
            ':prix' => $prix,
            ':commentaire' => $commentaire,
            ':type_vehicule' => $typeVehicule,
            ':energie' => $energie,
            ':est_ecologique' => $eco,
        ]);
        $inserted++;
    }

    while ($inserted < $targetCount) {
        $villeDepart = $villes[array_rand($villes)];
        do { $villeArrivee = $villes[array_rand($villes)]; } while ($villeArrivee === $villeDepart);
        $conducteur = $userIds[array_rand($userIds)];
        $places = mt_rand(1, 5);
        $prix = round(mt_rand(500, 12000)/100, 2);
        $typeVehicule = $vehicules[array_rand($vehicules)];
        $energie = $energies[array_rand($energies)];
        $commentaire = $commentaires[array_rand($commentaires)];
        $dateDepart = randomDate();
        $dateArrivee = date('Y-m-d H:i:s', strtotime($dateDepart . ' +' . mt_rand(30, 300) . ' minutes'));
        $eco = ($energie === 'Electrique') ? 1 : 0;

        $stmt->execute([
            ':conducteur_id' => $conducteur,
            ':ville_depart' => $villeDepart,
            ':ville_arrivee' => $villeArrivee,
            ':date_depart' => $dateDepart,
            ':date_arrivee' => $dateArrivee,
            ':places_disponibles' => $places,
            ':prix' => $prix,
            ':commentaire' => $commentaire,
            ':type_vehicule' => $typeVehicule,
            ':energie' => $energie,
            ':est_ecologique' => $eco,
        ]);

        $inserted++;
        if ($inserted % 1000 === 0) echo "Inserted: $inserted\n";
    }

    $pdo->commit();
    echo "✅ Terminé : $inserted trajets générés.\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "Erreur : " . $e->getMessage() . "\n";
}
