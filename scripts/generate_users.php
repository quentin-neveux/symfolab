<?php
// Quentin – génération réaliste d'utilisateurs EcoRide

$host = '127.0.0.1';
$port = 3307;
$db   = 'ecoride_symfony';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';
$targetCount = 5200;

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$firstNames = [
    'Lucas','Louis','Gabriel','Arthur','Jules','Léo','Raphaël','Hugo','Ethan','Noah',
    'Marie','Camille','Chloé','Louise','Manon','Léa','Julie','Sarah','Inès','Clara',
    'Pierre','Thomas','Maxime','Nicolas','Alexandre','Antoine','Paul','Baptiste','Vincent','Romain',
    'Sophie','Charlotte','Emma','Valentine','Anaïs','Laura','Émilie','Amélie','Lina','Océane'
];

$lastNames = [
    'Martin','Bernard','Dubois','Thomas','Robert','Richard','Petit','Durand','Leroy','Moreau',
    'Simon','Laurent','Lefebvre','Mercier','Rousseau','Vincent','Fournier','Morel','Girard','Andre',
    'Marchand','Duval','Garnier','Faure','Blanc','Guerin','Muller','Henry','Rousseau','Colin',
    'Nicolas','Perrin','Renaud','Robin','Gonzalez','Brun','Gauthier','Chevalier','Adam','Meyer'
];

$domains = ['gmail.com','hotmail.com','outlook.com','yahoo.com','proton.me','example.com'];

function randDateInLastYears($years = 3) {
    $end = time();
    $start = strtotime("-{$years} years", $end);
    return date('Y-m-d H:i:s', mt_rand($start, $end));
}

function frenchPhoneNumber() {
    $prefix = (mt_rand(0,1) ? '06' : '07');
    $n = '';
    for ($i=0;$i<8;$i++) $n .= mt_rand(0,9);
    return $prefix . $n;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $check = $pdo->query("SHOW TABLES LIKE 'user'")->fetch();
    if (!$check) throw new Exception("Table `user` not found in database $db.");

    $sql = "INSERT INTO user (email, roles, password, prenom, nom, pseudo, note_moyenne, created_at, photo, telephone)
            VALUES (:email, :roles, :password, :prenom, :nom, :pseudo, :note_moyenne, :created_at, :photo, :telephone)";
    $stmt = $pdo->prepare($sql);

    $usedEmails = [];
    $basePasswordPlain = 'Ec0Ride2025!';
    $hashedPassword = password_hash($basePasswordPlain, PASSWORD_BCRYPT);

    $pdo->beginTransaction();
    $inserted = 0;
    $seed = 0;

    while ($inserted < $targetCount) {
        $seed++;
        $prenom = $firstNames[array_rand($firstNames)];
        $nom = $lastNames[array_rand($lastNames)];
        $num = mt_rand(1, 9999);
        $domain = $domains[array_rand($domains)];
        $localPrenom = iconv('UTF-8', 'ASCII//TRANSLIT', $prenom);
        $localNom = iconv('UTF-8', 'ASCII//TRANSLIT', $nom);
        $localPrenom = preg_replace('/[^a-zA-Z]/', '', $localPrenom);
        $localNom = preg_replace('/[^a-zA-Z]/', '', $localNom);
        $email = strtolower($localPrenom . '.' . $localNom . $num . '@' . $domain);

        if (isset($usedEmails[$email])) {
            $email = strtolower($localPrenom . '.' . $localNom . $num . '.' . $seed . '@' . $domain);
        }
        $usedEmails[$email] = true;

        $isAdmin = (mt_rand(1,1000) <= 10);
        $roles = $isAdmin ? ['ROLE_ADMIN','ROLE_USER'] : ['ROLE_USER'];
        $createdAt = randDateInLastYears(3);
        $telephone = frenchPhoneNumber();
        $photo = null;

        // pseudo = prenom + première lettre du nom + chiffre
        $pseudo = strtolower($localPrenom . substr($localNom, 0, 1) . mt_rand(1, 99));

        // note aléatoire réaliste
        $noteMoyenne = round(mt_rand(30, 50) / 10, 1); // entre 3.0 et 5.0

        $stmt->execute([
            ':email' => $email,
            ':roles' => json_encode($roles, JSON_UNESCAPED_UNICODE),
            ':password' => $hashedPassword,
            ':prenom' => $prenom,
            ':nom' => $nom,
            ':pseudo' => $pseudo,
            ':note_moyenne' => $noteMoyenne,
            ':created_at' => $createdAt,
            ':photo' => $photo,
            ':telephone' => $telephone,
        ]);

        $inserted++;
        if ($inserted % 500 === 0) echo "Inserted: $inserted\n";
    }

    $pdo->commit();
    echo "✅ Done. Inserted $inserted users.\n";
    echo "Password for all generated users: $basePasswordPlain (hashed in DB).\n";
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
