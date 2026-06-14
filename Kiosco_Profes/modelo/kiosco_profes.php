<?php
// ==================================================================================
// CONFIGURACIÓN Y UTILIDADES PHP
// ==================================================================================

// Iniciar sesión para rastrear el estado del usuario (admin) y la sesión del estudiante.
session_start();

// --- 1. Configuración de la Base de Datos MySQL ---
// ATENCIÓN: Debe reemplazar estos valores con sus credenciales reales de MySQL.
define('DB_HOST', '127.0.0.1'); // Agregue 127.0.0.1
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kiosco_profes_db'); // Asegúrese de que esta DB exista (ejecute database_setup.sql)

// --- 2. Conexión a la Base de Datos ---
$pdo = null;
$db_connected = false;
$error_message = '';

try {
    // Conexión forzada al puerto 3307 que es el que tienes activo en XAMPP
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=3307;dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_connected = true;
} catch (PDOException $e) {
    // Si falla la conexión, la aplicación lo mostrará en el frontend.
    $db_connected = false;
    $error_message = "⚠️ Error de conexión a la base de datos. Por favor, revise DB_HOST, DB_USER y DB_PASS. Mensaje: " . $e->getMessage();
}

// --- GESTIÓN DE SLIDER Y ASIGNATURAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {

    // 1. Agregar Slide
    if (isset($_POST['action']) && $_POST['action'] === 'add_slide') {
        $title = $_POST['title'];
        $file = $_FILES['slide_image'];

        if(!isset($_FILES['slide_image']) || $_FILES['slide_image']['error'] !==UPLOAD_ERR_OK) {
            die('Error: Debes subir una imagen');
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['slide_image']['type'];
    
        if (!in_array($file_type, $allowed_types)) {
            die('Error: Solo se permiten imágenes JPG, PNG, GIF o WEBP');
        }
    
        // Validar tamaño: máximo 5MB
        if ($_FILES['slide_image']['size'] > 5 * 1024 * 1024) {
            die('Error: La imagen es muy pesada. Maximo 5MB.');
        }

        // Ruta fisica para guardar el archivo
        $target_dir = __DIR__ . '/../uploads/slider';

        // Si no exise la carpea la crea
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . '/' . $new_filename;

        // Ruta relativa para guardar en BD y usar en el HTML
        $db_path = 'uploads/slider/' . $new_filename;

        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO slider_content (image_path, title) VALUES (?, ?)");
            $stmt->execute([$db_path, $title]);
            header("Location: kiosco_profes.php?vista=slides&success=1"); // Recarga para evitar reenvio
            exit;
        } else {
            die("Error: No se pudo subir la imagen. Revisa los permisos de la carpeta.");
        }
    }

    // 2. Agregar Asignatura
    if (isset($_POST['action']) && $_POST['action'] === 'add_subject') {
        $name = $_POST['subject_name'];
        $color = $_POST['subject_color'];
        $stmt = $pdo->prepare("INSERT INTO subjects_list (name, color_hex) VALUES (?, ?)");
        $stmt->execute([$name, $color]);
    }
    
    // Eliminar del servidor
    if (isset($_POST['delete_type']) && $_POST['delete_type'] === 'slide') {
        $id = $_POST['id'];

        // Busca que img borrar
        $stmt = $pdo->prepare("SELECT image_path FROM slider_content WHERE id = ?");
        $stmt->execute([$id]);
        $slide = $stmt->fetch();
        
        // Borra la img
        if ($slide && file_exists(__DIR__ . '/../' . $slide['image_path'])) {
            unlink(__DIR__ . '/../' . $slide['image_path']); // Borra del servidor
        }

        // Borra la img de la bd
        $pdo->prepare("DELETE  FROM slider_content WHERE id = ?")->execute([$_POST['id']]);

        // Mensaje de hecho
        header("Location: kiosco_profes.php?vista=slides&deleted=1");
        exit;
    }

    // 3. Eliminar (Simple)
    if (isset($_POST['delete_type'])) {
        $id = $_POST['id'];
        if ($_POST['delete_type'] === 'slide') {
            $stmt = $pdo->prepare("DELETE FROM slider_content WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM subjects_list WHERE id = ?");
        }
        $stmt->execute([$id]);
    }
}

// --- 3. Variables y Constantes de la Aplicación ---
// Mantenemos la lista de asignaturas para la lógica, aunque la vista principal use las tarjetas.
$ASSIGNATURES = [
    'Matemáticas',
    'Inglés',
    'Química',
    'Física',
    'Biología',
    'Comprensión Lectora',
    'Ciencias Sociales',
    'Otro Tipo de Asesorías'
];

// NUEVO: Definición de las tarjetas para la vista interactiva
$ASSIGNATURE_CARDS = [
    [
        'subject' => 'Matemáticas',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-math-symbols"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 12l18 0" /><path d="M12 3l0 18" /><path d="M16.5 4.5l3 3" /><path d="M19.5 4.5l-3 3" /><path d="M6 4l0 4" /><path d="M4 6l4 0" /><path d="M18 16l.01 0" /><path d="M18 20l.01 0" /><path d="M4 18l4 0" /></svg>', // Icono eliminado
        'color' => 'hover:bg-indigo-50 border border-indigo-500'
    ],
    [
        'subject' => 'Inglés',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-language"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M9 6.371c0 4.418 -2.239 6.629 -5 6.629" /><path d="M4 6.371h7" /><path d="M5 9c0 2.144 2.252 3.908 6 4" /><path d="M12 20l4 -9l4 9" /><path d="M19.1 18h-6.2" /><path d="M6.694 3l.793 .582" /></svg>', // Icono eliminado
        'color' => 'orange'
    ],
    [
        'subject' => 'Química',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-flask"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M15 2a1 1 0 0 1 0 2v4.826l3.932 10.814l.034 .077a1.7 1.7 0 0 1 -.002 1.193l-.07 .162a1.7 1.7 0 0 1 -1.213 .911l-.181 .017h-11l-.181 -.017a1.7 1.7 0 0 1 -1.285 -2.266l.039 -.09l3.927 -10.804v-4.823a1 1 0 1 1 0 -2h6zm-2 2h-2v4h2v-4z" /></svg>', // Icono eliminado
        'color' => 'green'
    ],
    [
        'subject' => 'Física',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-bulb"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 11a1 1 0 0 1 .117 1.993l-.117 .007h-1a1 1 0 0 1 -.117 -1.993l.117 -.007h1z" /><path d="M12 2a1 1 0 0 1 .993 .883l.007 .117v1a1 1 0 0 1 -1.993 .117l-.007 -.117v-1a1 1 0 0 1 1 -1z" /><path d="M21 11a1 1 0 0 1 .117 1.993l-.117 .007h-1a1 1 0 0 1 -.117 -1.993l.117 -.007h1z" /><path d="M4.893 4.893a1 1 0 0 1 1.32 -.083l.094 .083l.7 .7a1 1 0 0 1 -1.32 1.497l-.094 -.083l-.7 -.7a1 1 0 0 1 0 -1.414z" /><path d="M17.693 4.893a1 1 0 0 1 1.497 1.32l-.083 .094l-.7 .7a1 1 0 0 1 -1.497 -1.32l.083 -.094l.7 -.7z" /><path d="M14 18a1 1 0 0 1 1 1a3 3 0 0 1 -6 0a1 1 0 0 1 .883 -.993l.117 -.007h4z" /><path d="M12 6a6 6 0 0 1 3.6 10.8a1 1 0 0 1 -.471 .192l-.129 .008h-6a1 1 0 0 1 -.6 -.2a6 6 0 0 1 3.6 -10.8z" /></svg>', // Icono eliminado
        'color' => 'purple'
    ],
    [
        'subject' => 'Biología',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-microscope"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M15.707 4.293l3 3a1 1 0 0 1 0 1.414l-1.553 1.555a7 7 0 0 1 -.256 9.74l2.102 -.002a1 1 0 0 1 0 2h-14a1 1 0 0 1 0 -2h1v-1a1 1 0 0 1 0 -2h2a1 1 0 0 1 0 2v1h4a5 5 0 0 0 3.737 -8.323l-3.03 3.03a1 1 0 0 1 -1.414 0l-.793 -.792l-.793 .792a1 1 0 1 1 -1.414 -1.414l.792 -.793l-.792 -.793a1 1 0 0 1 0 -1.414l6 -6a1 1 0 0 1 1.414 0m2 -2l3 3a1 1 0 1 1 -1.414 1.414l-3 -3a1 1 0 1 1 1.414 -1.414" /></svg>', // Icono eliminado
        'color' => 'cyan'
    ],
    [
        'subject' => 'Comprensión Lectora',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-book"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M21.5 5.134a1 1 0 0 1 .493 .748l.007 .118v13a1 1 0 0 1 -1.5 .866a8 8 0 0 0 -7.5 -.266v-15.174a10 10 0 0 1 8.5 .708m-10.5 -.707l.001 15.174a8 8 0 0 0 -7.234 .117l-.327 .18l-.103 .044l-.049 .016l-.11 .026l-.061 .01l-.117 .006h-.042l-.11 -.012l-.077 -.014l-.108 -.032l-.126 -.056l-.095 -.056l-.089 -.067l-.06 -.056l-.073 -.082l-.064 -.089l-.022 -.036l-.032 -.06l-.044 -.103l-.016 -.049l-.026 -.11l-.01 -.061l-.004 -.049l-.002 -13.068a1 1 0 0 1 .5 -.866a10 10 0 0 1 8.5 -.707" /></svg>', // Icono eliminado
        'color' => 'yellow'
    ],
    [
        'subject' => 'Ciencias Sociales',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-globe"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M11 4a5 5 0 1 1 -4.995 5.217l-.005 -.217l.005 -.217a5 5 0 0 1 4.995 -4.783z" /><path d="M14.133 1.502a1 1 0 0 1 1.365 -.369a9.015 9.015 0 1 1 -10.404 14.622a1 1 0 1 1 1.312 -1.51a7.015 7.015 0 1 0 8.096 -11.378a1 1 0 0 1 -.369 -1.365z" /><path d="M11 16a1 1 0 0 1 .993 .883l.007 .117v4a1 1 0 0 1 -1.993 .117l-.007 -.117v-4a1 1 0 0 1 1 -1z" /><path d="M15 20a1 1 0 0 1 .117 1.993l-.117 .007h-8a1 1 0 0 1 -.117 -1.993l.117 -.007h8z" /></svg>', // Icono eliminado
        'color' => 'red'
    ],
    [
        'subject' => 'Otro Tipo de Asesorías',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="icon icon-tabler icons-tabler-filled icon-tabler-zoom-question"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M14 3.072a8 8 0 0 1 2.32 11.834l5.387 5.387a1 1 0 0 1 -1.414 1.414l-5.388 -5.387a8 8 0 0 1 -12.905 -6.32l.005 -.285a8 8 0 0 1 11.995 -6.643m-4 8.928a1 1 0 0 0 -.993 .883l-.007 .127a1 1 0 0 0 1.993 .117l.007 -.127a1 1 0 0 0 -1 -1m-1.9 -5.123a1 1 0 0 0 1.433 1.389l.088 -.09a.5 .5 0 1 1 .379 .824a1 1 0 0 0 -.002 2a2.5 2.5 0 1 0 -1.9 -4.123" /></svg>', // Icono eliminado
        'color' => 'pink'
    ],
];
// --- Acción para verificar estado de la cita vía AJAX ---
if (isset($_GET['action']) && $_GET['action'] === 'check_status' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT status FROM appointments WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status' => $appointment['status'] ?? 'DELETED']);
    exit;
}


$VIEWS = [
    'SCHEDULE_VIEW' => 'schedule',
    'PAYMENT_VIEW' => 'payment',
    'ADMIN_LOGIN' => 'admin_login',
    'ADMIN_DASHBOARD' => 'admin_dashboard',
];

// Obtener o crear un ID de sesión para el estudiante anónimo (simula el Firebase UID)
if (!isset($_SESSION['student_session_id'])) {
    $_SESSION['student_session_id'] = uniqid('student_');
}
$student_session_id = $_SESSION['student_session_id'];

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Inicializar estado de la cita actual
$current_appointment = null;


// --- PROCESAR LIBERACIÓN MANUAL ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment_manual') {
    $id_cita = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'PAID' WHERE id = ?");
    if ($stmt->execute([$id_cita])) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . ($_GET['filter'] ?? 'ALL') . "&success=1");
        exit();
    }
}
// --- 4. Funciones CRUD para MySQL ---
// --- ACCIÓN: CONFIRMAR PAGO MANUALMENTE (ADMIN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment_manual') {
    $appointment_id = $_POST['id'];

    // Cambiamos el estado a 'PAID' (que es el que configuramos como verde)
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'PAID' WHERE id = ?");

    if ($stmt->execute([$appointment_id])) {
        // Redirigimos para ver los cambios
        $filter = $_GET['filter'] ?? 'ALL';
        header("Location: " . $_SERVER['PHP_SELF'] . "?view=" . $VIEWS['ADMIN_DASHBOARD'] . "&filter=" . $filter . "&success=1");
        exit();
    }
}
/**
 * Intenta obtener la cita actual del estudiante. También cancela citas expiradas.
 */
function get_current_appointment($student_id, $pdo)
{
    global $error_message;
    if (!$pdo) return null;

    try {
        // Primero, cancelar cualquier cita PENDING_PAYMENT que haya expirado
        $stmt_cancel = $pdo->prepare("
            UPDATE appointments 
            SET status = 'CANCELLED' 
            WHERE status = 'PENDING_PAYMENT' 
            AND expires_at < NOW()
        ");
        $stmt_cancel->execute(); // Ejecutar en todas las citas, no solo en la del estudiante actual

        // Luego, buscar la cita activa (no CANCELLED, no PAID) para el estudiante actual
        $stmt = $pdo->prepare("
            SELECT * FROM appointments 
            WHERE student_session_id = ? 
            AND status IN ('PENDING_PAYMENT', 'PENDING_VALIDATION')
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$student_id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convertir strings de fecha a objetos DateTime para consistencia
        if ($appointment) {
            // Manejar expires_at que puede ser NULL
            $appointment['created_at'] = new DateTime($appointment['created_at']);
            if ($appointment['expires_at']) {
                $appointment['expires_at'] = new DateTime($appointment['expires_at']);
            }
        }

        return $appointment;
    } catch (PDOException $e) {
        $error_message = "Error al obtener Asesoria: " . $e->getMessage();
        return null;
    }
}
// --- Lógica para Actualizar Estado de la Cita (Confirmar/Rechazar) ---
// --- ACCIÓN: CONFIRMAR PAGO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    $appointment_id = $_POST['id'];

    // Actualizamos a PAID
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'PAID' WHERE id = ?");

    if ($stmt->execute([$appointment_id])) {
        // Mantenemos el filtro actual para que el usuario no se pierda
        $current_filter = $_GET['filter'] ?? 'ALL';
        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . $current_filter . "&success=1");
        exit();
    }
}
// --- Lógica para Confirmar Pago y Liberar Asesoría ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_payment') {
    $appointment_id = $_POST['id'];

    // Actualizamos el estado a CONFIRMED
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'CONFIRMADO/PAGADA' WHERE id = ?");
    if ($stmt->execute([$appointment_id])) {
        // Redirigimos para refrescar la tabla y mostrar el cambio
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=success");
        exit();
    }
}
    
// --- Lógica para Exportar Informe CSV Organizado ---
if (isset($_GET['action']) && $_GET['action'] === 'export_xls') {

        //Limpiar buffer para evitar errores de descarga
        while (ob_get_level()) ob_end_clean();

        // 1. Obtener filtros de la URL
        $current_filter = $_GET['filter'] ?? 'ALL';
        $search = $_GET['search'] ?? '';

        //Forzar UTF-8 en la conexión PDO
        $pdo->exec("SET NAMES 'utf8mb4'");

        // 2. Construir consulta dinámica
        $query = "SELECT id, student_name, student_contact, subject, other_subject, date, time, proof_details, status FROM appointments WHERE 1=1";
        $params = [];

        // Filtro por Estado (Botones/Cards)
        if ($current_filter !== 'ALL') {
            $query .= " AND status = :status";
            $params['status'] = $current_filter;
        }

        // Filtro por Buscador (Nombre o Materia)
        if (!empty($search)) {
            $query .= " AND (student_name LIKE :search OR subject LIKE :search OR other_subject LIKE :search OR id LIKE :search OR proof_details LIKE :search)";
            $params['search'] = "%$search%";
        }

        $query .= " ORDER BY date DESC, time DESC";

        // 3. Ejecutar
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Cabeceras para descarga limpia
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Reporte_Filtrado_' . date('d-m-Y') . '.xls"');
        header('Cache-Control: max-age=0');

        //BOM para UTF-8
        echo "\xEF\xBB\xBF";
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:ofice" xmlns:x="urn:schemas-microsoft-com:office:exel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>';
        echo '<table border="1" x:str>';
        echo '<tr>
            <th>ID</th>
            <th>ESTUDIANTE</th>
            <th>CONTACTO</th>
            <th>ASIGNATURA</th>
            <th>DETALLE MATERIA</th>
            <th>FECHA</th>
            <th>HORA</th>
            <th>COMPROBANTE / REFERENCIA PAGO</th>
            <th>ESTADO</th>
         </tr>';

        // Escribir datos organizados
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . mb_strtoupper($row['student_name'], 'UTF-8') . '</td>';
            echo '<td>' . mb_strtoupper($row['student_contact'], 'UTF-8') . '</td>';
            echo '<td>' . mb_strtoupper($row['subject'], 'UTF-8') . '</td>';
            echo '<td>' . mb_strtoupper($row['other_subject'] ?: 'N/A', 'UTF-8') . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($row['date'])) . '</td>';
            echo '<td>' . date('h:i A', strtotime($row['time'])) . '</td>';
            echo '<td>' . str_replace(["\r\n", "\n", "\r"], '', $row['proof_details'] ?: 'Sin detalles de pago') . '</td>';
            echo '<td>' . strtoupper($row['status']) . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit();
    }

/**
 * Obtiene una sola cita por ID (útil para el administrador al editar).
 */
function get_appointment_by_id($id, $pdo)
{
    global $error_message;
    if (!$pdo) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Convertir strings de fecha a objetos DateTime
        if ($appointment) {
            if (isset($appointment['created_at']) && is_string($appointment['created_at'])) {
                $appointment['created_at'] = new DateTime($appointment['created_at']);
            }
            if (isset($appointment['expires_at']) && is_string($appointment['expires_at']) && $appointment['expires_at']) {
                $appointment['expires_at'] = new DateTime($appointment['expires_at']);
            }
        }
        return $appointment;
    } catch (PDOException $e) {
        $error_message = "Error al obtener Asesoria por ID: " . $e->getMessage();
        return null;
    }
}


/**
 * Obtiene todas las citas para el panel de administración.
 */
function get_all_appointments($pdo)
{
    global $error_message;
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM appointments ORDER BY created_at DESC");
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convertir strings de fecha a objetos DateTime
        foreach ($appointments as &$app) {
            // Asegurarse de que el campo existe y es un string antes de intentar convertir
            if (isset($app['created_at']) && is_string($app['created_at'])) {
                $app['created_at'] = new DateTime($app['created_at']);
            }
            if (isset($app['expires_at']) && is_string($app['expires_at']) && $app['expires_at']) {
                $app['expires_at'] = new DateTime($app['expires_at']);
            }
        }

        return $appointments;
    } catch (PDOException $e) {
        $error_message = "Error al obtener Asesorias para Admin: " . $e->getMessage();
        return [];
    }
}


/**
 * Agenda una nueva cita. (Create - C)
 */
function schedule_appointment($details, $student_id, $pdo)
{
    global $error_message;
    if (!$pdo) return false;

    try {
        $now = new DateTime();

        // Dentro de la función schedule_appointment en tu archivo PHP:
        $expires_at = (new DateTime())->modify('+30 minutes'); // Cambiado a 30 minutos

        // Antes de agendar, verificar si ya tiene una cita activa 
        if (get_current_appointment($student_id, $pdo)) {
            $error_message = "Ya tiene una Asesoria pendiente de pago o validación. Por favor, cancélela primero para agendar una nueva.";
            return false;
        }


        $stmt = $pdo->prepare("
            INSERT INTO appointments (student_session_id, subject, other_subject, date, time, student_name, student_contact, status, created_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING_PAYMENT', ?, ?)
        ");
        $success = $stmt->execute([
            $student_id,
            $details['subject'],
            $details['other_subject'] ?? null,
            $details['date'],
            $details['time'],
            $details['student_name'],
            $details['student_contact'],
            $now->format('Y-m-d H:i:s'),
            $expires_at->format('Y-m-d H:i:s')
        ]);

        if ($success) {
            // Devolver el ID de la nueva cita
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        $error_message = "Error al agendar Asesoria: " . $e->getMessage();
        return false;
    }
}

/**
 * Actualiza el estado o detalles de comprobante/detalle de una cita. (Update - U)
 * Ahora permite actualizar múltiples campos, incluyendo los detalles de la cita.
 */
function update_appointment($id, $updates, $pdo)
{
    global $error_message;
    if (!$pdo) return false;

    $set_clauses = [];
    $execute_params = [];

    // Lista segura de campos actualizables (incluye los campos de edición de detalles)
    $allowed_updates = ['status', 'proof_details', 'expires_at', 'subject', 'other_subject', 'date', 'time', 'student_name', 'student_contact'];

    foreach ($updates as $key => $value) {
        if (in_array($key, $allowed_updates)) {
            $set_clauses[] = "$key = ?";
            // Usar NULL explícito para PDO si el valor es null
            $execute_params[] = $value;
        }
    }

    if (empty($set_clauses)) return false;

    $execute_params[] = $id;

    try {
        $sql = "UPDATE appointments SET " . implode(', ', $set_clauses) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($execute_params);
    } catch (PDOException $e) {
        $error_message = "Error al actualizar Asesoria: " . $e->getMessage();
        return false;
    }
}

/**
 * Elimina permanentemente una cita de la base de datos. (Delete - D)
 */
function delete_appointment($id, $pdo)
{
    global $error_message;
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        $error_message = "Error al eliminar Asesoria: " . $e->getMessage();
        return false;
    }
}

// --- 5. Lógica de Enrutamiento y Acciones ---

// Acción de Login de Administrador


// Verificamos si existe el usuario y si la contraseña coincide
// NOTA: Si usas password_hash en el registro, aquí usa password_verify($password, $user['password'])
// Procesar Login de Administrador
if (isset($_POST['action']) && $_POST['action'] === 'admin_login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($db_connected) {
        try {
            // Buscamos el usuario por su email
            $stmt = $pdo->prepare("SELECT * FROM administradores WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificamos si existe el usuario y si la contraseña coincide
            //if ($user && $password === $user['password']) {
            // Seteamos las variables de sesión

            /*PARA DEBUGUEAR 
                if($user) { 
                    var_dump($user);
                    var_dump($password);
                    var_dump(password_verify($password, $user['password']));
                    exit;
                }
                */
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_nombre'] = $user['nombre'];
                $_SESSION['admin_rol'] = $user['rol'];

                $is_admin = true;
                // Redirigir al dashboard
                header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . "?view={$VIEWS['ADMIN_DASHBOARD']}");
                exit;
            } else {
                $error_message = "❌ Credenciales incorrectas. Acceso denegado.";
            }
        } catch (PDOException $e) {
            $error_message = "Error en la base de datos: " . $e->getMessage();
        }
    }
}


// Acción de Logout de Administrador
if (isset($_GET['action']) && $_GET['action'] === 'admin_logout') {
    $_SESSION['is_admin'] = false;
    unset($_SESSION['is_admin']);
    $is_admin = false;
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?')); // Redirigir al inicio (schedule view)
    exit;
}

// Acción de Agendar Cita (Estudiante)
if (isset($_POST['action']) && $_POST['action'] === 'schedule' && $db_connected) {
    $details = [
        'subject' => $_POST['subject'] ?? '',
        'other_subject' => $_POST['other_subject'] ?? null,
        'date' => $_POST['date'] ?? '',
        'time' => $_POST['time'] ?? '',
        'student_name' => $_POST['student_name'] ?? '',
        'student_contact' => $_POST['student_contact'] ?? '',
    ];

    // NOTA: La validación de $ASSIGNATURES ya no es estrictamente necesaria aquí si el campo es pre-rellenado
    // Pero mantenemos la validación de campos vacíos.

    if (empty($details['subject']) || empty($details['date']) || empty($details['time']) || empty($details['student_name']) || empty($details['student_contact'])) {
        $error_message = "Por favor, complete todos los campos obligatorios.";
    } else {
        // Validación extra para "Otro tipo de asesorías"
        if ($details['subject'] === 'Otro tipo de asesorías' && empty($details['other_subject'])) {
            $error_message = "Debe especificar el tipo de asesoría en el campo 'Otro'.";
        } else {
            $new_id = schedule_appointment($details, $student_session_id, $pdo);
            if ($new_id) {
                // Si la cita se agenda con éxito, redirigimos a la vista de pago
                header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . "?view={$VIEWS['PAYMENT_VIEW']}");
                exit;
            }
        }
    }
}

// Acción de Subir Comprobante (Estudiante)
if (isset($_POST['action']) && $_POST['action'] === 'upload_proof' && $db_connected) {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    $proof_details = $_POST['proof_details'] ?? '';

    if (empty($proof_details)) {
        $error_message = "Debe ingresar detalles del comprobante.";
    } elseif ($appointment_id > 0) {
        $updates = [
            'status' => 'PENDING_VALIDATION',
            'proof_details' => $proof_details,
            // Eliminamos la fecha de expiración ya que el pago fue intentado
            'expires_at' => null // Usar NULL en PDO para campos DATETIME
        ];
        if (!update_appointment($appointment_id, $updates, $pdo)) {
            $error_message = "Fallo al actualizar el comprobante.";
        } else {
            // Recargar la página para mostrar el nuevo estado
            header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . "?view={$VIEWS['PAYMENT_VIEW']}");
            exit;
        }
    }
}
// Busca esta línea (aprox 235) y verifica que diga:
if (isset($_POST['action']) && $_POST['action'] === 'admin_edit' && $is_admin && $db_connected) {
    // ... tu código ...
}

// Busca esta línea (aprox 266) y verifica que diga:
if (isset($_POST['action']) && $_POST['action'] === 'admin_delete' && $is_admin && $db_connected) {
    // ... tu código ...
}

// Busca esta línea (aprox 292) y verifica que diga:
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && $is_admin && $db_connected) {
    // ... tu código ...
}
// ACCIÓN NUEVA: Edición de Cita (Administrador)
if (isset($_POST['action']) && $_POST['action'] === 'admin_edit' && $is_admin && $db_connected) {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    $filter_to_return = $_POST['filter'] ?? 'ALL'; // Para mantener el filtro después de la acción
    $details = [
        'subject' => $_POST['subject'] ?? '',
        'other_subject' => $_POST['other_subject'] ?? null,
        'date' => $_POST['date'] ?? '',
        'time' => $_POST['time'] ?? '',
        'student_name' => $_POST['student_name'] ?? '',
        'student_contact' => $_POST['student_contact'] ?? '',
    ];

    if ($appointment_id > 0 && !empty($details['subject']) && !empty($details['date'])) {
        // Validación extra para "Otro tipo de asesorías"
        if ($details['subject'] !== 'Otro tipo de asesorías') {
            $details['other_subject'] = null; // Limpiar si no aplica
        } elseif (empty($details['other_subject'])) {
            $error_message = "Debe especificar el tipo de asesoría en el campo 'Otro' al editar.";
            goto end_edit;
        }

        if (!update_appointment($appointment_id, $details, $pdo)) {
            $error_message = "Fallo al actualizar la Asesoria ID: $appointment_id.";
        } else {
            // Éxito: Redirigir al dashboard
            header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . "?view={$VIEWS['ADMIN_DASHBOARD']}&filter={$filter_to_return}&msg=edited");
            exit;
        }
    } else {
        $error_message = "Faltan datos obligatorios para editar la Asesoria.";
    }
    end_edit: // Etiqueta para manejar la redirección/continuación en caso de error
}


// ACCIÓN NUEVA: Eliminación de Cita (Administrador - Hard Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_delete') {
    $appointment_id = $_POST['appointment_id'];

    // 1. Borramos la cita seleccionada
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");

    if ($stmt->execute([$appointment_id])) {
        // 2. REORDENAR TODOS LOS IDs (Para que no queden huecos)
        // Esto pone una variable en 0 y la va sumando fila por fila
        $pdo->query("SET @count = 0;");
        $pdo->query("UPDATE appointments SET id = (@count := @count + 1);");

        // 3. RESETEAR EL AUTO_INCREMENT
        // Para que la siguiente cita nueva use el número que sigue al último
        $pdo->query("ALTER TABLE appointments AUTO_INCREMENT = 1;");

        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=" . ($_GET['filter'] ?? 'ALL') . "&msg=deleted");
        exit();
    }
}


// ACCIÓN: Cancelación por parte del estudiante
if (isset($_POST['action']) && $_POST['action'] === 'student_cancel' && $db_connected) {
    $appointment_id = $_POST['appointment_id'] ?? 0;

    // Solo permitir cancelar si tienen el ID de la cita
    if ($appointment_id > 0) {
        $updates = [
            'status' => 'CANCELLED',
            'expires_at' => null
        ];

        // En un entorno real, se debería verificar que student_session_id coincide con la cita
        if (!update_appointment($appointment_id, $updates, $pdo)) {
            $error_message = "Fallo al cancelar la cita.";
        } else {
            // Redirigir al inicio para que puedan agendar una nueva
            header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
    }
}


// Acción de Actualizar Estado (Admin) - Usado para Aprobar Pago o Cancelación.
if (isset($_POST['action']) && $_POST['action'] === 'update_status' && $is_admin && $db_connected) {
    $appointment_id = $_POST['appointment_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';
    $filter_to_return = $_POST['filter'] ?? 'ALL'; // Para mantener el filtro después de la acción

    if ($appointment_id > 0 && in_array($new_status, ['PAID', 'CANCELLED'])) {
        $updates = ['status' => $new_status];

        // Si se confirma o cancela, eliminamos la fecha de expiración
        $updates['expires_at'] = null; // Usar NULL en PDO para campos DATETIME

        // También quitamos el comprobante si se cancela, aunque no es estrictamente necesario en este demo
        if ($new_status === 'CANCELLED') {
            $updates['proof_details'] = null;
        }

        if (!update_appointment($appointment_id, $updates, $pdo)) {
            $error_message = "Fallo al actualizar el estado.";
        } else {
            // Redirigir al dashboard para ver el cambio
            header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?') . "?view={$VIEWS['ADMIN_DASHBOARD']}&filter={$filter_to_return}");
            exit;
        }
    }
}


// --- 6. Determinación de la Vista a Renderizar ---

$view_param = $_GET['view'] ?? $VIEWS['SCHEDULE_VIEW'];

if ($is_admin) {
    $current_view = $VIEWS['ADMIN_DASHBOARD'];
} else {
    // Buscar si el estudiante tiene una cita activa
    $current_appointment = get_current_appointment($student_session_id, $pdo);

    if ($current_appointment && $view_param !== $VIEWS['ADMIN_LOGIN']) {
        // Si hay cita activa, forzar a la vista de pago (excepto para login administrativo)
        $current_view = $VIEWS['PAYMENT_VIEW'];
    } elseif ($view_param === $VIEWS['ADMIN_LOGIN']) {
        $current_view = $VIEWS['ADMIN_LOGIN'];
    } else {
        $current_view = $VIEWS['SCHEDULE_VIEW'];
    }
}


// --- 7. Datos para el Slider Show ---
// 1. Iniciamos el arreglo con las imágenes predeterminadas que están en /assets/
$SLIDER_IMAGES = [
    [
        'url' => '../assets/r4.jpg',
        'title' => 'Domina las Matemáticas y las Ciencias',
        'caption' => 'Refuerza Matemáticas, Física y Química con docentes expertos y acompañamiento personalizado.',
        'cta' => 'Agenda tu asesoría',
        'color' => 'bg-gradient-to-r from-indigo-600 to-indigo-500'
    ],
    [
        'url' => '../assets/r2.jpg',
        'title' => 'Prepárate para tu examen con confianza',
        'caption' => 'Te ayudamos a comprender, practicar y aprobar con éxito ese examen tan importante.',
        'cta' => 'Comienza ahora',
        'color' => 'bg-gradient-to-r from-emerald-600 to-green-500'
    ],
    [
        'url' => '../assets/r1.jpg',
        'title' => 'Mejora tu Lectura e Inglés',
        'caption' => 'Fortalece tu comprensión lectora e inglés de forma práctica, clara y efectiva.',
        'cta' => 'Quiero mejorar',
        'color' => 'bg-gradient-to-r from-rose-600 to-red-500'
    ]
];

// 2. Si la base de datos está conectada, le sumamos las imágenes dinámicas
if ($db_connected) {
    try {
        $stmt_slides = $pdo->query("SELECT * FROM slider_content ORDER BY id DESC");
        while ($row = $stmt_slides->fetch(PDO::FETCH_ASSOC)) {
            // Agregamos cada fila de la BD al arreglo existente
            $SLIDER_IMAGES[] = [
                'url'     => '../' . $row['image_path'],
                'title'   => $row['title'],
                'caption' => $row['title'],
                'cta'     => 'Agenda tu asesoría',
                'color'   => 'bg-gradient-to-r from-indigo-600 to-indigo-500'
            ];
        }
    } catch (PDOException $e) {
        // Si falla la BD, el slider simplemente se queda con las 3 originales
    }
}

// --- 8. Función para Renderizar el Slider Show ---
/**
 * Renders the HTML markup for the testimonial slider show.
 */
function render_slider_show($images)
{
    if (empty($images)) return;
?>
    <div id="slider-container" class="max-w-4xl mx-auto relative overflow-hidden rounded-xl shadow-2xl">
        <div id="slider-track" class="flex transition-transform duration-500 ease-in-out">
            <?php foreach ($images as $index => $image): ?>
                <div class="slider-item flex-shrink-0 w-full aspect-video md:aspect-[21/9] relative" data-index="<?php echo $index; ?>">

                    <img src="<?php echo htmlspecialchars($image['url']); ?>"
                        alt="<?php echo htmlspecialchars($image['caption']); ?>"
                        class="absolute inset-0 w-full h-full object-cover opacity-70">

                    <div class="absolute inset-0 <?php echo htmlspecialchars($image['color']); ?> opacity-70"></div>

                    <div class="relative p-6 md:p-12 h-full flex flex-col justify-center items-center text-center">
                        <h3 class="text-xl md:text-3xl font-extrabold text-white drop-shadow-lg leading-tight">
                            <?php echo htmlspecialchars($image['caption']); ?>
                        </h3>
                        <p class="mt-2 text-sm md:text-lg text-gray-200 font-medium">¡Agenda tu sesión ahora!</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Flechas de navegación -->
        <button onclick="changeSlide(-1)" class="absolute top-1/2 left-4 transform -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white p-3 rounded-full z-10 transition duration-300 hidden md:block" aria-label="Anterior">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>
        <button onclick="changeSlide(1)" class="absolute top-1/2 right-4 transform -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white p-3 rounded-full z-10 transition duration-300 hidden md:block" aria-label="Siguiente">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>

        <!-- Puntos de navegación -->
        <div class="absolute bottom-4 left-0 right-0 flex justify-center space-x-2">
            <?php for ($i = 0; $i < count($images); $i++): ?>
                <button onclick="goToSlide(<?php echo $i; ?>)" class="dot w-3 h-3 rounded-full bg-white bg-opacity-50 hover:bg-opacity-100 transition duration-300" data-slide-index="<?php echo $i; ?>"></button>
            <?php endfor; ?>
        </div>
    </div>
<?php
}


// --- 9. Función para Renderizar las Tarjetas de Asignaturas ---
/**
 * Renders the interactive subject cards.
 */
function render_subject_cards($cards)
{
?>
    <!--
    <style>
        /* Contenedor con perspectiva para efecto 3D */
        /* XANTY: ESTILOS DE LN 817 A 941 NO SIRVEN PARA NADA */
        .grid-cards {
            perspective: 1000px;
        }

        /* Estilo base de la tarjeta: Efecto Cristal 3D */
        .card-3d-vibrant {
            position: relative;
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 150px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            text-decoration: none !important;
        }

        /* Brillo interno superior para simular luz */
        .card-3d-vibrant::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 100%);
            pointer-events: none;
        }


        /* Mapeo de Colores Vibrantes (Se activa según la clase border- que ya tienes) */
        .vibrant-blue {
            background: linear-gradient(135deg, #00c6ff, #44f54dff);
            border-bottom: 6px solid #38f00aff;
        }

        .vibrant-purple {
            background: linear-gradient(135deg, #834d9b, #d04ed6);
            border-bottom: 6px solid #8e24aa;
        }

        .vibrant-pink {
            background: linear-gradient(135deg, #ff0080, #ff8c00);
            border-bottom: 6px solid #f30aa5ff;
        }

        .vibrant-green {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            border-bottom: 6px solid #1b5e20;
        }

        .vibrant-orange {
            background: linear-gradient(135deg, #f2994a, #f2c94c);
            border-bottom: 6px solid #e65100;
        }

        .vibrant-red {
            background: linear-gradient(135deg, #eb3349, #f45c43);
            border-bottom: 6px solid #ccee0aff;
        }

        /* Comportamiento en Android al tocar (Feedback táctil) */
        .card-3d-vibrant:active {
            transform: scale(0.92) translateY(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            filter: brightness(1.2);
        }

        /* Iconos flotantes con sombra */
        .icon-container {
            font-size: 6rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
    </style>
    <style>
        /* ... (tus otros estilos se mantienen igual) ... */

        .card-3d-vibrant {
            position: relative;
            border-radius: 20px;
            padding: 15px;
            /* Reduje un poco el padding para dar más espacio al texto */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 160px;
            /* Aumenté un poco el alto mínimo para letras grandes */
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            text-decoration: none !important;

            /* --- AJUSTES DE TEXTO --- */
            color: white;
            text-align: center;
            /* Tamaño extra grande */
            font-size: 4.6rem !important;
            font-weight: 1000 !important;
            text-transform: uppercase;
            line-height: 1;
            /* Para que no haya mucho espacio entre líneas si el texto baja */
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.4);
            /* Sombra más fuerte para que resalte */
        }

        /* Ocultar iconos por completo */
        .icon-container,
        .card-3d-vibrant svg {
            display: none !important;
        }

        /* Ajuste especial para textos largos (ej. Ciencias Sociales) */
        .card-3d-vibrant span {
            word-wrap: break-word;
            max-width: 200%;
            display: block;
        } 
    </style>
    -->

    <style>
        /* Contenedor Grid optimizado para Kiosco */
        * {
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #050505;
            color: #f5f5f5;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            padding: 20px;
        }

        /* Tarjeta Base Estilo "Glass-Neon" */
        .kiosk-card {
            position: relative;
            height: 160px;
            border-radius: 2.0rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 3px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        /* EFECTO DE LUZ QUE CRUZA (Para pantallas oscuras) */
        .kiosk-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.4),
                    transparent);
            transition: 0.5s;
            animation: lightSweep 4s infinite;
        }

        @keyframes lightSweep {
            0% {
                left: -100%;
            }

            20% {
                left: 100%;
            }

            100% {
                left: 100%;
            }
        }

        /* Colores de Alto Brillo (Fluorescentes) */
        .glow-indigo {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.5);
            border-color: #60a5fa !important;
        }

        .glow-purple {
            background: linear-gradient(135deg, #6d28d9, #8b5cf6);
            box-shadow: 0 0 25px rgba(139, 92, 246, 0.5);
            border-color: #a78bfa !important;
        }

        .glow-pink {
            background: linear-gradient(135deg, #be185d, #ec4899);
            box-shadow: 0 0 25px rgba(236, 72, 153, 0.5);
            border-color: #f472b6 !important;
        }

        .glow-green {
            background: linear-gradient(135deg, #15803d, #22c55e);
            box-shadow: 0 0 25px rgba(34, 197, 94, 0.5);
            border-color: #4ade80 !important;
        }

        .glow-orange {
            background: linear-gradient(135deg, #c2410c, #f97316);
            box-shadow: 0 0 25px rgba(249, 115, 22, 0.5);
            border-color: #fb923c !important;
        }

        .glow-red {
            background: linear-gradient(135deg, #b91c1c, #ef4444);
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.5);
            border-color: #f87171 !important;
        }

        .glow-yellow {
            background: linear-gradient(135deg, #BBC039, #f4ff28df);
            box-shadow: 0 0 25px rgba(199, 199, 18, 0.5);
            border-color: #f8f9ae !important;
        }

        .glow-cyan {
            background: linear-gradient(135deg, #0890b2f4, #06b6d4);
            box-shadow: 0 0 25px rgba(6, 182, 212, 0.5);
            border-color: #22d3ee !important;
        }

        /* Efecto Hover SOLO para dispositivos con Mouse (PC) */
        @media (hover: hover) {
            .kiosk-card:hover {
                transform: translateY(-10px) scale(1.02);
                filter: brightness(1.15);
                box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7);
                cursor: pointer;
            }
        }

        /* Efecto al tocar (Feedback táctil fuerte) */
        .kiosk-card:active {
            transform: scale(0.9) brightness(1.5);
            box-shadow: 0 0 50px rgba(255, 255, 255, 0.4);
        }

        .card-text {
            font-size: 1.6rem;
            font-weight: 900;
            color: #f2e9e9f7;
            text-transform: uppercase;
            text-align: center;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.7);
            z-index: 10;
            padding: 0 1rem;
            line-height: 1;
        }

        /* Estilo para los iconos SVG */
        .kiosk-card svg {
            width: 48px;
            height: 48px;
            margin-bottom: 5px;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }
    </style>


    <div class="subjects-grid">
        <?php foreach ($cards as $card):
            $color = strtolower($card['color']);
            // Mapeo dinámico de colores de alto contraste
            $glow_class = 'glow-indigo';
            if (strpos($color, 'purple') !== false) $glow_class = 'glow-purple';
            elseif (strpos($color, 'pink') !== false) $glow_class = 'glow-pink';
            elseif (strpos($color, 'green') !== false) $glow_class = 'glow-green';
            elseif (strpos($color, 'orange') !== false || strpos($color, 'glow-yellow') !== false) $glow_class = 'glow-orange';
            elseif (strpos($color, 'red') !== false) $glow_class = 'glow-red';
            elseif (strpos($color, 'yellow') !== false) $glow_class = 'glow-yellow';
            elseif (strpos($color, 'cyan') !== false) $glow_class = 'glow-cyan';
        ?>
            <div class="kiosk-card <?php echo $glow_class; ?>"
                onclick="selectSubject('<?php echo addslashes($card['subject']); ?>')">

                <div class="absolute top-3 right-5 w-8 h-8 bg-white/20 rounded-full blur-lg"></div>

                <?php echo $card['icon']; ?>

                <span class="card-text dark:text-gray-100">
                    <?php echo htmlspecialchars($card['subject']); ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>


    <div class="max-w-7xl mx-auto mb-10 px-4">




    </div>
    </div>
    </div>
<?php
}

?>
<!DOCTYPE html>
<html lang="es">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />


<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title> Kiosco Los Profe’s</title>
    <!-- Incluir Tailwind CSS CDN para estilos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            margin: 0;
            color: #1a202c;
        }

        /* Cabecera Creativa Curvada */
        .header-wave {
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.9), rgba(59, 130, 246, 0.8)), url('../assets/barnner.png');
            background-size: cover;
            background-position: center;
            height: 280px;
            width: 100%;
            border-radius: 0 0 80px 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        /* Contenedor Flotante */
        .main-container {
            max-width: 1000px;
            margin: -60px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 40px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
        }

        /* Rejilla Creativa Asimétrica */
        .grid-materias {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        /* Tarjetas Tipo "Pill" Modernas */
        .subject-card {
            background: #ffffff;
            height: 160px;
            border-radius: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            box-shadow: 8px 8px 20px #d1d9e6, -8px -8px 20px #ffffff;
            /* Efecto Neumórfico suave */
        }

        .subject-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: currentColor;
            /* Usa el color de la clase PHP */
        }

        .subject-card:active {
            transform: scale(0.92);
            box-shadow: inset 4px 4px 10px #d1d9e6, inset -4px -4px 10px #ffffff;
        }

        /* Textos con personalidad */
        .title-main {
            font-weight: 800;
            font-size: 2.5rem;
            color: white;
            text-align: center;
            margin: 0;
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .card-label {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2d3748;
            padding: 0 15px;
            text-align: center;
        }
    </style>
    <style>
        /* 1. RESET Y BASE: Vital para que Android no cree scrolls raros */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-user-select: none;
            user-select: none;
        }

        .texto-combinado {
            color: #ffffff !important;
            /* Sombra estratégica: genera contraste en bordes de letras */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5),
                0px 0px 10px rgba(0, 0, 0, 0.2);
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Opcional: Si el texto está sobre una zona muy clara del banner */
        .badge-texto {
            background-color: rgba(0, 0, 0, 0.3);
            /* Fondo oscuro muy suave */
            padding: 5px 15px;
            border-radius: 50px;
            backdrop-filter: blur(4px);
            /* Difumina un poco el banner detrás del texto */
            display: inline-block;
        }

        /* 3. CONTENEDORES (Lógica de "No desborde") */
        .contenido-central {
            width: min(95%, 800px);
            /* Móvil: 95%, Tablet/PC: máximo 800px */
            margin: 0 auto;
            padding: clamp(15px, 5vw, 30px);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            /* Efecto moderno */
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .subject-card {
            border-left: 8px solid;
            /* El color de la materia resaltará a la izquierda */
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .contenido-central {
            animation: slideUp 0.6s ease-out;
        }

        /* 4. TEXTOS (Lógica Fluida) */
        h1,
        h2,
        .text-4xl {
            /* Se ajusta solo: Pequeño en móvil, grande en tablet */
            font-size: clamp(1.4rem, 6vw, 2.8rem) !important;
            text-align: center;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        /* 5. GRID DE MATERIAS (Lógica Adaptable) */
        .grid-subjects {
            display: grid;
            /* Crea columnas automáticamente de mínimo 140px */
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            width: 100%;
        }

        /* 6. CORRECCIÓN DE MEDIA QUERIES (Sin errores de llave) */
        @media (max-width: 480px) {
            .grid-subjects {
                grid-template-columns: repeat(2, 1fr);
                /* 2 columnas en móviles pequeños */
            }

            .status-badge {
                font-size: 0.75rem;
            }
        }

        /* Ajuste para cuando giran la tablet (Landscape) */
        @media (orientation: landscape) and (max-height: 500px) {
            .schedule-bg {
                padding-top: 5px;
            }

            h1 {
                font-size: 1.2rem !important;
            }
        }

        /* 7. UTILIDADES */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }

        /* Hace que los iconos de fecha y hora sean blancos en modo oscuro */
        .dark input::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        img,
        svg {
            max-width: 100%;
            height: auto;
        }
    </style>
    <style>
        /* Efecto de entrada suave del formulario */
        .scale-up-center {
            animation: scale-up-center 0.4s cubic-bezier(0.390, 0.575, 0.565, 1.000) both;
        }

        @keyframes scale-up-center {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Inputs que brillan al enfocarse */
        input:focus {
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* Prevenir selección de texto para modo Kiosco */
        * {
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }
    </style>
    <style>
        /* Contenedor que permite scroll si el teclado de Android ocupa media pantalla */
        .android-modal-scroll {
            max-height: 95dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            display: flex;
            flex-direction: column;
        }

        /* Estilo para los inputs: Evita que el zoom de Android rompa el layout */
        .input-touch {
            width: 100%;
            min-height: 60px;
            /* Tamaño ideal para dedos */
            padding: 1rem 1.25rem;
            background-color: #f3f4f6;
            border: 2px solid transparent;
            border-radius: 1.25rem;
            font-size: 16px !important;
            /* Crucial para evitar auto-zoom en Chrome Android */
            font-weight: 600;
            color: #1f2937;
            transition: all 0.3s ease;
        }

        .input-touch:focus {
            background-color: #ffffff;
            border-color: #4f46e5;
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* Botón optimizado para tablets */
        .btn-touch {
            width: 100%;
            min-height: 70px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: white;
            border-radius: 1.5rem;
            font-size: 1.25rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>

<body class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-500">

    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script>
        if (
            localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)
        ) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Botón de cambiar tema -->
    <div>
        <button id="CambiarModo"
            aria-label="Cambiar entre modo claro y oscuro"
            class="fixed top-4 left-4 p-3 rounded-full bg-gray-200 dark:bg-gray-800 transition duration-300 z-[110] hover:scale-110">

            <!-- Icono modo oscuro -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="#1f2937" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-800 dark:hidden">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>

            <!-- Icono modo claro -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-gray-200 hidden dark:block">
                <path d="M12 2.25a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75ZM7.5 12a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM18.894 6.166a.75.75 0 0 0-1.06-1.06l-1.591 1.59a.75.75 0 1 0 1.06 1.061l1.591-1.59ZM21.75 12a.75.75 0 0 1-.75.75h-2.25a.75.75 0 0 1 0-1.5H21a.75.75 0 0 1 .75.75ZM17.834 18.894a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 1 0-1.061 1.06l1.59 1.591ZM12 18a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-2.25A.75.75 0 0 1 12 18ZM7.758 17.303a.75.75 0 0 0-1.061-1.06l-1.591 1.59a.75.75 0 0 0 1.06 1.061l1.591-1.59ZM6 12a.75.75 0 0 1-.75.75H3a.75.75 0 0 1 0-1.5h2.25A.75.75 0 0 1 6 12ZM6.697 7.757a.75.75 0 0 0 1.06-1.06l-1.59-1.591a.75.75 0 0 0-1.061 1.06l1.59 1.591Z" />
            </svg>
        </button>
    </div>

    <script>
        // Script para el botón de cambio de modo
        document.getElementById("CambiarModo").addEventListener("click", () => {
            const html = document.documentElement;
            const isDark = html.classList.toggle("dark");

            // Guardar preferencia
            localStorage.setItem("theme", isDark ? "dark" : "light");
        });
    </script>

    <?php if (!$is_admin && $current_view !== $VIEWS['ADMIN_LOGIN']): // Botón de acceso Admin flotante 
    ?>
        <style>
            .btn-admin-glow {
                /* Fondo base oscuro con borde de neón */
                background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);
                border: 2px solid rgba(255, 255, 255, 0.3);

                /* Resplandor exterior (Glow) - Crucial para bajo brillo */
                box-shadow: 0 0 15px rgba(124, 58, 237, 0.6),
                    inset 0 0 10px rgba(255, 255, 255, 0.2);
                position: fixed;
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }

            /* Efecto de luz giratoria interna */
            .btn-admin-glow::before {
                content: "";
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: conic-gradient(transparent,
                        rgba(255, 255, 255, 0.3),
                        transparent 30%);
                animation: rotate-glow 4s linear infinite;
            }

            @keyframes rotate-glow {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            /* Feedback táctil para Android */
            .btn-admin-glow:active {
                transform: scale(0.9);
                filter: brightness(1.5);
                box-shadow: 0 0 30px rgba(124, 58, 237, 0.9);
            }

            .icon-admin {
                filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.5));
                z-index: 10;
            }
        </style>

        <a href="?view=<?php echo $VIEWS['ADMIN_LOGIN']; ?>"
            class="btn-admin-glow fixed top-4 right-4 p-3 rounded-full bg-gray-200 dark:bg-gray-800 transition duration-300 z-[110] hover:scale-110"
            aria-label="Acceso Administrador"
            title="Acceso Administrador">

            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-gray-800 dark:text-gray-100 icon-admin">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
            </svg>
        </a>
    <?php elseif ($is_admin): // Botón de Logout Admin flotante 
    ?>
        <a href="?action=admin_logout"
            class="fixed top-4 right-4 md:top-8 md:right-8 bg-red-600 text-white p-3 rounded-full shadow-xl hover:bg-red-700 transition duration-z-50 transform hover:scale-105"
            aria-label="Cerrar Sesión Administrador"
            title="Cerrar Sesión">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </a>
    <?php endif; ?>

    <div class="<?php echo in_array($current_view, [$VIEWS['ADMIN_LOGIN'], $VIEWS['SCHEDULE_VIEW']]) ? '' : 'p-4 md:p-8'; ?>">
        <?php if (isset($error_message) && $error_message): ?>
            <div class="max-w-4xl mx-auto bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                <p class="font-bold">Error del Sistema / Permisos:</p>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <?php

        if ($current_view === $VIEWS['SCHEDULE_VIEW']): ?>
            <style>
                /* OPTIMIZACIÓN BAJO BRILLO Y ANDROID */
                :root {
                    --kiosk-bg: #000000;
                    --kiosk-surface: #0a0a0b;
                    --neon-indigo: #6366f1;
                    --neon-orange: #f97316;
                    --neon-green: #22c55e;
                    --neon-purple: #b917eaf9;
                    --neon-cyan: #06b6d4;
                    --neon-yellow: #fbbf24;
                    --neon-red: #fa2c2c;
                    --neon-pink: #ec4899;
                }

                /* Slider con marco de profundidad */
                .slider-frame {
                    border: 2px solid rgba(255, 255, 255, 0.1);
                    box-shadow: 0 0 40px rgba(0, 0, 0, 0.8), inset 0 0 20px rgba(255, 255, 255, 0.05);
                    border-radius: 3rem;
                    overflow: hidden;
                }

                /* Cajas de materias (Grid) */
                /* Nota: Asegúrate de que render_subject_cards genere elementos con estas clases */
                .subject-card-premium {
                    background: linear-gradient(145deg, #121214, #080809) !important;
                    border: 1px solid rgba(255, 255, 255, 0.08) !important;
                    border-radius: 2.5rem !important;
                    padding: 2rem !important;
                    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .subject-card-premium:active {
                    transform: scale(0.92);
                    border-color: var(--accent-color, #6366f1) !important;
                    box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
                }

                /* Formulario Android 14 */
                .android-sheet {
                    background: #050505 !important;
                    border-top: 2px solid var(--accent-color, #6366f1) !important;
                    box-shadow: 0 -20px 50px rgba(0, 0, 0, 0.9);
                    padding: 14px;
                }

                .kiosk-input-pro {
                    background: #111112 !important;
                    border: 2px solid #222 !important;
                    color: #fff !important;
                    height: 80px !important;
                    font-size: 1.4rem !important;
                    border-radius: 2rem !important;
                    padding: 0 1.5rem !important;
                    width: 100%;
                    outline: none;
                }

                .kiosk-input-pro:focus {
                    border-color: var(--accent-color, #6366f1) !important;
                    background: #161618 !important;
                }

                /* Forzar iconos blancos para fecha/hora */
                input::-webkit-calendar-picker-indicator {
                    filter: invert(1);
                    transform: scale(1.8);
                }
            </style>

            <div class="min-h-screen relative overflow-x-hidden text-white">

                <div class="relative z-10 container mx-auto px-4 py-8">

                    <div class="max-w-4xl mx-auto mb-10 text-center animate-fade-in">
                        <div class="relative inline-block">
                            <div class="absolute inset-0 bg-indigo-500 blur-2xl opacity-20"></div>
                            <img src="../assets/logo.avif" class="relative mx-auto h-24 md:h-32 mb-6 p-2">
                        </div>
                        <h1 class="text-5xl md:text-7xl font-black tracking-tighter uppercase italic text-black dark:text-white">
                            Kiosco <span class="text-yellow-400 drop-shadow-[0_0_10px_rgba(250,204,21,0.3)]">Los Profe’s</span>
                        </h1>
                        <p class="mt-4 text-indigo-400 font-bold uppercase tracking-[0.4em] text-sm">
                            Asesorías Académicas de Alto Nivel
                        </p>
                    </div>

                    <div class="max-w-6xl mx-auto space-y-12">

                        <div class="relative">
                            <?php render_slider_show($SLIDER_IMAGES); ?>
                        </div>

                        <div class="p-10 rounded-[4rem] border border-white/5 shadow-2xl">
                            <div class="flex items-center gap-5 mb-10">
                                <div class="h-14 w-4 bg-indigo-500 rounded-full shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                                <h2 class="md:pl[100px] md:pr[100px] xl:pr[100px] xl:pl-[100px] 2xl:pr-[100px] 2xl:pl-[100px] text-3xl md:text-5xl font-black text-black dark:text-white drop-shadow-[0_4px_8px_rgba(0,0,0,0.5)] mb-10 text-center uppercase tracking-tighter">
                                    ✨ Paso 1: ¡Selecciona la asesoría que necesitas y agenda tu sesión ahora!
                                </h2>
                            </div>

                            <div class="grid-wrapper">
                                <?php render_subject_cards($ASSIGNATURE_CARDS); ?>
                            </div>
                        </div>
                    </div>

                    <div id="schedule_form_container"
                        class="bg-gray-100 dark:bg-gray-900 fixed inset-0 z-[100] hidden flex items-end md:items-center justify-center">

                        <div class="android-sheet w-full max-w-2xl md:rounded-[4rem] rounded-t-[4rem] flex flex-col max-h-[96dvh] animate-slide-up">

                            <div class="w-full flex justify-center py-4" onclick="hideForm()">
                                <div class="w-24 h-2 bg-white/20 rounded-full"></div>
                            </div>

                            <div class="pb-10 overflow-y-auto">
                                <div class="block justify-between items-center mb-7">
                                    <div>
                                        <h2 class="text-4xl font-black text-[var(--accent-color)] p-3 italic" id="selected_subject_title">AGENDAR</h2>
                                        <button type="button" onclick="hideForm()" class="flex items-center gap-2 bg-white/5 border border-white/10 px-4 py-2 rounded-2xl active:scale-95 active:bg-[var(--accent-color)] transition-all group">
                                            <svg class="w-5 h-5 text-[var(--accent-color)] group-active:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                            </svg>
                                            <span class="text-xs font-black text-white uppercase">Regresar</span>
                                        </button>
                                    </div>
                                </div>

                                <style>
                                    /* Ajuste de escala del contenedor principal */
                                    .android-sheet {
                                        max-width: 500px !important;
                                        /* Formulario más estrecho */
                                        margin: 0 auto;
                                    }

                                    /* Inputs más compactos pero con el mismo brillo */
                                    .kiosk-input-sm {
                                        background: #0a0a0a !important;
                                        border: 2px solid #333 !important;
                                        color: #fff !important;
                                        height: 52px !important;
                                        /* Altura reducida de 60px a 52px */
                                        font-size: 1rem !important;
                                        border-radius: 1.2rem !important;
                                        padding: 0 1rem !important;
                                        width: 100%;
                                        outline: none;
                                        box-shadow: inset 0 0 8px rgba(0, 0, 0, 0.8);
                                    }

                                    .kiosk-input-sm:focus {
                                        border-color: var(--accent-color, #6366f1) !important;
                                        box-shadow: 0 0 5px var(--accent-color, #6366f1);
                                    }

                                    /* Botón compacto */
                                    .btn-kiosk-sm {
                                        height: 65px;
                                        /* Reducido de 75px a 65px */
                                        font-size: 1rem;
                                        border-radius: 1.5rem;
                                        background: linear-gradient(135deg, var(--accent-color, #6366f1));
                                        box-shadow: 0 8px 10px -5px var(--accent-color, #6366f1);
                                        border: 1px solid rgba(255, 255, 255, 0.1);
                                    }

                                    /* Badge de materia compacto */
                                    .subject-badge-sm {
                                        background: linear-gradient(to right, rgba(255, 255, 255, 0.1), transparent);
                                        border: 2px solid var(--accent-color, #6366f1);
                                        padding: 1rem;
                                        /* Reducido de 1.2rem */
                                        border-radius: 1.5rem;
                                        text-shadow: 0 0 10px var(--accent-color, #6366f1);
                                        box-shadow: 0 0 5px var(--accent-color, #6366f1);
                                        margin-bottom: 0.5rem;
                                    }
                                </style>

                                <form method="POST" class="space-y-4 max-w-md mx-auto">
                                    <p class="text-[var(--accent-color)] font-bold text-sm tracking-widest uppercase">Completa los datos </p>
                                    <input type="hidden" name="action" value="schedule">
                                    <input type="hidden" id="subject" name="subject">

                                    <div id="subject_display" class="subject-badge-sm text-center text-white font-black text-xl shadow-lg italic">
                                    </div>

                                    <div id="other_subject_container" style="display:none;">
                                        <input type="text" name="other_subject" placeholder="¿Materia específica?" class="kiosk-input-sm">
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="relative">
                                            <label class="text-[10px] font-black text-[var(--accent-color)] absolute -top-2 left-4 bg-black px-1 z-10 uppercase tracking-tighter">Fecha</label>
                                            <input type="date" name="date" class="kiosk-input-sm" required>
                                        </div>
                                        <div class="relative">
                                            <label class="text-[10px] font-black text-[var(--accent-color)] absolute -top-2 left-4 bg-black px-1 z-10 uppercase tracking-tighter">Hora</label>
                                            <input type="time" name="time" class="kiosk-input-sm" required>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <label class="text-[10px] font-black text-[var(--accent-color)] absolute -top-2 left-4 bg-black px-1 z-10 uppercase tracking-tighter">Estudiante</label>
                                        <input type="text" name="student_name" placeholder="Tu nombre..." class="kiosk-input-sm" required>
                                    </div>

                                    <div class="relative">
                                        <label class="text-[10px] font-black text-[var(--accent-color)] absolute -top-2 left-4 bg-black px-1 z-10 uppercase tracking-tighter">WhatsApp</label>
                                        <input type="tel" name="student_contact" placeholder="Número de contacto" class="kiosk-input-sm" required>
                                    </div>

                                    <button type="submit" class="w-full btn-kiosk-sm text-white font-black flex items-center justify-center gap-3 active:scale-95 transition-all">
                                        <span class="tracking-widest">CONFIRMAR AGENDAMIENTO</span>
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                    <div class="h-2 md:hidden"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php
        // ===============================================
        // VISTA: Pago (Estudiante)
        // [CONTENIDO DEL RESTO DE VISTAS NO MODIFICADO PARA BREVEDAD, PERO INCLUIDO EN EL ARCHIVO FINAL]
        // ===============================================
        elseif ($current_view === $VIEWS['PAYMENT_VIEW'] && $current_appointment):
            $status = $current_appointment['status'];
            // Convertimos la fecha de expiración a milisegundos para JavaScript
            $expires_at_timestamp = $current_appointment['expires_at'] ? $current_appointment['expires_at']->getTimestamp() * 1000 : 0;

            $is_payment_pending = $status === 'PENDING_PAYMENT';
            $is_cancellable = in_array($status, ['PENDING_PAYMENT', 'PENDING_VALIDATION']);

            $status_styles = [
                'PENDING_PAYMENT' => [
                    'bg'      => 'bg-amber-500',
                    'text'    => 'text-white',
                    'icon'    => '✨',
                    'message' => '¡Tu éxito te espera! Asegura tu cupo ahora',
                    'light'   => 'bg-amber-50'
                ],
                'PENDING_VALIDATION' => [
                    'bg'      => 'bg-indigo-600',
                    'text'    => 'text-white',
                    'icon'    => '🛡️',
                    'message' => 'Estamos verificando tu pago con prioridad',
                    'light'   => 'bg-indigo-50'
                ],
                'PAID' => [
                    'bg'      => 'bg-emerald-600',
                    'text'    => 'text-white',
                    'icon'    => '🏆',
                    'message' => '¡Todo listo! Tu camino al siguiente nivel comienza aquí',
                    'light'   => 'bg-emerald-50'
                ],
                'CANCELLED' => [
                    'bg'      => 'bg-slate-800',
                    'text'    => 'text-white',
                    'icon'    => '↩️',
                    'message' => 'Sesión liberada. ¡Vuelve cuando estés listo!',
                    'light'   => 'bg-slate-50'
                ],
            ];
            $style = $status_styles[$status] ?? ['bg' => 'bg-slate-500', 'text' => 'text-white', 'icon' => '❓', 'message' => 'Desconocido', 'light' => 'bg-slate-50'];
        ?>
            <div class="min-h-screen py-6 px-4 md:py-12 md:px-8 font-sans antialiased text-slate-900">

                <div class="max-w-6xl mx-auto mb-10">
                    <div class="<?php echo $style['bg']; ?> <?php echo $style['text']; ?> rounded-[2.5rem] p-6 md:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.08)] flex flex-col md:flex-row justify-between items-center gap-8 relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-tr from-black/10 to-transparent opacity-50"></div>
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                        <div class="flex flex-col md:flex-row items-center gap-6 relative z-10 text-center md:text-left">
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-white/20 backdrop-blur-2xl rounded-3xl flex items-center justify-center shrink-0 aspect-square text-5xl shadow-2xl border border-white/30 transform hover:scale-105 transition-transform duration-500">
                                <?php echo $style['icon']; ?>
                            </div>
                            <div>
                                <h2 class="text-3xl md:text-5xl font-black tracking-tight leading-tight mb-2">
                                    <?php echo $style['message']; ?>
                                </h2>
                                <div class="flex items-center justify-center md:justify-start gap-3">
                                    <span class="px-3 py-1 bg-black/10 backdrop-blur-md rounded-full text-[11px] font-bold uppercase tracking-[0.2em] opacity-90">
                                        Ref: #<?php echo $current_appointment['id']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($status === 'PAID' || $status === 'CANCELLED'): ?>
                            <a href="?view=<?php echo $VIEWS['SCHEDULE_VIEW']; ?>" class="bg-white text-center text-black px-7 py-3.5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:shadow-2xl hover:-translate-y-1 active:scale-95 transition-all duration-300 relative z-10 shadow-xl border border-transparent">
                                Agendar nueva Asesoría
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- NUEVO -->
                <?php if ($status === 'PENDING_PAYMENT'): ?>
                    <div id="timeline-container"
                        class="relative before:absolute before:left-0 before:top-0 before:h-full before:w-1 before:bg-emerald-500/20">

                        <!-- PASO 1 -->
                        <div id="paso1" class="ml-8 mb-12 relative">
                            <div class="absolute -left-[46px] top-1 w-8 h-8 bg-emerald-500 rounded-full border-4 border-white dark:border-gray-900 shadow-[0_0_15px_rgba(16,185,129,0.5)] flex items-center justify-center text-[14px] font-black text-white">1</div>

                            <p class="text-emerald-500 dark:text-emerald-400 font-black uppercase text-sm tracking-[0.3em] mb-1">
                                Paso 1
                            </p>

                            <h3 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mb-4 tracking-tighter italic uppercase">
                                Verifica tu asesoría
                            </h3>

                            <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base font-medium max-w-3xl leading-relaxed m-3 transition-colors duration-300">
                                Estás a pocos pasos de asegurar tu asesoría.
                                Verifica que los datos sean correctos y continúa con el proceso de pago y confirmación.
                            </p>

                            <!-- CARD RESUMEN -->
                            <div>
                                <div class="lg:col-span-4 space-y-6 order-2 lg:order-1">
                                    <div class="w-full bg-white/50 dark:bg-gray-800 rounded-[2.5rem] p-8 shadow-[0_4px_20px_rgba(0,0,0,0.02)]">
                                        <div class="flex items-center gap-3 mb-10">
                                            <div class="w-2 h-6 bg-emerald-500 rounded-full"></div>
                                            <h3 class="text-black dark:text-white font-bold text-[14px] uppercase tracking-[0.2em]">
                                                Resumen de tu Asesoria
                                            </h3>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-3 gap-6">
                                            <div class="group p-6 rounded-3xl bg-slate-50 dark:bg-gray-700/50 border border-slate-300 dark:border-gray-600">
                                                <div class="flex mx-auto w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-xl group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors duration-300">👤</div>
                                                <div>
                                                    <p class="text-center text-black dark:text-white text-[10px] font-bold uppercase tracking-widest mb-0.5">Estudiante</p>
                                                    <p class="text-center font-extrabold text-black dark:text-white tracking-tight leading-tight"><?php echo htmlspecialchars($current_appointment['student_name']); ?></p>
                                                </div>
                                            </div>

                                            <div class="group p-6 rounded-3xl bg-slate-50 dark:bg-gray-700/50 border border-slate-300 dark:border-gray-600">
                                                <div class="flex mx-auto w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-xl group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors duration-300">📚</div>
                                                <div>
                                                    <p class="text-center text-black dark:text-white text-[10px] font-bold uppercase tracking-widest mb-0.5">Asignatura</p>
                                                    <p class="text-center font-extrabold text-black dark:text-white tracking-tight leading-tight">
                                                        <?php echo htmlspecialchars($current_appointment['subject']); ?>
                                                        <?php if ($current_appointment['other_subject']): ?>
                                                            <span class="block text-black dark:text-slice-100 text-xs font-semibold mt-1">(<?php echo htmlspecialchars($current_appointment['other_subject']); ?>)</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="group p-6 rounded-3xl bg-slate-50 dark:bg-gray-700/50 border border-slate-300 dark:border-gray-600">
                                                <div class="flex mx-auto w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-xl group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors duration-300">📅</div>
                                                <div>
                                                    <p class="text-center text-black dark:text-white text-[10px] font-bold uppercase tracking-widest mb-0.5">Fecha programada</p>
                                                    <p class="text-center font-extrabold text-black dark:text-white tracking-tight"><?php echo htmlspecialchars($current_appointment['date']); ?></p>
                                                    <p class="text-center text-emerald-500 dark:text-emerald-300 font-bold text-xs mt-0.5"><?php echo htmlspecialchars($current_appointment['time']); ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($is_cancellable): ?>
                                            <form method="POST" class="mt-12 pt-8 border-t border-black dark:border-white" onsubmit="return confirm('¿Seguro que desea cancelar su cupo?');">
                                                <input type="hidden" name="action" value="student_cancel">
                                                <input type="hidden" name="appointment_id" value="<?php echo $current_appointment['id']; ?>">
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 text-slate-300 hover:text-red-500 font-bold text-[10px] uppercase tracking-[0.2em] transition-all">
                                                    <span class="text-lg">×</span> Cancelar mi reservación
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- CIERRE DE CONTENEDORES DEL PASO 1 -->

                        <!-- PASO 2 -->
                        <div id="paso2" class="ml-8 mb-12 relative">
                            <div class="absolute -left-[46px] top-1 w-8 h-8 bg-emerald-500 rounded-full border-4 border-white dark:border-gray-900 shadow-[0_0_15px_rgba(16,185,129,0.5)] flex items-center justify-center text-[14px] font-black text-white">2</div>

                            <p class="text-emerald-500 dark:text-emerald-400 font-black uppercase text-sm tracking-[0.3em]">
                                Paso 2
                            </p>

                            <h3 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tighter italic uppercase">
                                Realiza el pago
                            </h3>

                            <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base font-medium max-w-3xl leading-relaxed m-3 transition-colors duration-300">
                                Realiza el pago desde un Corresponsal Bancolombia o desde tu
                                cuenta Nequi al número <b>3166692913</b>
                            </p>

                            <div class="lg:col-span-8 space-y-8 order-1">
                                <?php if ($is_payment_pending): ?>
                                    <!-- AVISO -->
                                    <div class="w-full">
                                        <div class="w-full bg-white/50 dark:bg-gray-800 rounded-[2.5rem] p-8 md:p-10 border border-emerald-100 dark:border-none flex flex-col md:flex-row items-center justify-between gap-8 group hover:bg-emerald-100 dark:hover:bg-gray-700 transition-all duration-500 shadow-sm">

                                            <div class="flex flex-row items-center gap-6 md:gap-12 flex-1">
                                                <div class="flex-shrink-0 w-16 h-16 md:w-28 md:h-28 bg-white rounded-[2.2rem] flex items-center justify-center text-3xl md:text-6xl shadow-sm border border-emerald-200 group-hover:rotate-12 transition-transform duration-500">
                                                    📸
                                                </div>

                                                <div class="text-left flex-1">
                                                    <h4 class="font-black text-black dark:text-white text-xl md:text-3xl tracking-tighter leading-none uppercase">
                                                        ¿Vas a realizar tu pago desde un corresponsal Bancolombia?
                                                    </h4>
                                                    <p class="text-emerald-900 dark:text-emerald-400 text-sm md:text-lg uppercase mt-3 leading-tight max-w-[90%]">
                                                        Una vez realizado el pago, envíanos la foto del comprobante a
                                                        nuestro WhatsApp <b>3164876650</b> o haz clic en el botón de WhatsApp
                                                        desde este dispositivo para enviarlo directamente.
                                                        Recuerda adjuntar tus datos para validar correctamente tu asesoría.
                                                        Recuerde que solo tiene 30 minutos para completar este proceso.
                                                        Si el tiempo expira, deberás agendar nuevamente tu asesoría.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
                                        <!-- CARD NEQUI -->
                                        <div class="relative min-h-[320px] flex flex-col justify-between p-10 sm:p-10 rounded-[2.5rem] md:rounded-[3.5rem] overflow-hidden group bg-gradient-to-br from-[#F6FDFA] via-[#CDF3E5] dark:bg-gradient-to-br dark:from-[#062c1d] dark:via-[#02110b] dark:to-black border border-emerald-100 dark:border-emerald-500/20 shadow-[0_32px_64px_-15px_rgba(0,0,0,0.05)] dark:shadow-[0_32px_64px_-15px_rgba(0,0,0,0.6)] transition-all duration-500 hover:border-emerald-400/40">
                                            <div class="absolute -top-20 -right-20 w-80 h-80 bg-emerald-50 dark:bg-emerald-600/20 rounded-full blur-[100px] group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/30 transition-colors duration-700"></div>
                                            <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-green-50 dark:bg-green-400/10 rounded-full blur-[80px]"></div>

                                            <div class="relative z-10 flex justify-between items-start">
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-2.5 h-2.5 bg-emerald-400 rounded-full animate-pulse shadow-[0_0_10px_rgba(52,211,153,0.8)]"></div>
                                                        <h3 class="text-3xl font-black tracking-tighter bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-500 dark:from-white dark:via-emerald-100 dark:to-emerald-500/50 bg-clip-text text-transparent">Nequi</h3>
                                                    </div>
                                                    <p class="text-[10px] text-emerald-600/70 dark:text-emerald-400/60 font-black uppercase tracking-[0.3em] pl-5">
                                                        Realiza tu pago de Inmediato
                                                    </p>
                                                </div>

                                                <div class="relative w-14 h-11 bg-gradient-to-br from-emerald-200/20 to-emerald-600/20 rounded-xl border border-emerald-400/30 flex items-center justify-center overflow-hidden shadow-inner">
                                                    <div class="absolute inset-0 grid grid-cols-2 gap-px opacity-30">
                                                        <div class="border-r border-b border-emerald-400/50"></div>
                                                        <div class="border-b border-emerald-400/50"></div>
                                                        <div class="border-r border-emerald-400/50"></div>
                                                        <div></div>
                                                    </div>
                                                    <div class="w-8 h-6 bg-emerald-400/10 rounded-md border border-emerald-400/40 shadow-2xl"></div>
                                                </div>
                                            </div>

                                            <div class="relative z-10">
                                                <div class="flex flex-col gap-1">

                                                    <div class="flex items-center gap-4 group/number cursor-pointer">
                                                        <p class="text-3xl md:text-5xl font-mono font-medium tracking-[0.15em] text-slate-800 dark:text-white/90 group-hover:text-emerald-700 dark:group-hover:text-emerald-300 transition-colors">
                                                            316 <span class="text-emerald-600 dark:text-emerald-400 font-black drop-shadow-[0_0_15px_rgba(52,211,153,0.1)] dark:drop-shadow-[0_0_15px_rgba(52,211,153,0.3)]">669</span> 2913
                                                        </p>
                                                        <div
                                                            class="opacity-0 group-hover/number:opacity-100 transition-all transform translate-x-[-10px] group-hover:translate-x-0 bg-emerald-900 dark:bg-emerald-900 p-2 rounded-full border border-emerald-500/30"
                                                            onclick="event.stopPropagation(); navigator.clipboard.writeText('3166692913'); alert('¡Celular Copiado!')">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-200 hover:text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="relative z-10 pt-8 border-t border-emerald-500/10 flex justify-between items-end">
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2 mb-1">

                                                    </div>
                                                    <p class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-widest pl-0.5">
                                                        Total a Pagar por tu Asesoría
                                                    </p>
                                                    <div class="flex items-baseline gap-2">
                                                        <span class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tighter drop-shadow-md">
                                                            $30.000
                                                        </span>
                                                        <span class="text-sm font-bold text-emerald-400 italic">
                                                            PESOS
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="flex flex-col items-end gap-2">
                                                    <div class="p-2.5 bg-emerald-500/10 backdrop-blur-xl border border-emerald-500/30 rounded-[1.2rem] flex items-center gap-3 shadow-2xl">
                                                        <span class="relative flex h-2.5 w-2.5">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                                                        </span>
                                                        <span class="text-emerald-700 dark:text-emerald-100 text-[10px] font-black uppercase tracking-widest">Sesión 1H</span>
                                                    </div>
                                                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-black italic pr-2 uppercase tracking-tighter">
                                                        LOS PROFES
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="absolute inset-0 opacity-[0.04] pointer-events-none bg-[url('https://grainy-gradients.vercel.app/noise.svg')] contrast-150"></div>
                                        </div>

                                        <!-- LÍMITE DE PAGO -->
                                        <div id="timer-container" class="bg-white/50 dark:bg-gray-800 rounded-[2.5rem] p-6 md:p-8 shadow-xl dark:shadow-2xl relative overflow-hidden border border-slate-100 dark:border-white/5 flex flex-col justify-center min-h-[320px]">
                                            <div class="relative z-10">
                                                <p class="text-dark dark:text-white font-bold text-[10px] uppercase tracking-[0.3em] mb-6 text-center">Tiempo límite de pago</p>
                                                <div id="timer-display" class="text-5xl md:text-6xl font-black text-slate-900 dark:text-white tracking-tighter text-center tabular-nums leading-none mb-6 dark:drop-shadow-2xl">--:--</div>
                                                <div class="flex items-center justify-center gap-3 bg-slate-200 dark:bg-white/5 py-3 px-6 rounded-2xl border border-slate-100 dark:border-white/10">
                                                    <div class="relative flex h-3 w-3">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                                    </div>
                                                    <p class="text-[10px] text-red-600 dark:text-red-500 font-black uppercase tracking-widest animate-pulse">Agendamiento en Curso</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        <!-- CIERRE DE PASO 2 -->

                        <!-- PASO 3 -->
                        <div id="paso3" class="ml-8 relative">
                            <div class="absolute -left-[46px] top-1 w-8 h-8 bg-emerald-500 rounded-full border-4 border-white dark:border-gray-900 shadow-[0_0_15px_rgba(16,185,129,0.5)] flex items-center justify-center text-[14px] font-black text-white">3</div>

                            <p class="text-emerald-500 dark:text-emerald-400 font-black uppercase text-sm tracking-[0.3em] mb-1">
                                Paso 3
                            </p>

                            <h3 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mb-4 tracking-tighter italic uppercase">
                                Envía el comprobante
                            </h3>

                            <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base font-medium max-w-3xl leading-relaxed transition-colors m-3 duration-300">
                                Una vez realizado el pago, envíanos tu número de referencia de pago, que aparece en la parte inferior
                                del comprobante, (mira la imagen de ejemplo). Validaremos tu pago y un Esecialista te contactará en minutos.
                            </p>

                            <!-- FORM -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch w-full">
                                <div class="flex flex-col items-center justify-center bg-white/50 dark:bg-gray-800 rounded-[3.5rem] p-10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.04)] border border-slate-100 dark:border-white/5 overflow-hidden group">
                                    <img
                                        src="../assets/comprobante_Nequi.webp"
                                        alt="Comprobante pago Nequi"
                                        class="w-full max-w-sm rounded-2xl shadow-lg">
                                </div>
                                <div class="relative bg-white/50 dark:bg-gray-800 rounded-[3.5rem] p-10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.04)] border border-slate-100 dark:border-white/5 flex flex-col justify-center overflow-hidden group">

                                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl opacity-70 group-hover:opacity-100 transition-opacity duration-700"></div>

                                    <div class="relative z-10 flex items-center gap-5 mb-10">
                                        <div class="border-l-4 border-emerald-500 pl-6 py-2">
                                            <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-tight">
                                                ¡Casi hemos terminado!
                                                <span class="block text-slate-900 dark:text-emerald-400 font-bold text-sm md:text-base uppercase tracking-[0.15em] mt-1">
                                                    Envía la referencia del comprobante para confirmar la asesoría:
                                                </span>
                                            </h1>
                                        </div>
                                    </div>

                                    <form id="formWhatsapp" method="POST" action="" class="relative z-10 space-y-6">
                                        <input type="hidden" name="action" value="upload_proof">
                                        <input type="hidden" name="appointment_id" value="<?php echo $current_appointment['id']; ?>">

                                        <div class="relative group/input">
                                            <label class="block text-[10px] font-black text-slate-500 dark:text-emerald-400 uppercase tracking-[0.2em] mb-3 ml-2">
                                                Referencia de Transacción
                                            </label>

                                            <div class="relative">
                                                <textarea name="proof_details" rows="3" required
                                                    class="w-full p-6 bg-slate-50 dark:bg-white/5 border-2 border-emerald/20 dark:border-white/20 rounded-[2.2rem] 
                           focus:bg-white dark:focus:bg-white/10 focus:border-emerald-400 focus:ring-[12px] focus:ring-emerald-500/5 
                           outline-none transition-all duration-300 text-sm font-bold text-slate-700 dark:text-white 
                           placeholder:text-slate-500 dark:placeholder:text-slate-300 dark:focus:border-emerald-400 focus:ring-[12px] tracking-tight"
                                                    placeholder="Escribe el código de referencia o adjunta detalles..."></textarea>

                                                <div class="absolute bottom-5 right-6 text-slate-200 dark:text-slate-700 group-focus-within/input:text-emerald-400 transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit"
                                            class="group relative w-full py-6 bg-[#10B981] overflow-hidden rounded-[1.8rem] font-black text-xs uppercase tracking-[0.1em] text-white shadow-[0_10px_20px_rgba(6,44,29,0.2)] hover:shadow-[0_10px_20px_rgba(16,185,129,0.3)] active:scale-[0.98] transition-all duration-300">

                                            <div class="absolute inset-0 bg-emerald-600 translate-y-full group-hover:translate-y-0 transition-transform duration-500 ease-out"></div>

                                            <span class="relative z-10 flex items-center justify-center gap-3">
                                                Enviar Referencia
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </span>
                                        </button>

                                        <div class="flex items-center justify-center gap-2 opacity-60">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-center text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-widest">
                                                Conexión segura y cifrada
                                            </p>
                                        </div>
                                    </form>
                                    <script>
                                        /*
                                    document.getElementById('formWhatsapp').addEventListener('submit', function(e) {
                                        e.preventDefault();

                                        //Capturo los datos del form con el id que tiene el formlario
                                        const proof_details = this.proof_details.value;

                                        //Numero cel de Los Profes
                                        const numeroLosProfes = `+573166692913`

                                        //Mensaje
                                        let mensaje = `Nuevo pago para Kiosco Los Profes%0A%0A`;
                                        mensaje += `Referencia de pago: ${proof_details}%0A%0A`;
                                        mensaje += `Ya realice el pago, quedo atent@.`;

                                        const url = `https://wa.me/${numeroLosProfes}?text=${mensaje}`;
                                        window.open(url, `_blank`); //Abre en nueva pestaña del navegador
                                    });
                                    */
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-white/50 dark:bg-gray-800 rounded-[3.5rem] p-12 md:p-24 shadow-sm border border-slate-100 dark:border-white/5 text-center relative overflow-hidden">
                    <div class="absolute -top-12 -left-12 w-64 h-64 <?php echo $style['bg']; ?> opacity-[0.03] rounded-full blur-3xl"></div>

                    <div class="w-32 h-32 md:w-42 md:h-42 lg:w-52 lg:h-52 <?php echo $style['bg']; ?> rounded-[3rem] flex items-center justify-center mx-auto mb-12 shadow-2xl relative z-10 border-[12px] dark:border-gray-800 transform hover:rotate-6 transition-transform duration-700 ease-out overflow-hidden">
                        <img src="../assets/logo1.png"
                            alt="100%"
                            class="w-full h-full object-contain p-4 drop-shadow-md">
                    </div>

                    <?php if ($status === 'PENDING_VALIDATION'): ?>
                        <div class="max-w-md mx-auto relative p-6 bg-emerald-200/30 dark:bg-emerald-900/20 rounded-[2rem] border border-emerald-300/30 dark:border-emerald-500/20 shadow-inner">
                            <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-medium text-lg">
                                <span class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-full mb-4">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                    </span>
                                    Procesando con prioridad
                                </span>

                                <br>

                                <span class="text-slate-900 dark:text-slate-100 font-black text-xl block mb-2">
                                    ¡Tu proceso ha comenzado con éxito! 🚀
                                </span>

                                Tu pago está siendo validado por nuestro sistema. En un Momento, un
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold italic">Coordinador Especialista</span>
                                se comunicará contigo para darte la bienvenida y entregarte todos los detalles exclusivos de tu asesoría.

                                <span class="block mt-4 text-sm font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                                    ¡Prepárate para llevar tu aprendizaje al siguiente nivel!
                                </span>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($current_appointment['proof_details']): ?>
                        <div class="mt-16 p-8 bg-slate-200/50 dark:bg-gray-700/50 rounded-[2rem] text-left border-2 border-dashed border-slate-300 dark:border-gray-600 relative max-w-xl mx-auto">
                            <span class="absolute -top-3 left-10 bg-white dark:bg-gray-800 px-4 py-1 rounded-full text-[10px] font-black text-black dark:text-white uppercase tracking-widest shadow-sm">Tu Pin de Referencia</span>
                            <p class="text-slate-800 dark:text-slate-100 font-extrabold italic text-md leading-relaxed">"<?php echo htmlspecialchars($current_appointment['proof_details']); ?>"</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
    </div>
    </div>

    <!-- Script para el Timer de Expiración -->
    <script>
        const expiresAtTimestamp = <?php echo $expires_at_timestamp; ?>;
        const timerDisplay = document.getElementById('timer-display');

        function updateTimer() {
            const now = new Date().getTime();
            const distance = expiresAtTimestamp - now;

            if (distance <= 0) {
                if (timerDisplay) {
                    timerDisplay.textContent = "00:00";
                    // No recargamos inmediatamente, esperamos a que PHP lo haga en el siguiente request
                    // Pero podemos cambiar el estilo para indicar la expiración
                    timerDisplay.closest('.text-center').innerHTML = "<p class='text-xl font-bold text-red-600 tracking-wide'>¡Tiempo Expirado! Refresque para cancelar la Asesoria.</p>";
                }
                clearInterval(x);
                return;
            }

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            const displayMinutes = String(minutes).padStart(2, '0');
            const displaySeconds = String(seconds).padStart(2, '0');

            if (timerDisplay) {
                timerDisplay.textContent = displayMinutes + ":" + displaySeconds;
            }
        }

        if (expiresAtTimestamp > 0 && timerDisplay) {
            // Actualizar el contador cada segundo
            var x = setInterval(updateTimer, 1000);
            // Ejecutar inmediatamente al cargar
            updateTimer();
        }
    </script>

<?php

        // ===============================================
        // VISTA: Login de Administrador
        // ===============================================
        elseif ($current_view === $VIEWS['ADMIN_LOGIN']): ?>

    <div class="min-h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('../assets/por.webp');">
        <div class="w-full bg-white/95 backdrop-blur-sm rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800/95 dark:border-gray-700">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <h2 class="text-3xl md:text-4xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white text-center">Acceso Administrador</h2>

                <form method="POST" class="space-y-4 md:space-y-6" action="">
                    <input type="hidden" name="action" value="admin_login">

                    <div>
                        <label for="admin_email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                        <input type="email" id="admin_email" name="email" value=""
                            class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 outline-none block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="admin@gmail.com"
                            required>
                    </div>

                    <div>
                        <label for="admin_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contraseña</label>
                        <input type="password" id="admin_password" name="password" value=""
                            class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 outline-none block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="••••••••"
                            required>
                    </div>

                    <button type="submit"
                        class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
                        Iniciar Sesión
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="?" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold text hover:underline dark:text-indigo-500 dark:hover:text-indigo-400">Volver a Agendar Asesoria</a>
                </div>
            </div>
        </div>
    </div>
<?php

        // ===============================================
        // VISTA: Dashboard de Administrador
        // =ENTA EL CÓDIGO DEL DASHBOARD...
        // ===============================================
        elseif ($current_view === $VIEWS['ADMIN_DASHBOARD'] && $is_admin):
            // Obtener todas las citas
            $all_appointments = get_all_appointments($pdo);
            $filter = $_GET['filter'] ?? 'ALL'; // Filtro por defecto
            $filtered_appointments = [];

            // Nuevo: Calcular conteos para el panel de resumen
            $counts = [
                'ALL' => count($all_appointments),
                'PENDING_PAYMENT' => 0,
                'PENDING_VALIDATION' => 0,
                'PAID' => 0,
                'CANCELLED' => 0,
            ];

            foreach ($all_appointments as $app) {
                if (isset($counts[$app['status']])) {
                    $counts[$app['status']]++;
                }
                // Aplicar el filtro a la lista de la tabla
                if ($filter === 'ALL' || $app['status'] === $filter) {
                    $filtered_appointments[] = $app;
                }
            }

            // Definir estilos y textos de estado
            $status_details = [
                'PENDING_PAYMENT' => ['text' => 'Pendiente Pago', 'bg' => 'bg-yellow-200 text-yellow-800', 'color' => 'yellow'],
                'PENDING_VALIDATION' => ['text' => 'Pendiente Validación', 'bg' => 'bg-blue-200 text-blue-800', 'color' => 'blue'],
                'PAID' => ['text' => 'CONFIRMADO / PAGADA', 'bg' => 'bg-green-200 text-green-800', 'color' => 'green'],
                'CANCELLED' => ['text' => 'Cancelada / Expirada', 'bg' => 'bg-red-200 text-red-800', 'color' => 'red'],
            ];
            $filters = ['ALL' => 'Todas'] + array_map(fn($d) => $d['text'], $status_details);

            // Preparar el Modal de Edición (si se activa)
            $edit_appointment_id = $_GET['edit'] ?? null;
            $appointment_to_edit = null;
            if ($edit_appointment_id && is_numeric($edit_appointment_id)) {
                $appointment_to_edit = get_appointment_by_id($edit_appointment_id, $pdo);
            }
?>

    <div class="mt-4 py-4 border-l-4 border-indigo-500 pl-4 bg-gray-200 dark:bg-gray-800 rounded-r-2xl shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="relative">
                <div class="w-12 h-12 bg-gradient-to-tr from-indigo-600 to-purple-500 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-md">
                    <?php echo strtoupper(substr($_SESSION['admin_nombre'] ?? 'A', 0, 1)); ?>
                </div>
                <span class="absolute bottom-0 right-0 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500 border-2 border-white"></span>
                </span>
            </div>
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <p class="text-[10px] text-gray-800 dark:text-gray-200 uppercase tracking-[0.2em] font-bold">Sesión activa</p>
                </div>
                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200 uppercase leading-tight">
                    <?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? 'Administrador'); ?>
                </p>
                <div class="mt-1">
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full font-black uppercase tracking-wider">
                        <?php echo htmlspecialchars($_SESSION['admin_rol'] ?? 'Invitado'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-8">
        <h1 class="text-4xl lg:text-5xl font-black text-gray-800 dark:text-slate-200 mt-10 tracking-tight">Panel de
            <span class="bg-gradient-to-r from-indigo-600 to-purple-500 bg-clip-text text-transparent">Administración</span>
        </h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="flex flex-wrap items-center gap-3 mb-5">
            <?php
            $subs = $pdo->query("SELECT * FROM subjects_list WHERE is_active = 1")->fetchAll();
            foreach ($subs as $m): ?>
                <div class="flex items-center gap-2 px-4 py-2 rounded-full border-2 border-gray-500 dark:border-gray-400 font-bold text-xs" style="color: <?php echo $m['color_hex']; ?>">
                    <?php echo $m['name']; ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="delete_type" value="subject">
                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                        <button type="submit" class="ml-1 text-800 dark:text-gray-200 hover:text-red-500 dark:hover:text-red-500">×</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Panel de Conteo de Asesorías -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <?php
            $count_cards = [
                'ALL' => ['title' => 'Total Asesorías', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'color' => 'bg-indigo-600', 'count' => $counts['ALL']],
                'PENDING_VALIDATION' => ['title' => 'Pendientes Validar', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'bg-blue-600', 'count' => $counts['PENDING_VALIDATION']],
                'PENDING_PAYMENT' => ['title' => 'Pendientes Pago', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'bg-yellow-600', 'count' => $counts['PENDING_PAYMENT']],
                'PAID' => ['title' => 'Pagadas (Conf.)', 'icon' => 'M5 13l4 4L19 7', 'color' => 'bg-green-600', 'count' => $counts['PAID']],
                'CANCELLED' => ['title' => 'Canceladas / Exp.', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'bg-red-600', 'count' => $counts['CANCELLED']],
            ];

            foreach ($count_cards as $key => $card): ?>
            <div class="<?php echo $card['color']; ?> p-6 rounded-2xl text-white shadow-xl flex items-center justify-between transform hover:-translate-y-2 hover:shadow-2xl transition-all duration-300 cursor-pointer group relative overflow-hidden"
                onclick="window.location.href='?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $key; ?>'">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <p class="text-4xl font-black tracking-tight mb-1"><?php echo $card['count']; ?></p>
                    <p class="text-xs font-bold uppercase tracking-widest opacity-90"><?php echo $card['title']; ?></p>
                </div>
                <div class="relative z-10 bg-white/20 p-3 rounded-lg backdrop-blur-sm">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="<?php echo $card['icon']; ?>"></path>
                    </svg>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Fin Panel de Conteo de Asesorías -->

    <?php
            // Detectamos la vista
            $view = $_GET['view'] ?? '';
    ?>

    <div class="container-fluid <?php echo ($view !== 'admin_dashboard') ? 'schedule-bg' : ''; ?>">
    </div>
    <!-- Filtros (Se mantiene el bloque de filtros original para la compatibilidad y visibilidad del filtro activo) -->
    <div class="mb-7 flex flex-wrap gap-2 items-center">
        <span class="w-full sm:w-auto mb-2 sm:mb-0 font-semibold text-gray-800 dark:text-slate-200">Filtrar por Estado:</span>
        <?php foreach ($filters as $status_key => $status_text):
                $isActive = $filter === $status_key;
                $button_class = $isActive
                    ? "bg-indigo-600 text-white font-bold"
                    : "bg-gray-200 text-gray-700 hover:text-slate-800 hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-300 dark:hover:text-slate-800";
        ?>
            <a href="?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $status_key; ?>"
                class="uppercase py-2 px-3 sm:px-4 rounded-lg text-xs sm:text-sm transition-all duration-200 whitespace-nowrap <?php echo $button_class; ?>">
                <?php echo $status_text; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="relative mx-auto text-center max-w-5xl overflow-hidden bg-white/50 dark:bg-gray-800 p-6 md:p-8 rounded-2xl border border-gray-300 dark:border-gray-700 shadow-sm">
        <div class="absolute -top16 -right-16 w-64 h-64 bg-indigo-100/40 dark:bg-gray-700/20 rounded-full"></div>
        <div class="absolute -bottom-16 -left-16 w-64 h-64 bg-indigo-100/40 dark:bg-gray-700/20 rounded-full"></div>

        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
            <div class="flex-shrink-0 bg-white/50 dark:bg-gray-800 p-3  rounded-xl shadow-md">
                <img src="../assets/logo2.png" alt="Logo" class="h-16 md:h-20 w-auto">
            </div>
            <div class="text-center md:text-left uppercase">
                <h1 class="text-xl md:text-2xl font-black text-gray-900 dark:text-gray-200 md:mb-4 lg:mb-4 xl:mb-4 leading-tight">
                    ¡Bienvenido, <span class="text-indigo-600 uppercase"><?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin'); ?></span>!
                </h1>
                <p class="text-gray-900 dark:text-gray-100 text-xs md:text-sm font-medium mt-1 mb-2 md:mt-1 md:mb-2">Gestión centralizada de asesorías académicas</p>
            </div>

        </div>
        <div class="mb-6 relative group">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-indigo-500 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" id="smartSearch"
                placeholder="Buscar por estudiante, asignatura, id o comprobante de pago..."
                class="w-full pl-12 pr-4 py-4 bg-white border-2 border-gray-100 rounded-2xl shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-300 dark:focus:border-indigo-500 dark:focus:ring-2 dark:focus:ring-indigo-300 transition-all text-gray-700 font-medium placeholder-gray-400"
                onkeyup="filterTable()">
        </div>

    </div>
    <div class="flex justify-end m-4">
        <button type="button" onclick="descargarInforme()"
            class="w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-2xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Descargar Informe XLS
        </button>

        <script>
            function descargarInforme() {
                // 1. Obtener el texto del buscador inteligente (ajusta el ID si es diferente)
                const buscador = document.getElementById('smartSearch').value;

                // 2. Obtener el filtro de estado actual de la URL (si existe)
                const urlParams = new URLSearchParams(window.location.search);
                const filtroEstado = urlParams.get('filter') || 'ALL';

                // 3. Redirigir enviando ambos filtros
                window.location.href = `?action=export_xls&filter=${filtroEstado}&search=${encodeURIComponent(buscador)}`;
            }
        </script>
    </div>

    <div>

        <div class="rounded-2xl mb-4 scrollbar-hide touch-pan-x">
            <div class="overflow-x-auto">

                <table class="hidden md:table w-full text-center">
                    <thead class="bg-white/50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">ID</th>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">Estudiante</th>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">Asignatura</th>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">Fecha / Hora</th>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">Comprobante</th>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">Estado</th>
                            <th class="px-6 py-4 text-[12px] font-black uppercase text-gray-800 dark:text-gray-200 tracking-widest">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered_appointments)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-10 italic text-gray-400">No hay Asesorias registradas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered_appointments as $app):
                                $status = $status_details[$app['status']] ?? ['bg' => 'bg-gray-100 text-gray-800', 'text' => $app['status']];
                            ?>
                                <tr class="bg-white/50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-center transition-all shadow-sm rounded-2xl">
                                    <td class="font-bold text-gray-800 dark:text-gray-200">#<?php echo $app['id']; ?></td>

                                    <td>
                                        <div class="text-gray-800 dark:text-gray-200 uppercase text-sm"><?php echo htmlspecialchars($app['student_name']); ?></div>
                                        <div class="text-xs text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($app['student_contact']); ?></div>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-center font-medium text-gray-800 dark:text-gray-200">
                                        <?php echo htmlspecialchars($app['subject']); ?>
                                    </td>

                                    <td>
                                        <div class="text-sm text-gray-800 dark:text-gray-200"><?php echo $app['date']; ?></div>
                                        <div class="text-xs text-gray-800 dark:text-gray-200"><?php echo $app['time']; ?></div>
                                    </td>

                                    <td class="px-3 proof-details-cell">
                                        <?php if (!empty($app['proof_details'])): ?>
                                            <div class="text-xs italic text-gray-800 dark:text-gray-200 break-all">
                                                <?php echo nl2br(htmlspecialchars($app['proof_details'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-800 dark:text-gray-200">N/A</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="text-gray-800 px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tighter <?php echo $status['bg']; ?>">
                                            <?php echo $status['text']; ?>
                                        </span>
                                    </td>

                                    <td class="py-2">
                                        <div class="flex gap-3 justify-center">

                                            <a href="?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $filter; ?>&edit=<?php echo $app['id']; ?>"
                                                class="p-3 bg-indigo-100 text-indigo-600 rounded-2xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Editar">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>

                                            <?php if ($app['status'] !== 'PAID'): ?>
                                                <form method="POST" class="inline" onsubmit="event.preventDefault(); confirmSubmit(this, '¿Confirmar pago?', 'Se liberará la asesoría manualmente.', 'success', 'Sí, confirmar');">
                                                    <input type="hidden" name="action" value="confirm_payment_manual">
                                                    <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                                    <button type="submit" class="p-3 bg-emerald-100 text-emerald-600 rounded-2xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Liberar Asesoria">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="p-3 text-emerald-600 bg-emerald-50 rounded-2xl border border-emerald-200">
                                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>

                                            <form method="POST" class="inline" onsubmit="event.preventDefault(); confirmSubmit(this, '¿Eliminar asesoría?', 'Esta acción no se puede deshacer.', 'warning', 'Sí, eliminar');">
                                                <input type="hidden" name="action" value="admin_delete">
                                                <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                                <button type="submit" class="p-3 bg-red-100 text-red-600 rounded-2xl hover:bg-red-600 hover:text-white transition-all shadow-sm">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="md:hidden space-y-4 px-2">
                    <?php foreach ($filtered_appointments as $app):
                        $status = $status_details[$app['status']] ?? ['bg' => 'bg-white/50 text-gray-800', 'text' => $app['status']];
                    ?>
                        <div class="relative bg-white/50 dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 mb-2">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-[10px] font-black text-gray-800 dark:text-gray-200 uppercase tracking-widest">ID #<?php echo $app['id']; ?></span>
                                    <h3 class="font-black text-gray-800 dark:text-gray-200 uppercase text-lg leading-tight"><?php echo htmlspecialchars($app['student_name']); ?></h3>
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-bold"><?php echo htmlspecialchars($app['student_contact']); ?></p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo $status['bg']; ?>">
                                    <?php echo $status['text']; ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-gray-100 dark:bg-gray-900/50 p-3 rounded-xl">
                                    <span class="block text-[8px] font-black text-gray-600 dark:text-gray-300 uppercase">Asignatura</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($app['subject']); ?></span>
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-900/50 p-3 rounded-xl">
                                    <span class="block text-[8px] font-black text-gray-600 dark:text-gray-300 uppercase">Horario</span>
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200"><?php echo $app['date']; ?> <br> <?php echo $app['time']; ?></span>
                                </div>
                            </div>

                            <div class="mb-4 bg-indigo-50/50 dark:bg-indigo-900/20 p-3 rounded-2xl border border-dashed border-indigo-200 dark:border-indigo-800">
                                <span class="block text-[8px] font-black text-indigo-600 dark:text-indigo-400 uppercase mb-1">Comprobante</span>
                                <p class="text-xs italic text-gray-800 dark:text-gray-200 break-all"><?php echo !empty($app['proof_details']) ? nl2br(htmlspecialchars($app['proof_details'])) : 'Sin detalles de pago.'; ?></p>
                            </div>

                            <div class="flex gap-2">
                                <a href="?edit=<?php echo $app['id']; ?>" class="flex-1 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-center rounded-xl font-black text-[10px] uppercase">Editar</a>
                                <?php if ($app['status'] === 'PENDING_VALIDATION'): ?>
                                    <form method="POST" class="flex-1" onsubmit="event.preventDefault(); confirmSubmit(this, '¿Confirmar Pago?', 'Se liberará la asesoría manualmente.', 'success', 'Confirmar');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="status" value="PAID">
                                        <button class="w-full py-3 bg-green-500 dark:bg-green-600 text-white rounded-xl font-black text-[10px] uppercase">Confirmar Pago</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            </table>
        </div>

        <div class="md:hidden space-y-4">
            <?php foreach ($filtered_appointments as $app):
                $status = $status_details[$app['status']] ?? ['bg' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200', 'text' => $app['status']];
            ?>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 relative overflow-hidden">
                    <div class="absolute top-0 right-0 h-1 w-20 <?php echo strpos($status['bg'], 'green') !== false ? 'bg-green-500' : 'bg-amber-500'; ?>"></div>

                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-xl bg-indigo-600 dark:bg-indigo-500 text-white flex items-center justify-center font-bold mr-3">
                                <?php echo strtoupper(substr($app['student_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h3 class="font-black text-gray-800 dark:text-gray-200 leading-none"><?php echo htmlspecialchars($app['student_name']); ?></h3>
                                <p class="text-xs text-gray-800 dark:text-gray-200 mt-1"><?php echo htmlspecialchars($app['student_contact']); ?></p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-[9px] font-black rounded-full uppercase <?php echo $status['bg']; ?>">
                            <?php echo strtoupper($status['text']); ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 py-3 border-y border-gray-200 dark:border-gray-700 my-3 text-sm">
                        <div>
                            <p class="text-[10px] uppercase text-gray-800 dark:text-gray-200 font-bold">Materia</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($app['subject']); ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase text-gray-800 dark:text-gray-200 font-bold">Horario</p>
                            <p class="font-semibold text-gray-700 dark:text-gray-200"> <?php echo $app['date']; ?> <span class="mx-4 text-indigo-600 dark:text-indigo-400"> <?php echo $app['time']; ?></span></p>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <a href="?edit=<?php echo $app['id']; ?>" class="flex-1 bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 text-center py-2.5 rounded-xl font-bold text-xs">Editar</a>
                        <form method="POST" class="flex-1" onsubmit="event.preventDefault(); confirmSubmit(this, '¿Eliminar asesoría?', 'Se borrará el registro permanentemente.', 'warning', 'Borrar');">
                            <input type="hidden" name="action" value="admin_delete">
                            <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                            <button class="w-full bg-red-50 dark:bg-red-900/30 text-red-500 dark:text-red-400 py-2.5 rounded-xl font-bold text-xs text-center">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    </div>

    <!-- Modal de Edición de Cita (Admin) -->
    <?php if ($appointment_to_edit): ?>
        <div id="edit-modal" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 relative border dark:border-gray-700">
                <h3 class="text-center text-2xl font-bold text-indigo-700 dark:text-indigo-400 mb-4 border-b dark:border-gray-700 pb-2">Editar Asesoria #<?php echo $appointment_to_edit['id']; ?></h3>

                <a href="?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $filter; ?>"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>

                <form method="POST" action="" id="admin-edit-form" class="space-y-4" onsubmit="event.preventDefault(); confirmSubmit(this, '¿Guardar cambios?', 'Se ha editado la asesoría correctamente.', 'question', 'Confirmar');">
                    <input type="hidden" name="action" value="admin_edit">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_to_edit['id']; ?>">
                    <input type="hidden" name="filter" value="<?php echo $filter; ?>">

                    <div>
                        <label for="edit_subject" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Asignatura</label>
                        <select id="edit_subject" name="subject" onchange="toggleOtherSubjectAdmin(this.value, 'edit')"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                            required>
                            <?php foreach ($ASSIGNATURES as $sub): ?>
                                <option value="<?php echo htmlspecialchars($sub); ?>"
                                    <?php echo ($appointment_to_edit['subject'] === $sub) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="edit_other_subject_container" style="<?php echo ($appointment_to_edit['subject'] === 'Otro tipo de asesorías') ? 'display:block;' : 'display:none;'; ?>" class="mt-2">
                            <input type="text" id="edit_other_subject" name="other_subject"
                                placeholder="Especifique la asignatura"
                                value="<?php echo htmlspecialchars($appointment_to_edit['other_subject'] ?? ''); ?>"
                                class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <div class="flex-1">
                            <label for="edit_date" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Fecha</label>
                            <input type="date" id="edit_date" name="date"
                                value="<?php echo htmlspecialchars($appointment_to_edit['date']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                                required>
                        </div>
                        <div class="flex-1">
                            <label for="edit_time" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Hora</label>
                            <input type="time" id="edit_time" name="time"
                                value="<?php echo htmlspecialchars($appointment_to_edit['time']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                                required>
                        </div>
                    </div>

                    <div>
                        <label for="edit_student_name" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Nombre del Estudiante</label>
                        <input type="text" id="edit_student_name" name="student_name"
                            value="<?php echo htmlspecialchars($appointment_to_edit['student_name']); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                            required>
                    </div>

                    <div>
                        <label for="edit_student_contact" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Email / Teléfono de Contacto</label>
                        <input type="text" id="edit_student_contact" name="student_contact"
                            value="<?php echo htmlspecialchars($appointment_to_edit['student_contact']); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                            required>
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 border border-transparent rounded-lg shadow-lg text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150">
                        Guardar Cambios
                    </button>
                </form>
            </div>
        </div>

        <script>
            // Función específica para el formulario de edición del Admin
            function toggleOtherSubjectAdmin(value, prefix) {
                const container = document.getElementById(`${prefix}_other_subject_container`);
                const input = document.getElementById(`${prefix}_other_subject`);
                if (container && input) {
                    if (value === 'Otro tipo de asesorías') {
                        container.style.display = 'block';
                    } else {
                        container.style.display = 'none';
                    }
                }
            }
        </script>

    <?php endif; ?>

<?php endif; ?>
</div>

<script>
    // Variables y funciones del Slider (reubicadas para que sean globales y usables)
    let currentSlide = 0;
    const totalSlides = <?php echo count($SLIDER_IMAGES); ?>;
    let slideInterval;
    let sliderTrack;
    let dots;
    const slideDuration = 5000; // 5 segundos

    // Función para confirmaciones con SweetAlert2
    function confirmSubmit(form, title, text, icon, confirmText) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: icon === 'warning' ? '#ef4444' : '#10b981',
            cancelButtonColor: '#6366f1',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }

    function updateSlider() {
        if (!sliderTrack) return;
        const offset = -currentSlide * 100;
        sliderTrack.style.transform = `translateX(${offset}%)`;

        dots.forEach((dot, index) => {
            if (index === currentSlide) {
                dot.classList.add('bg-opacity-100', 'ring-2', 'ring-white');
                dot.classList.remove('bg-opacity-50');
            } else {
                dot.classList.remove('bg-opacity-100', 'ring-2', 'ring-white');
                dot.classList.add('bg-opacity-50');
            }
        });
    }

    function changeSlide(direction) {
        currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
        updateSlider();
        resetInterval();
    }

    function goToSlide(index) {
        currentSlide = index;
        updateSlider();
        resetInterval();
    }

    function startInterval() {
        slideInterval = setInterval(() => {
            changeSlide(1);
        }, slideDuration);
    }

    function resetInterval() {
        clearInterval(slideInterval);
        startInterval();
    }

    // --- NUEVAS FUNCIONES PARA INTERACCIÓN CON TARJETAS Y FORMULARIO ---

    const formContainer = document.getElementById('schedule_form_container');
    const subjectInput = document.getElementById('subject'); // Hidden input
    const subjectDisplay = document.getElementById('subject_display'); // Display div
    const selectedSubjectTitle = document.getElementById('selected_subject_title');
    const otherSubjectContainer = document.getElementById('other_subject_container');
    const subjectCards = document.querySelectorAll('.subject-card');

    /**
     * Maneja la selección de la asignatura desde la tarjeta.
     */
    function selectSubject(subject) {
        // Mapeo de colores neón por materia
        const colorMap = {
            'Matemáticas': '#6366f1',
            'Inglés': '#f97316',
            'Química': '#22c55e',
            'Física': '#b917eaf9',
            'Biología': '#06b6d4',
            'Comprensión Lectora': '#fbbf24',
            'Ciencias Sociales': '#fa2c2c',
            'Otro Tipo de Asesorías': '#ec4899'
        };

        // Aplicar el color correspondiente al contenedor del formulario
        const accentColor = colorMap[subject] || '#6366f1';
        formContainer.style.setProperty('--accent-color', accentColor);

        // 1. Ocultar todas las tarjetas y el título de selección
        subjectCards.forEach(card => card.style.display = 'none');

        // 2. Rellenar los campos del formulario
        subjectInput.value = subject;
        subjectDisplay.textContent = subject;
        selectedSubjectTitle.textContent = `Agendar: ${subject}`;

        // 3. Mostrar u ocultar el campo "Otro"
        if (subject === 'Otro tipo de asesorías') {
            otherSubjectContainer.style.display = 'block';
        } else {
            otherSubjectContainer.style.display = 'none';
        }

        // 4. Mostrar el formulario con animación (opcional, pero mejora la UX)
        formContainer.style.display = 'flex';

        // 5. Scroll al formulario
        formContainer.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }

    /**
     * Oculta el formulario y muestra las tarjetas.
     */
    function hideForm() {
        formContainer.style.display = 'none';
        subjectCards.forEach(card => card.style.display = 'block');
        // Scroll arriba
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }


    // Inicialización al cargar la página
    window.onload = function() {
        // Inicialización del Slider
        sliderTrack = document.getElementById('slider-track');
        dots = document.querySelectorAll('.dot');
        if (sliderTrack) {
            updateSlider();
            startInterval();
        }

        // Lógica para el formulario (si la vista es SCHEDULE_VIEW)
        // Ya no necesitamos toggleOtherSubject en el onload porque lo manejamos en selectSubject

        // Lógica para el formulario de edición del Admin
        const editSubjectSelect = document.getElementById('edit_subject');
        if (editSubjectSelect) toggleOtherSubjectAdmin(editSubjectSelect.value, 'edit');

        // Si hay un error de POST, el formulario debe seguir visible
        const isError = <?php echo isset($error_message) && $error_message ? 'true' : 'false'; ?>;
        const isSchedulePost = <?php echo isset($_POST['action']) && $_POST['action'] === 'schedule' ? 'true' : 'false'; ?>;

        if (isError && isSchedulePost) {
            // Si hubo un error al enviar el formulario, lo mostramos y rellenamos el campo.
            const lastSubject = "<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>";
            if (lastSubject) {
                selectSubject(lastSubject);
                // Ocultar tarjetas de nuevo ya que selectSubject las mostró si no lo estaban
                subjectCards.forEach(card => card.style.display = 'none');
            }
        }

    };
</script>
<?php if (!$is_admin): // Solo se muestra si NO es administrador
?>
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end">

        <div class="mb-3 bg-white border border-gray-100 px-4 py-2 rounded-2xl shadow-xl animate-bounce hidden md:block">
            <p class="text-xs font-semibold text-gray-600 flex items-center">
                <span class="flex h-2 w-2 mr-2">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                ¡Logremos tus objetivos académicos juntos! 🚀
            </p>
        </div>

        <a href="https://wa.me/573164876650?text=Hola!%20Vengo%20del%20Kiosco%20de%20Asesorías%20y%20necesito%20apoyo%20con%20una%20materia."
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-center justify-center w-16 h-16 bg-[#25D366] rounded-full shadow-[0_10px_25px_rgba(37,211,102,0.4)] hover:shadow-[0_15px_30px_rgba(37,211,102,0.6)] transition-all duration-300 transform hover:scale-110 active:scale-95 group relative">

            <svg class="w-9 h-9 text-white group-hover:rotate-12 transition-transform duration-300" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.148-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
            </svg>

            <span class="absolute inset-0 rounded-full bg-green-500 opacity-20 animate-ping"></span>
        </a>
    </div>
<?php endif; ?>
<script>
    function filterTable() {
        const input = document.getElementById("smartSearch");
        const filter = input.value.toLowerCase();

        // 1. Filtrar filas de la tabla (Vista PC)
        const tableRows = document.querySelectorAll("tbody tr");
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });

        // 2. Filtrar tarjetas (Vista Móvil Android)
        const mobileCards = document.querySelectorAll(".md:hidden .bg-white.p-5");
        mobileCards.forEach(card => {
            const text = card.innerText.toLowerCase();
            // Si no hay resultados, ocultamos la tarjeta con una pequeña animación
            if (text.includes(filter)) {
                card.classList.remove("hidden");
            } else {
                card.classList.add("hidden");
            }
        });

        // Mostrar mensaje si no hay resultados
        const noResults = document.getElementById("no-results-msg");
        const visibleCards = document.querySelectorAll(".md:hidden .bg-white.p-5:not(.hidden)");

        // Si tienes un div para "no resultados", puedes activarlo aquí

    }
</script>
<script>
    /**
     * Monitorea el estado de la cita automáticamente
     */
    function monitorAppointmentStatus(appointmentId, currentStatus) {
        // Si no hay ID de cita, no hacemos nada
        if (!appointmentId) return;

        const checkInterval = setInterval(async () => {
            try {
                // Consultamos al endpoint que creamos en el paso 1
                const response = await fetch(`?action=check_status&id=${appointmentId}`);
                const data = await response.json();

                // Si el estado en la base de datos es diferente al estado actual de la pantalla
                if (data.status !== currentStatus) {
                    clearInterval(checkInterval); // Detenemos el monitor

                    // Mostramos un mensaje elegante antes de recargar
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Asesoria Actualizada!',
                            text: 'El administrador ha procesado tu solicitud. Recargando...',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            willClose: () => {
                                window.location.href = window.location.pathname; // Redirige a la principal
                            }
                        });
                    } else {
                        window.location.href = window.location.pathname;
                    }
                }
            } catch (error) {
                console.error("Error monitoreando estado:", error);
            }
        }, 3000); // Se ejecuta cada 3 segundos (ajustable)
    }

    // Iniciar el monitoreo si estamos en la vista de confirmación
    <?php if (isset($current_appointment['id'])): ?>
        monitorAppointmentStatus(
            "<?php echo $current_appointment['id']; ?>",
            "<?php echo $status; ?>"
        );
    <?php endif; ?>
</script>

</body>

<div id="modalSlide" class="fixed inset-0 z-[60]  bg-black/60 dark:bg-black/80 backdrop-blur-sm p-4 flex items-center justify-center">
    <div class="bg-white dark:bg-zinc-900 border dark:border-zinc-800 rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl scale-up-center">
        <h2 class="text-2xl font-black text-gray-800 dark:text-gray-100 mb-6">Nuevo Banner</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="action" value="add_slide">
            <div class="bg-gray-50 dark:bg-zinc-800 p-6 rounded-3xl border-2 border-dashed border-gray-200 dark:border-zinc-700 text-center hover:border-indigo-500 dark:hover:border-zinc-500 transition overflow-hidden">
                <input type="file" name="slide_image" accept="image/*" required class="text-sm text-gray-700 dark:text-zinc-300 file:mr-4 file:py-2 file:px-4 rounded-full file:border-0 file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 w-full">
            </div>
            <input type="text" name="title" placeholder="Título del banner" class="w-full p-4 bg-gray-100 dark:bg-zinc-800 text-black dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 rounded-2xl outline-none font-bold border border-transparent dark:border-zinc-700 focus:border-indigo-500" required>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-lg transition">GUARDAR</button>
                <button type="button" onclick="this.closest('#modalSlide').classList.add('hidden')" class="flex-1 py-4 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-500 dark:text-zinc-300 font-bold rounded-2xl transition">Cerrar</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Deshabilitar click derecho
    document.addEventListener('contextmenu', event => event.preventDefault());

    // Deshabilitar combinaciones de teclas (F12, Ctrl+U, Ctrl+Shift+I)
    document.onkeydown = function(e) {
        if (e.keyCode == 123) return false; // F12
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;
        if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;
        if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
    };
</script>

<style>
    /* Evitar que seleccionen texto con el mouse */
    body {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;



    }
</style>
<script>
    // 1. Definir urlParams al inicio para que esté disponible en todos los checks
    const urlParams = new URLSearchParams(window.location.search);

    // 2. Verificar mensajes de éxito
    if (urlParams.get('msg') === 'edited') {
        Swal.fire({
            title: '¡Editado!',
            text: 'La Asesoria ha sido actualizada correctamente.',
            icon: 'success',
            confirmButtonColor: '#6366f1',
            showClass: {
                popup: 'animate__animated animate__fadeInUp'
            }
        });
    }

    if (urlParams.has('success') || urlParams.get('msg') === 'success') {
        Swal.fire({
            title: '¡Operación Exitosa!',
            text: 'Los cambios se han procesado correctamente.',
            icon: 'success',
            confirmButtonColor: '#6366f1',
            showClass: {
                popup: 'animate__animated animate__fadeInUp'
            }
        });
    }

    if (urlParams.get('msg') === 'deleted') {
        Swal.fire({
            title: '¡Eliminado!',
            text: 'La Asesoria ha sido borrada y el ID reordenado.',
            icon: 'success',
            confirmButtonColor: '#6366f1',
            showClass: {
                popup: 'animate__animated animate__fadeInUp'
            }
        });
    }

    // Limpiar la URL para evitar que los mensajes se repitan al recargar (F5)
    if (urlParams.has('msg') || urlParams.has('success')) {
        const cleanParams = new URLSearchParams(window.location.search);
        cleanParams.delete('msg');
        cleanParams.delete('success');
        const newSearch = cleanParams.toString();
        const newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '');
        window.history.replaceState({}, document.title, newUrl);
    }
</script>

</html>