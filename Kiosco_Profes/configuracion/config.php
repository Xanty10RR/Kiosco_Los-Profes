<?php
// ==================================================================================
// CONFIGURACIÓN, CONSTANTES Y FUNCIONES DE BASE DE DATOS (MODELO)
// ==================================================================================

session_start();

// --- 1. Configuración de Credenciales (¡Cuidado con 'root' en producción!) ---
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kiosco_profes_db');

// --- 2. Conexión a la Base de Datos ---
$pdo = null;
$db_connected = false;
$error_message = '';

try {
    // Se agrega el puerto 3307 a la cadena de conexión
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    // La conexión fallida será manejada en index.php, solo guardamos el error
    $error_message = "⚠️ Error de conexión a la base de datos: " . $e->getMessage();
}

// --- 3. Variables y Constantes de la Aplicación ---
$ASSIGNATURE_CARDS = [
    // ... (Mantener TODA la matriz $ASSIGNATURE_CARDS aquí) ...
    [
        'subject' => 'Matemáticas', 
        'icon' => '<svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M19 10H5a2 2 0 00-2 2v2a2 2 0 002 2h14a2 2 0 002-2v-2a2 2 0 00-2-2z"></path></svg>',
        'color' => 'hover:bg-indigo-50 border-indigo-500'
    ],
    // ... (Agregar el resto de tarjetas aquí) ...
    [
        'subject' => 'Otro tipo de asesorías', 
        'icon' => '<svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        'color' => 'hover:bg-gray-100 border-gray-500'
    ],
];

$VIEWS = [
    'SCHEDULE_VIEW' => 'schedule',
    'PAYMENT_VIEW' => 'payment',
    'ADMIN_LOGIN' => 'admin_login',
    'ADMIN_DASHBOARD' => 'admin_dashboard',
    // NUEVO: Vista de edición para el Admin
    'ADMIN_EDIT' => 'admin_edit', 
];

// Obtener o crear un ID de sesión para el estudiante anónimo
if (!isset($_SESSION['student_session_id'])) {
    $_SESSION['student_session_id'] = uniqid('student_');
}
$student_session_id = $_SESSION['student_session_id'];

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Variables para el dashboard Admin (ver mejora en index.php)
$admin_filter = $_GET['filter'] ?? 'ALL';
$admin_appointments = []; 

// --- 4. Funciones CRUD (Mantener todas las funciones que manipulan la DB aquí) ---

// 🔥 IMPORTANTE: Aquí se mantienen get_current_appointment, get_appointment_by_id, 
// get_all_appointments (MODIFICADA con filtro), schedule_appointment, 
// update_appointment (MODIFICADA para null), y delete_appointment.

/**
 * Obtiene todas las citas para el panel de administración, con opción de filtrado.
 */
function get_all_appointments($pdo, $filter_status = 'ALL') {
    global $error_message;
    if (!$pdo) return [];

    $sql = "SELECT * FROM appointments";
    $params = [];

    // Lógica del filtro (implementada en la mejora anterior)
    if ($filter_status !== 'ALL' && in_array($filter_status, ['PAID', 'PENDING_VALIDATION', 'PENDING_PAYMENT', 'CANCELLED'])) {
        $sql .= " WHERE status = ?";
        $params[] = $filter_status;
    }

    $sql .= " ORDER BY created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ... (Tu código de conversión de DateTime se mantiene aquí) ...
        return $appointments;
    } catch (PDOException $e) {
        $error_message = "Error al obtener citas para Admin: " . $e->getMessage();
        return [];
    }
}


/**
 * Actualiza el estado o detalles de comprobante/detalle de una cita. (Update - U)
 */
function update_appointment($id, $updates, $pdo) {
    global $error_message;
    if (!$pdo) return false;

    $set_clauses = [];
    $execute_params = [];
    
    // Lista segura de campos actualizables
    $allowed_updates = ['status', 'proof_details', 'expires_at', 'subject', 'other_subject', 'date', 'time', 'student_name', 'student_contact'];

    foreach ($updates as $key => $value) {
        if (in_array($key, $allowed_updates)) {
            $set_clauses[] = "$key = ?";
            // 🔥 CORRECCIÓN CLAVE: Esto asegura que los valores NULL se pasen correctamente a PDO
            $execute_params[] = ($value === null) ? null : $value;
        }
    }

    if (empty($set_clauses)) return false;

    $execute_params[] = $id;

    try {
        $sql = "UPDATE appointments SET " . implode(', ', $set_clauses) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($execute_params);
    } catch (PDOException $e) {
        $error_message = "Error al actualizar cita: " . $e->getMessage();
        return false;
    }
}

// ... (El resto de tus funciones CRUD se mantienen aquí: get_current_appointment, schedule_appointment, etc.) ...