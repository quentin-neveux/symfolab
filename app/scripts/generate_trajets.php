<?php
// Quentin – génération réaliste de trajets EcoRide (prix par personne, sans doublons exacts)

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

// Nombre total de trajets à générer
$targetCount = 40000;

// === Villes principales et frontalières ===
$villes = [
    'Paris','Lyon','Marseille','Toulouse','Bordeaux','Nice','Nantes','Strasbourg','Lille','Montpellier',
    'Rennes','Grenoble','Rouen','Dijon','Tours','Nancy','Angers','Le Havre','Orléans','Metz','Avignon','Annecy','Besançon',
    'Clermont-Ferrand','Amiens','Poitiers','Caen','Reims','La Rochelle','Pau','Limoges','Perpignan','Saint-Étienne',
    'Chambéry','Troyes','Colmar','Mulhouse','Valence','Bayonne','Tarbes','Lorient','Brest','Vannes','Blois','Chartres',
    'Versailles','Cannes','Antibes','Aix-en-Provence','Arles','Carcassonne','Perpignan','Fréjus','Gap','Menton','Grasse',
    'Biarritz','Mâcon','Chalon-sur-Saône','Albi','Narbonne','Béziers','Agen','Brive','Périgueux','Niort','Cholet','Vienne',
    'Annemasse','Thonon-les-Bains','Genève','Lausanne','Neuchâtel','Fribourg','Sion','Vevey','Montreux','Martigny',
    'Zurich','Bâle','Berne','Luxembourg','Bruxelles','Namur','Liège','Mons','Charleroi','Anvers','Gand','Bruges',
    'Turin','Milan','Aoste','Modane','Briançon','Chamonix','Megève','Cluses','Rumilly','Albertville','Culoz',
];

// === Distances principales (km, pour estimation du prix) ===
$distancesConnues = [
    ['Annecy','Genève', 41],
    ['Paris','Lyon', 465],
    ['Lyon','Marseille', 315],
    ['Bordeaux','Toulouse', 240],
    ['Lille','Paris', 225],
    ['Lyon','Grenoble', 110],
    ['Grenoble','Chambéry', 60],
    ['Paris','Bordeaux', 585],
    ['Lyon','Annecy', 145],
    ['Lyon','Nice', 470],
    ['Marseille','Nice', 200],
    ['Paris','Marseille', 775],
];

// === Données complémentaires ===
$vehicules = [
    'Peugeot 208','Renault Clio','Citroën C3','Volkswagen Golf','Tesla Model 3',
    'Toyota Yaris','BMW Série 1','Mercedes Classe A','Fiat 500','Dacia Sandero'
];
$energies = ['Essence','Diesel','Électrique','Hybride'];
$commentaires = [
    'Pause café prévue ☕','Pas d’animaux svp 🐶❌','Trajet rapide sans détour 🕒',
    'Voiture confortable 🚗','Musique douce 🎵','Je peux prendre un bagage 🧳',
    'Départ ponctuel 👍','Trajet régulier chaque semaine','Arrêt possible à mi-chemin','Bonne humeur garantie 😁'
];

// === Fonctions utilitaires ===
function randomDate() {
    $now = time();
    $future = strtotime('+60 days');
    $t = mt_rand($now, $future);
    $h = mt_rand(6, 21);
    $m = [0,15,30,45][array_rand([0,15,30,45])];
    return date('Y-m-d ', $t) . sprintf('%02d:%02d:00', $h, $m);
}

function estimerPrixTotal($depart, $arrivee, $distancesConnues) {
    foreach ($distancesConnues as $axe) {
        if (
            (strcasecmp($axe[0], $depart) === 0 && strcasecmp($axe[1], $arrivee) === 0) ||
            (strcasecmp($axe[1], $depart) === 0 && strcasecmp($axe[0], $arrivee) === 0)
        ) {
            $d = $axe[2];
            $prixTotal = ($d * 0.12) + mt_rand(-150, 250)/100; // 0.12€/km ±2€
            return max(5, round($prixTotal, 2));
        }
    }
    // Si la distance n'est pas connue → estimation aléatoire réaliste
    $distance = mt_rand(30, 900);
    $prixTotal = ($distance * 0.11) + mt_rand(-300, 300)/100;
    return max(5, round($prixTotal, 2));
}

// === Génération des trajets ===
try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $userIds = $pdo->query("SELECT id FROM user")->fetchAll(PDO::FETCH_COLUMN);
    if (!$userIds) throw new Exception("Table user vide.");

    // Prépare la vérification anti-doublon
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM trajet 
        WHERE conducteur_id = :c 
          AND ville_depart = :vd 
          AND ville_arrivee = :va 
          AND DATE(date_depart) = DATE(:dd)
    ");

    // Prépare l'insertion
    $stmt = $pdo->prepare("
        INSERT INTO trajet (conducteur_id, ville_depart, ville_arrivee, date_depart, places_disponibles, prix, commentaire, type_vehicule, energie)
        VALUES (:conducteur_id, :ville_depart, :ville_arrivee, :date_depart, :places_disponibles, :prix, :commentaire, :type_vehicule, :energie)
    ");

    $pdo->beginTransaction();
    $inserted = 0;

    while ($inserted < $targetCount) {
        $villeDepart = $villes[array_rand($villes)];
        do { $villeArrivee = $villes[array_rand($villes)]; } while ($villeArrivee === $villeDepart);

        $conducteur = $userIds[array_rand($userIds)];
        $places = mt_rand(1, 5);

        // Prix total estimé puis conversion en prix par personne
        $prixTotal = estimerPrixTotal($villeDepart, $villeArrivee, $distancesConnues);
        $prixParPersonne = max(3, round(($prixTotal / $places) + mt_rand(-50, 50)/100, 2));

        // Choix du véhicule
        $typeVehicule = $vehicules[array_rand($vehicules)];

        // Énergie cohérente selon le véhicule
        if (str_contains($typeVehicule, 'Tesla')) {
            $energie = 'Électrique';
        } elseif (in_array($typeVehicule, ['Toyota Yaris','Hyundai Ioniq','Kia Niro','Peugeot 3008'])) {
            $energie = (mt_rand(0,1) ? 'Hybride' : 'Essence');
        } elseif (in_array($typeVehicule, ['Fiat 500','Dacia Sandero','Renault Clio','Peugeot 208','Citroën C3'])) {
            $energie = (mt_rand(0,1) ? 'Essence' : 'GPL');
        } elseif (in_array($typeVehicule, ['BMW Série 1','Mercedes Classe A','Volkswagen Golf'])) {
            $energie = (mt_rand(0,1) ? 'Diesel' : 'Essence');
        } else {
            $energie = $energies[array_rand($energies)];
        }

        $commentaire = $commentaires[array_rand($commentaires)];
        $date = randomDate();

        // Vérifie si un trajet identique existe déjà pour ce conducteur/jour
        $check->execute([
            ':c' => $conducteur,
            ':vd' => $villeDepart,
            ':va' => $villeArrivee,
            ':dd' => $date
        ]);
        if ($check->fetchColumn() > 0) {
            continue; // saute ce trajet
        }

        // Insère le trajet
        $stmt->execute([
            ':conducteur_id' => $conducteur,
            ':ville_depart' => $villeDepart,
            ':ville_arrivee' => $villeArrivee,
            ':date_depart' => $date,
            ':places_disponibles' => $places,
            ':prix' => $prixParPersonne,
            ':commentaire' => $commentaire,
            ':type_vehicule' => $typeVehicule,
            ':energie' => $energie,
        ]);

        $inserted++;
        if ($inserted % 1000 === 0) echo "Inserted: $inserted\n";
    }

    $pdo->commit();
    echo "✅ Terminé : $inserted trajets générés avec prix réalistes (par personne) et sans doublons exacts.\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "Erreur : " . $e->getMessage() . "\n";
}
