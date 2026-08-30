<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración predeterminada para XAMPP / phpMyAdmin
$host = 'localhost';
$dbname = 'sb';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Error al conectar con la base de datos: ' . $e->getMessage()
    ]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_all';

switch ($action) {
    case 'get_all':
        try {
            // 1. Obtener Instituciones
            $instituciones = $pdo->query("SELECT id, codigo AS code, nombre AS name, ciudad AS city FROM instituciones")->fetchAll();
            
            // 2. Obtener Profesores
            $profesores = $pdo->query("SELECT id, dni, nombre AS name, email, institucion_id AS schoolId FROM profesores")->fetchAll();
            
            // 3. Obtener Alumnos
            $alumnos = $pdo->query("SELECT id, dni, nombre AS name, ano AS year, division, nivel, institucion_id AS schoolId, profesor_id AS teacherId FROM alumnos")->fetchAll();
            
            // 4. Obtener Torneos
            $torneos = $pdo->query("SELECT id, nombre AS name, tipo AS type, cantidad_rondas AS roundsCount, pasan_por_ronda AS maxAdvancingPerRound, nivel_activo AS activeLevel, ronda_activa AS activeRound, estado AS status FROM torneos")->fetchAll();
            
            // Cargar participantes inscriptos para cada torneo
            foreach ($torneos as &$t) {
                $stmt = $pdo->prepare("SELECT alumno_id FROM torneo_alumnos WHERE torneo_id = ?");
                $stmt->execute([$t['id']]);
                $t['participantIds'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            // 5. Obtener Registros de Tiempos / Performances
            $performances = $pdo->query("SELECT id, torneo_id AS tournamentId, alumno_id AS studentId, nivel AS level, ronda AS round, tiempo_deletreo AS spellingTimeSec, tiempo_oracion AS sentenceTimeSec, penalizaciones AS penalties, tiempo_total AS totalTimeSec, fecha_registro AS timestamp FROM performances ORDER BY fecha_registro DESC")->fetchAll();

            echo json_encode([
                'success' => true,
                'data' => [
                    'schools' => $instituciones,
                    'teachers' => $profesores,
                    'students' => $alumnos,
                    'tournaments' => $torneos,
                    'performances' => $performances
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'save_performance':
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        if (!$input) {
            $input = $_POST;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO performances 
                (torneo_id, alumno_id, nivel, ronda, tiempo_deletreo, tiempo_oracion, penalizaciones, tiempo_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $input['tournamentId'],
                $input['studentId'],
                $input['level'],
                $input['round'],
                $input['spellingTimeSec'],
                $input['sentenceTimeSec'],
                $input['penalties'],
                $input['totalTimeSec']
            ]);

            echo json_encode([
                'success' => $success,
                'inserted_id' => $pdo->lastInsertId()
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
        break;
}
?>