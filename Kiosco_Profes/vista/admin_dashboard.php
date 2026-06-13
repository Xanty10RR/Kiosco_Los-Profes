  // ===============================================
        // VISTA: Login de Administrador
        // ===============================================
        elseif ($current_view === $VIEWS['ADMIN_LOGIN']): ?>
                <div class="min-h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat"
                    style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('por.jpg');">

                    <div class="p-8 bg-white/95 backdrop-blur-sm shadow-2xl rounded-xl max-w-sm mx-auto w-full border-t-8 border-purple-600">
                        <h2 class="text-3xl font-extrabold text-purple-700 mb-6 border-b pb-2 text-center">Acceso Administrador</h2>

                        <form method="POST" action="" class="space-y-4">
                            <input type="hidden" name="action" value="admin_login">

                            <div>
                                <label for="admin_email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" id="admin_email" name="email" value=""
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2"
                                    required>
                            </div>

                            <div>
                                <label for="admin_password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                                <input type="password" id="admin_password" name="password" value=""
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 p-2"
                                    required>
                            </div>

                            <button type="submit"
                                class="w-full py-3 px-4 border border-transparent rounded-lg shadow-lg text-base font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition duration-150 ease-in-out transform hover:scale-[1.01]">
                                Iniciar Sesión
                            </button>
                        </form>

                        <div class="mt-6 text-center">
                            <a href="?" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">Volver a Agendar Asesoria</a>
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

                <div class="mt-4 py-3 border-l-4 border-indigo-500 pl-4 bg-gray-50/80 rounded-r-2xl shadow-sm flex items-center justify-between">
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
                                <p class="text-[10px] text-gray-400 uppercase tracking-[0.2em] font-bold">Sesión activa</p>
                            </div>
                            <p class="text-lg font-semibold text-gray-800 leading-tight">
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
                <h1 class="text-4xl font-extrabold text-gray-900 mb-6 border-b pb-2">Panel de Administración</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-4">


                    <div class="flex flex-wrap gap-3">
                        <?php
                        $subs = $pdo->query("SELECT * FROM subjects_list WHERE is_active = 1")->fetchAll();
                        foreach ($subs as $m): ?>
                            <div class="flex items-center gap-2 px-4 py-2 rounded-full border-2 border-gray-100 font-bold text-xs" style="color: <?php echo $m['color_hex']; ?>">
                                <?php echo $m['name']; ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="delete_type" value="subject">
                                    <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                    <button type="submit" class="ml-1 text-gray-300 hover:text-red-500">×</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
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
                            <p class="text-4xl font-black tracking-tight mb-1">
                                <?php echo $card['count']; ?>
                            </p>
                            <p class="text-xs font-bold uppercase tracking-widest opacity-90 group-hover:opacity-100 transition-opacity">
                                <?php echo $card['title']; ?>
                            </p>
                        </div>

                        <div class="relative z-10 bg-white/20 p-3 rounded-lg backdrop-blur-sm group-hover:bg-white/30 transition-colors duration-300">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
            <div class="mb-6 flex space-x-3 items-center">
                <span class="font-semibold text-gray-700">Filtrar por Estado:</span>
                <?php foreach ($filters as $status_key => $status_text):
                    $isActive = $filter === $status_key;
                    $button_class = $isActive
                        ? "bg-indigo-600 text-white font-bold"
                        : "bg-gray-200 text-gray-700 hover:bg-gray-300";
                ?>
                    <a href="?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $status_key; ?>"
                        class="py-2 px-4 rounded-lg text-sm transition-colors <?php echo $button_class; ?>">
                        <?php echo $status_text; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-gradient-to-r from-white to-gray-50 p-4 md:p-6 shadow-md rounded-2xl border border-gray-100 overflow-hidden relative">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-50 rounded-full opacity-50"></div>

                <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 relative z-10">
                    <div class="flex-shrink-0 bg-white p-2 rounded-xl shadow-sm border border-gray-100">
                        <img src="logo2.png" alt="Logo" class="h-16 md:h-20 w-auto object-contain">
                    </div>
                    <div class="text-center md:text-left">
                        <h1 class="text-xl md:text-2xl font-black text-gray-800 leading-tight">
                            ¡Bienvenido, <span class="text-indigo-600"><?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin'); ?></span>!
                        </h1>
                        <p class="text-gray-500 text-xs md:text-sm font-medium mt-1">Gestión centralizada de asesorías académicas</p>
                    </div>

                </div>
                <div class="mb-6 relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-indigo-500 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" id="smartSearch"
                        placeholder="Buscar por estudiante, asignatura o ID..."
                        class="w-full pl-12 pr-4 py-4 bg-white border-2 border-gray-100 rounded-2xl shadow-sm outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition-all text-gray-700 font-medium placeholder-gray-400"
                        onkeyup="filterTable()">
                </div>

            </div>
            <div class="flex justify-end mb-4">
                <button type="button" onclick="descargarInforme()"
                    class="w-full md:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-6 rounded-2xl shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Descargar Informe CSV
                </button>

                <script>
                    function descargarInforme() {
                        // 1. Obtener el texto del buscador inteligente (ajusta el ID si es diferente)
                        const buscador = document.getElementById('smartSearch').value;

                        // 2. Obtener el filtro de estado actual de la URL (si existe)
                        const urlParams = new URLSearchParams(window.location.search);
                        const filtroEstado = urlParams.get('filter') || 'ALL';

                        // 3. Redirigir enviando ambos filtros
                        window.location.href = `?action=export_csv&filter=${filtroEstado}&search=${encodeURIComponent(buscador)}`;
                    }
                </script>
            </div>
            <div class="bg-transparent">

                <div class="overflow-x-auto bg-white shadow-2xl shadow-gray-200/50 rounded-[2rem] border border-gray-100 mb-8 scrollbar-hide touch-pan-x">
                    <div class="overflow-x-auto pb-4">

                        <table class="hidden md:table w-full text-left border-separate border-spacing-y-3">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 tracking-widest">ID</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 tracking-widest">Estudiante</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 tracking-widest">Asignatura</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 tracking-widest">Fecha / Hora</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 tracking-widest">Comprobante</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase text-gray-400 tracking-widest">Estado</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black uppercase text-gray-400 tracking-widest">Acciones</th>
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
                                        <tr class="bg-white hover:bg-gray-50 transition-all shadow-sm rounded-2xl">
                                            <td class="px-6 py-4 font-bold text-gray-400">#<?php echo $app['id']; ?></td>

                                            <td class="px-6 py-4">
                                                <div class="font-black text-gray-800 uppercase text-sm"><?php echo htmlspecialchars($app['student_name']); ?></div>
                                                <div class="text-xs text-indigo-500 font-bold"><?php echo htmlspecialchars($app['student_contact']); ?></div>
                                            </td>

                                            <td class="px-6 py-4 text-sm font-medium text-gray-600">
                                                <?php echo htmlspecialchars($app['subject']); ?>
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="text-sm font-bold text-gray-700"><?php echo $app['date']; ?></div>
                                                <div class="text-xs text-gray-400"><?php echo $app['time']; ?></div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($app['date']); ?><br>
                                                <span class="font-semibold"><?php echo htmlspecialchars($app['time']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm proof-details-cell">
                                                <?php if (!empty($app['proof_details'])): ?>
                                                    <div class="text-xs italic p-1 bg-gray-100 rounded break-words">
                                                        <?php echo nl2br(htmlspecialchars($app['proof_details'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="px-6 py-4 text-center">
                                                <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-tighter <?php echo $status['bg']; ?>">
                                                    <?php echo $status['text']; ?>
                                                </span>
                                            </td>

                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-end gap-3">

                                                    <a href="?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $filter; ?>&edit=<?php echo $app['id']; ?>"
                                                        class="p-3 bg-indigo-100 text-indigo-600 rounded-2xl hover:bg-indigo-600 hover:text-white transition-all shadow-sm" title="Editar">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </a>

                                                    <?php if ($app['status'] !== 'PAID'): ?>
                                                        <form method="POST" class="inline" onsubmit="return confirm('¿Confirmar pago y liberar Asesoria manualmente?');">
                                                            <input type="hidden" name="action" value="confirm_payment_manual">
                                                            <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                                            <button type="submit" class="p-3 bg-emerald-500 text-white rounded-2xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100" title="Liberar Asesoria">
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

                                                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar permanentemente?');">
                                                        <input type="hidden" name="action" value="admin_delete">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                                        <button type="submit" class="p-3 bg-red-100 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-sm">
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
                                $status = $status_details[$app['status']] ?? ['bg' => 'bg-gray-100 text-gray-800', 'text' => $app['status']];
                            ?>
                                <div class="bg-white p-6 rounded-[2rem] shadow-lg border border-gray-100 relative overflow-hidden">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <span class="text-[10px] font-black text-gray-300 uppercase tracking-widest">ID #<?php echo $app['id']; ?></span>
                                            <h3 class="font-black text-gray-800 uppercase text-lg leading-tight"><?php echo htmlspecialchars($app['student_name']); ?></h3>
                                            <p class="text-xs text-indigo-500 font-bold"><?php echo htmlspecialchars($app['student_contact']); ?></p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase <?php echo $status['bg']; ?>">
                                            <?php echo $status['text']; ?>
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div class="bg-gray-50 p-3 rounded-2xl">
                                            <span class="block text-[8px] font-black text-gray-400 uppercase">Asignatura</span>
                                            <span class="text-xs font-bold text-gray-700"><?php echo htmlspecialchars($app['subject']); ?></span>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-2xl">
                                            <span class="block text-[8px] font-black text-gray-400 uppercase">Horario</span>
                                            <span class="text-xs font-bold text-gray-700"><?php echo $app['date']; ?> <br> <?php echo $app['time']; ?></span>
                                        </div>
                                    </div>

                                    <div class="mb-4 bg-indigo-50/30 p-3 rounded-2xl border border-dashed border-indigo-100">
                                        <span class="block text-[8px] font-black text-indigo-400 uppercase mb-1">Comprobante</span>
                                        <p class="text-[10px] italic text-indigo-900 leading-relaxed"><?php echo !empty($app['proof_details']) ? nl2br(htmlspecialchars($app['proof_details'])) : 'Sin detalles de pago.'; ?></p>
                                    </div>

                                    <div class="flex gap-2">
                                        <a href="?edit=<?php echo $app['id']; ?>" class="flex-1 py-3 bg-gray-100 text-gray-600 text-center rounded-xl font-black text-[10px] uppercase">Editar</a>
                                        <?php if ($app['status'] === 'PENDING_VALIDATION'): ?>
                                            <form method="POST" class="flex-1">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                                <input type="hidden" name="status" value="PAID">
                                                <button class="w-full py-3 bg-green-500 text-white rounded-xl font-black text-[10px] uppercase shadow-lg shadow-green-100">Confirmar Pago</button>
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
                        $status = $status_details[$app['status']] ?? ['bg' => 'bg-gray-100 text-gray-800', 'text' => $app['status']];
                    ?>
                        <div class="bg-white p-5 rounded-2xl shadow-md border border-gray-100 relative overflow-hidden">
                            <div class="absolute top-0 right-0 h-1 w-20 <?php echo strpos($status['bg'], 'green') !== false ? 'bg-green-500' : 'bg-amber-500'; ?>"></div>

                            <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold mr-3 shadow-lg shadow-indigo-200">
                                        <?php echo strtoupper(substr($app['student_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h3 class="font-black text-gray-900 leading-none"><?php echo htmlspecialchars($app['student_name']); ?></h3>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($app['student_contact']); ?></p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-[9px] font-black rounded-full <?php echo $status['bg']; ?>">
                                    <?php echo strtoupper($status['text']); ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 py-3 border-y border-gray-50 my-3 text-sm">
                                <div>
                                    <p class="text-[10px] uppercase text-gray-400 font-bold">Materia</p>
                                    <p class="font-semibold text-gray-700"><?php echo htmlspecialchars($app['subject']); ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase text-gray-400 font-bold">Horario</p>
                                    <p class="font-semibold text-gray-700"><?php echo $app['date']; ?> <span class="text-indigo-500"><?php echo $app['time']; ?></span></p>
                                </div>
                            </div>

                            <div class="flex gap-2 mt-4">
                                <a href="?edit=<?php echo $app['id']; ?>" class="flex-1 bg-indigo-50 text-indigo-600 text-center py-2.5 rounded-xl font-bold text-xs">Editar</a>
                                <form method="POST" class="flex-1" onsubmit="return confirm('¿Borrar?');">
                                    <input type="hidden" name="action" value="admin_delete">
                                    <input type="hidden" name="appointment_id" value="<?php echo $app['id']; ?>">
                                    <button class="w-full bg-red-50 text-red-500 py-2.5 rounded-xl font-bold text-xs text-center">Eliminar</button>
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
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 relative">
                <h3 class="text-2xl font-bold text-indigo-700 mb-4 border-b pb-2">Editar Asesoria #<?php echo $appointment_to_edit['id']; ?></h3>

                <a href="?view=<?php echo $VIEWS['ADMIN_DASHBOARD']; ?>&filter=<?php echo $filter; ?>"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>

                <form method="POST" action="" id="admin-edit-form" class="space-y-4">
                    <input type="hidden" name="action" value="admin_edit">
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_to_edit['id']; ?>">
                    <input type="hidden" name="filter" value="<?php echo $filter; ?>">

                    <div>
                        <label for="edit_subject" class="block text-sm font-medium text-gray-700">Asignatura</label>
                        <select id="edit_subject" name="subject" onchange="toggleOtherSubjectAdmin(this.value, 'edit')"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
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
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2">
                        </div>
                    </div>

                    <div class="flex space-x-4">
                        <div class="flex-1">
                            <label for="edit_date" class="block text-sm font-medium text-gray-700">Fecha</label>
                            <input type="date" id="edit_date" name="date"
                                value="<?php echo htmlspecialchars($appointment_to_edit['date']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                                required>
                        </div>
                        <div class="flex-1">
                            <label for="edit_time" class="block text-sm font-medium text-gray-700">Hora</label>
                            <input type="time" id="edit_time" name="time"
                                value="<?php echo htmlspecialchars($appointment_to_edit['time']); ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                                required>
                        </div>
                    </div>

                    <div>
                        <label for="edit_student_name" class="block text-sm font-medium text-gray-700">Nombre del Estudiante</label>
                        <input type="text" id="edit_student_name" name="student_name"
                            value="<?php echo htmlspecialchars($appointment_to_edit['student_name']); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                            required>
                    </div>

                    <div>
                        <label for="edit_student_contact" class="block text-sm font-medium text-gray-700">Email / Teléfono de Contacto</label>
                        <input type="text" id="edit_student_contact" name="student_contact"
                            value="<?php echo htmlspecialchars($appointment_to_edit['student_contact']); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 p-2"
                            required>
                    </div>

                    <button type="submit"
                        class="w-full py-3 px-4 border border-transparent rounded-lg shadow-lg text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150">
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

<?php