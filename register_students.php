<?php
/**
 * Script to register 270 students with pre-approved IDs
 * Access via: http://localhost/ClassSense/register_students.php
 */

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Starting student registration...\n";
echo "===========================================\n\n";

// List of students: LASTNAME, FIRSTNAME
$students = [
    "ABAD, MARCO LUIS",
    "ABALOS, JEROME ANDREW",
    "ABARQUEZ, NICOLE ANN",
    "ABELLA, CHRISTIAN PAOLO",
    "ABESAMIS, JOSHUA MIGUEL",
    "ABRINICA, STEPHEN DRAKE",
    "ABUNDA, ANGELICA MAE",
    "ACOSTA, JERICK DANIEL",
    "AGUILAR, KYLE PATRICK",
    "AGUSTIN, MARIA ANGELICA",
    "ALBA, JOHN CARLO",
    "ALCANTARA, RAVEN JAMES",
    "ALDAY, KIMBERLY JOY",
    "ALEJANDRO, MARK ANTHONY",
    "ALFEREZ, JANELLE GRACE",
    "ALFONSO, CARLO MIGUEL",
    "ALIPIO, JAYSON LEE",
    "ALMAZAN, KRISTINE JOY",
    "ALMENDRAS, ARK GABRIEL",
    "ALONZO, GABRIEL JOHN",
    "ALVARADO, SHANE MARIE",
    "AMBROSIO, KEVIN PAUL",
    "ANDAL, PRINCESS MAE",
    "ANDAYA, RALPH VINCENT",
    "ANGELES, JESSICA ANNE",
    "ANTONIO, JEROME CARLO",
    "AQUINO, KERNN STEVE CABUDTI",
    "AQUINO, PAOLO MIGUEL",
    "ARAGON, FAITH LOUISE",
    "ARANETA, JOHN REY",
    "ARCILLA, KYLA MAE",
    "ARELLANO, DANIEL JAMES",
    "ARGUELLES, MIKAELA ROSE",
    "ARIAS, JOSHUA PAUL",
    "ARROYO, ANGELICA JOY",
    "ASIS, CHRISTOPHER LEE",
    "ATIENZA, MARIELLE GRACE",
    "AUSTRIA, NATHAN ELIJAH",
    "AVILA, CAMILLE JOY",
    "BACANI, ANDREI LUIS",
    "BACOSA, JANELLE FAITH",
    "BAEZ, JOHN MICHAEL",
    "BALAGTAS, RYAN JAMES",
    "BALANE, KATRINA MAE",
    "BALDERAS, JERICO PAUL",
    "BALDONADO, KYLE ANDREW",
    "BALINGIT, SHEENA GRACE",
    "BALLESTEROS, MARK ANGELO",
    "BALMES, JASMINE ANN",
    "BALTAZAR, CHRISTIAN LUIS",
    "BANAL, JAYVEE ROSS",
    "BANDA, PRINCESS JOY",
    "BAÑEZ, KEVIN CARLO",
    "BARBOSA, RACHEL ANNE",
    "BARCENAS, KYZIA",
    "BARRAMEDA, CORREINE ARCEL",
    "BARRERA, DANICA MAE",
    "BARTOLOME, JOSHUA REY",
    "BASCO, MARY GRACE",
    "BAUTISTA, NICOLE FAITH",
    "BAYANI, JEROME LEE",
    "BELARMINO, PAOLO ANDRE",
    "BELEN, JANINE MAE",
    "BELTRAN, JAMES PATRICK",
    "BENITEZ, ALEXANDRA JOY",
    "BERNARDO, JOSHUA MIGUEL",
    "BERNAS, KYLA ROSE",
    "BERSAMIN, CARLO ANDREI",
    "BISCOCHO, MIKAELA JOY",
    "BLANCO, JOHN PAUL",
    "BOLO, AMONRRAH FAITH",
    "BONDOC, ANGELICA MARIE",
    "BORJA, JEROME DANIEL",
    "BRIONES, KIMBERLY ANN",
    "BUENAVENTURA, MARCO ANTONIO",
    "BUENO, JANELLE MAE",
    "BULAWAN, BETHANY",
    "CABALLERO, NATHAN JAMES",
    "CABANILLA, PRINCESS ANNE",
    "CABRERA, JOSHUA LEE",
    "CADIZ, RHEA MAE",
    "CAGUMBAY, ZYEIANA ANGELA",
    "CALDERON, GABRIEL PAOLO",
    "CALIMAG, JESSICA GRACE",
    "CALMA, ANDREW JOHN",
    "CALUMPANG, MARY ROSE",
    "CAMACHO, KYLE MIGUEL",
    "CAMBA, ANGEL FAITH",
    "CANLAS, CHRISTIAN REY",
    "CAPILI, JANINE LOUISE",
    "CARANDANG, JEROME PAUL",
    "CARPIO, SHANNA MAE",
    "CASAS, MARK JOSEPH",
    "CASTILLO, AIRA NICOLE",
    "CASTRO, JAYSON MIGUEL",
    "CATALAN, KATRINA JOY",
    "CATAPANG, JOHN RYAN",
    "CAYABYAB, CAMILLE GRACE",
    "CEBALLOS, DANIEL ANDREW",
    "CENIZA, JANELLE ROSE",
    "CERVANTES, JOSHUA CARLO",
    "CHAVEZ, MARIA KRISTINE",
    "CHUA, KEVIN LUIS",
    "CLARIDAD, ANGELICA MAE",
    "CLAVERIA, PAOLO JAMES",
    "CLEMENTE, RACHEL JOY",
    "COLLADO, JOHN MICHAEL",
    "CORPUZ, JASMINE ANN",
    "CORTEZ, NATHAN PAUL",
    "CRUZ, JERICHO DANIEL",
    "CUENCA, PRINCESS GRACE",
    "CUEVAS, MARK ANGELO",
    "CUNANAN, MIKAELA FAITH",
    "DACANAY, CHRISTIAN LEE",
    "DALISAY, KYLA MAE",
    "DAMASCO, JOSHUA REY",
    "DANIEL, JANELLE GRACE",
    "DANTES, CARLO MIGUEL",
    "DAROY, ANGELICA JOY",
    "DAVID, JOHN PAOLO",
    "DAYAO, KIMBERLY ANN",
    "DE GUZMAN, RYAN ANDREW",
    "DE LEON, CAMILLE ROSE",
    "DE MESA, JEROME LUIS",
    "DE VERA, NICOLE MAE",
    "DEL MORO, KYELCEE RAINNE",
    "DELA CRUZ, PAOLO MIGUEL",
    "DELA PEÑA, MARY FAITH",
    "DELFIN, JAMES CARLO",
    "DELOS REYES, ELIZA RHEIGH",
    "DEMETILLO, ANGELICA ROSE",
    "DESOLOC, RECCA ANGELA",
    "DIESTRO, CATHLEAH ZYRAH",
    "DIONSAY, ANGELO",
    "DIZON, JOHN ANDREW",
    "DOMINGO, JANELLE MAE",
    "DOMINGUEZ, KEVIN JAMES",
    "DORADO, KATRINA LOUISE",
    "DUARTE, CHRISTIAN PAOLO",
    "DULAY, JASMINE GRACE",
    "DUMAGUING, NATHAN LEE",
    "DUMANJUG, PRINCESS MAE",
    "DUMAPIT, JOSHUA ANDRE",
    "EBRADA, RACHEL ANN",
    "ECHAVARRIA, MARK LUIS",
    "EDQUIVILLO, MIKAELA JOY",
    "EJERCITO, CARLO JAMES",
    "ELARDO, KYLA FAITH",
    "ELIAS, JOHN MICHAEL",
    "ENCARNACION, JEROME PAUL",
    "ENRIQUEZ, CAMILLE MAE",
    "ESCALANTE, PAOLO REY",
    "ESPIRITU, JANELLE GRACE",
    "ESTEBAN, RYAN MIGUEL",
    "EVANGELISTA, ANGELICA JOY",
    "FABIAN, CHRISTIAN LEE",
    "FERNANDEZ, NICOLE ANN",
    "FERRER, JOSHUA PAUL",
    "FIGUEROA, MARY ROSE",
    "FLORES, DANIEL CARLO",
    "FRANCISCO, KATRINA MAE",
    "FRANCO, JOHN PATRICK",
    "FUENTES, JASMINE LOUISE",
    "GABRIEL, KEVIN ANDREW",
    "GALANG, PRINCESS JOY",
    "GALVEZ, JEROME MIGUEL",
    "GARCIA, ANGELICA FAITH",
    "GATCHALIAN, CARLO PAOLO",
    "GAVINO, JANELLE ROSE",
    "GERALDO, JOHN RYAN",
    "GIL, MIKAELA GRACE",
    "GIMENEZ, CHRISTIAN JAMES",
    "GO, NATHAN PAUL",
    "GONZALES, CAMILLE ANN",
    "GONZALEZ, JOSHUA REY",
    "GREGORIO, KIMBERLY JOY",
    "GUERRERO, PAOLO ANDRE",
    "GUEVARRA, JESSICA MAE",
    "GUTIERREZ, MARK ANTHONY",
    "HALILI, RACHEL GRACE",
    "HAVANA, SCARLET LEE",
    "HERNANDEZ, JOHN MIGUEL",
    "HICALE, JENIE MAE",
    "HILARIO, ANGELICA MAE",
    "HIPOLITO, JEROME CARLO",
    "HONTIVEROS, JANELLE FAITH",
    "IBA, CYRUS CLAUDE",
    "IGNACIO, CHRISTIAN PAOLO",
    "ILAGAN, NICOLE GRACE",
    "IMPERIAL, JOSHUA LEE",
    "INOCENCIO, MARIA JOY",
    "ISIDRO, KEVIN JAMES",
    "JACINTO, PRINCESS ANNE",
    "JAVIER, MARK PAOLO",
    "JAWON, JILLIANA",
    "JIMENEZ, KATRINA MAE",
    "JOAQUIN, RYAN LUIS",
    "JORDAN, CAMILLE ROSE",
    "JOSE, JOHN CARLO",
    "JUAN, ANGELICA GRACE",
    "JUGO, NATHAN MIGUEL",
    "KALAW, JANELLE ANN",
    "KATIGBAK, PAOLO JAMES",
    "LABRADOR, JOSHUA MIGUEL",
    "LACSON, MARY FAITH",
    "LAGMAN, KEVIN ANDREW",
    "LARA, DIRK JUN",
    "LAUREL, KYLA MAE",
    "LEGASPI, CHRISTIAN LEE",
    "LEONARDO, JASMINE ANN",
    "LIM, JOHN PAUL",
    "LISING, PRINCESS GRACE",
    "LISTAM, JUSTINE DWAYNN",
    "LORENZO, JEROME MIGUEL",
    "LUCERO, ANGELICA JOY",
    "LUMOCHO, MICHELLE GRACE",
    "LUNA, CARLO ANDREI",
    "LUPAC, MIKAELA ROSE",
    "MACALINO, JOSHUA PAUL",
    "MACAPAGAL, NICOLE ANN",
    "MACARAIG, RYAN JAMES",
    "MACATANGAY, JANELLE GRACE",
    "MABINI, CEARA DARRIANE",
    "MADRID, MARK ANGELO",
    "MAGALLANES, CAMILLE MAE",
    "MAGBANUA, CHRISTIAN PAOLO",
    "MAGNO, JESSICA FAITH",
    "MAGTOTO, JOHN MIGUEL",
    "MALABANAN, KYLA ROSE",
    "MALABUYOC, KEVIN LEE",
    "MALDONADO, PRINCESS JOY",
    "MALIGRO, PRECIOUS JECELLE",
    "MANTICA, VHON ZEDRICK",
    "MANTILLAS, EURICA DION",
    "MANALO, JOSHUA ANDRE",
    "MANANSALA, ANGELICA MAE",
    "MANAYAN, CARLO MIGUEL",
    "MANGAHAS, JANELLE ROSE",
    "MANUEL, JOHN PATRICK",
    "MARCELO, NICOLE GRACE",
    "MARAVILLA, CHELZEA LOUIZE",
    "MARQUEZ, CHRISTIAN REY",
    "MARTINEZ, MARY ANN",
    "MASANGKAY, JEROME PAUL",
    "MATEO, CAMILLE JOY",
    "MATIAS, JOSHUA LUIS",
    "MEDINA, JASMINE FAITH",
    "MENDOZA, KEVIN CARLO",
    "MERCADO, PRINCESS MAE",
    "MIEDES, PRINCE CLARK JASON",
    "MIRANDA, MARK JOSEPH",
    "MOLINA, RACHEL ANN",
    "MONTEVERDE, PAOLO JAMES",
    "MONTES, ANGELICA ROSE",
    "MORA, JOHN ANDREW",
    "MORALES, AYEISHA",
    "MURILLO, JANELLE MAE",
    "NARVAEZ, CHRISTIAN LUIS",
    "NAVARRO, KYLA GRACE",
    "NERI, JOSHUA MIGUEL",
    "NICOLAS, CAMILLE FAITH",
    "NOBLE, RYAN PAOLO",
    "NOLASCO, JESSICA ANN",
    "OBENITA, RAIDEN JAY",
    "OGAO, JYRYL KYLE",
    "ORDANIZA, PRINCESS MIA",
    "PALMARES, ASHLEE FRANCINE",
    "PALOMAR, SHEKINAH BLESS",
    "PESIDAS, CASSANDRA MORIELLE",
    "RANCHES, JHAYRON"
];

// Function to parse name
function parseName($fullName) {
    $parts = explode(',', $fullName);
    $lastName = trim($parts[0]);
    $firstName = trim($parts[1] ?? '');
    return [$lastName, $firstName];
}

// Function to generate realistic username from name
function generateUsername($lastName, $firstName, $index) {
    // Clean names
    $first = strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', $firstName)[0]));
    $last = strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', $lastName)[0]));

    // Generate different realistic patterns
    $patterns = [
        $first . $last,                           // marcoabad
        $first . '_' . $last,                     // marco_abad
        $first . '.' . $last,                     // marco.abad
        $first[0] . $last,                        // mabad
        $first . $last[0],                        // marcoa
        $first . $last . rand(10, 99),            // marcoabad23
        substr($first, 0, 4) . $last,             // marcabad (if first name is long)
        $first . substr($last, 0, 3),             // marcoaba
        $last . $first,                           // abadmarco
        $last . $first[0],                        // abadm
    ];

    // Use index to pick a pattern (deterministic but varied)
    $patternIndex = $index % count($patterns);
    return $patterns[$patternIndex];
}

// Function to generate unique username
function makeUniqueUsername($pdo, $baseUsername) {
    $username = $baseUsername;
    $counter = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            return $username;
        }
        $username = $baseUsername . $counter;
        $counter++;
    }
}

// Function to generate Philippine phone number
function generatePhoneNumber() {
    // Format: 09XXXXXXXXX (11 digits)
    $prefixes = ['09'];
    $prefix = $prefixes[array_rand($prefixes)];
    $suffix = str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    return $prefix . $suffix;
}

// Function to generate secure password
function generatePassword($lastName, $studentId) {
    // Format: LastnameStudentID (e.g., Cruz26123456)
    $cleanLastName = ucfirst(strtolower(preg_replace('/[^a-zA-Z]/', '', explode(' ', $lastName)[0])));
    return $cleanLastName . $studentId;
}

// Function to determine creation date based on distribution
function getCreationDate($index, $total) {
    // 80% = 2026-10-20
    // 15% = 2026-11-20
    // 5% = 2026-12-20

    $percentage = ($index / $total) * 100;

    if ($percentage < 80) {
        return '2026-10-20 08:00:00'; // 80% - October 20
    } elseif ($percentage < 95) {
        return '2026-11-20 09:00:00'; // 15% - November 20
    } else {
        return '2026-12-20 10:00:00'; // 5% - December 20
    }
}

try {
    $pdo = getPDO();

    // Fetch all available pre-approved student IDs (ordered)
    $stmt = $pdo->query("SELECT id, student_id FROM pre_approved_students WHERE used_at IS NULL ORDER BY student_id ASC");
    $availableIds = $stmt->fetchAll();

    echo "Available pre-approved IDs: " . count($availableIds) . "\n";
    echo "Students to register: " . count($students) . "\n\n";

    if (count($availableIds) < count($students)) {
        echo "ERROR: Not enough pre-approved IDs available!\n";
        exit(1);
    }

    echo "Starting registration process...\n";
    echo "===========================================\n\n";

    $successCount = 0;
    $failedCount = 0;
    $errors = [];

    $totalStudents = count($students);

    foreach ($students as $index => $studentName) {
        list($lastName, $firstName) = parseName($studentName);
        $studentId = $availableIds[$index]['student_id'];

        // Generate username (ensure uniqueness)
        $baseUsername = generateUsername($lastName, $firstName, $index);
        $username = makeUniqueUsername($pdo, $baseUsername);

        // Generate password
        $password = generatePassword($lastName, $studentId);

        // Generate phone number
        $phone = generatePhoneNumber();

        // Determine creation date based on distribution
        $createdAt = getCreationDate($index, $totalStudents);

        // Register the student
        try {
            $uid = strtolower(bin2hex(random_bytes(16)));
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user with specific created_at date
            $stmt = $pdo->prepare("INSERT INTO users (uid, username, password_hash, role, role_type, first_name, last_name, student_id, phone, created_at) VALUES (?, ?, ?, 'student', 'student', ?, ?, ?, ?, ?)");
            $stmt->execute([$uid, $username, $passwordHash, $firstName, $lastName, $studentId, $phone, $createdAt]);

            // Mark student ID as used with the same date
            $stmt = $pdo->prepare("UPDATE pre_approved_students SET used_at = ? WHERE student_id = ?");
            $stmt->execute([$createdAt, $studentId]);

            $successCount++;

            if ($successCount % 25 == 0) {
                echo "Progress: {$successCount}/" . count($students) . " registered...\n";
                flush();
            }

        } catch (PDOException $e) {
            $failedCount++;
            $errors[] = [
                'name' => $studentName,
                'error' => $e->getMessage()
            ];
        }
    }

    echo "\n===========================================\n";
    echo "Registration completed!\n";
    echo "===========================================\n";
    echo "Successfully registered: {$successCount} students\n";
    echo "Failed: {$failedCount}\n\n";

    if ($failedCount > 0) {
        echo "Errors:\n";
        foreach ($errors as $error) {
            echo "  - {$error['name']}: {$error['error']}\n";
        }
        echo "\n";
    }

    // Show sample accounts
    echo "===========================================\n";
    echo "Sample student accounts:\n";
    echo "===========================================\n";

    $sampleStmt = $pdo->query("SELECT TOP 10 student_id, username, first_name, last_name, phone FROM users WHERE role = 'student' ORDER BY student_id DESC");
    $samples = $sampleStmt->fetchAll();

    foreach ($samples as $sample) {
        list($sampleLastName, $sampleFirstName) = [$sample['last_name'], $sample['first_name']];
        $samplePassword = generatePassword($sampleLastName, $sample['student_id']);

        echo "\nStudent ID: {$sample['student_id']}\n";
        echo "Name: {$sample['last_name']}, {$sample['first_name']}\n";
        echo "Username: {$sample['username']}\n";
        echo "Password: {$samplePassword}\n";
        echo "Phone: {$sample['phone']}\n";
    }

    // Total count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetch()['count'];
    echo "\n===========================================\n";
    echo "Total students in system: {$totalStudents}\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✓ Done! You can now delete this file for security.\n";
echo "\nNOTE: Password format is LastnameStudentID (e.g., Cruz26123456)\n";
