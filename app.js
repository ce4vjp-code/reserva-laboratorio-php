let currentUser = null;
let currentWeekStart = new Date();
let currentWeekReservations = [];
let teachers = [];

// Definir los bloques horarios oficiales
const BLOCKS = [
    { id: 1, time: "08:30 - 10:00" },
    { id: 2, time: "10:20 - 11:50" },
    { id: 3, time: "12:00 - 13:30" },
    { id: 4, time: "14:15 - 15:45" },
    { id: 5, time: "16:00 - 17:30" }
];

const DAYS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

document.addEventListener('DOMContentLoaded', init);

async function init() {
    // Alinear al Lunes de esta semana
    const day = currentWeekStart.getDay();
    const diff = currentWeekStart.getDate() - day + (day == 0 ? -6:1);
    currentWeekStart = new Date(currentWeekStart.setDate(diff));

    // Auto-seleccionar el día actual en las pestañas móviles
    const currentDayOfWeek = new Date().getDay(); // 0=Dom, 1=Lun, etc.
    let tabIndex = currentDayOfWeek - 1;
    if (tabIndex < 0 || tabIndex > 4) tabIndex = 0; // Si es fin de semana, por defecto al Lunes
    selectMobileDay(tabIndex);

    const loggedIn = await checkSession();
    if (!loggedIn) return;

    if(currentUser.rol === 'admin') {
        await loadTeachers();
    }
    await loadCalendar();
}

async function checkSession() {
    try {
        const res = await fetch('api/check_session.php');
        const text = await res.text(); // Leer como texto primero por si hay un error PHP
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Error parseando JSON:", text);
            document.getElementById('userDisplayName').textContent = "Error de conexión BD";
            alert("Error del servidor: Revisa la consola o la ruta del .env en config.php");
            return false;
        }

        if(data.error) {
            document.getElementById('userDisplayName').textContent = data.error;
            return false;
        }

        if(!data.logged_in) {
            window.location.href = 'login.html';
            return false;
        } else {
            currentUser = data.user;
            document.getElementById('userDisplayName').textContent = `${currentUser.nombre} (${currentUser.rol === 'admin' ? 'Administrador' : 'Profesor'})`;
            return true;
        }
    } catch(e) {
        console.error("Error de red:", e);
        window.location.href = 'login.html';
        return false;
    }
}

async function loadTeachers() {
    const res = await fetch('api/get_usuarios.php');
    const data = await res.json();
    if(data.success) {
        teachers = data.data;
        const select = document.getElementById('modalTeacher');
        select.innerHTML = '';
        teachers.forEach(t => {
            select.innerHTML += `<option value="${t.id}">${t.nombre}</option>`;
        });
    }
}

async function logout() {
    await fetch('api/logout.php');
    window.location.href = 'login.html';
}

function formatDateForDB(date) {
    const d = new Date(date);
    let month = '' + (d.getMonth() + 1), day = '' + d.getDate(), year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

function getDayDate(offsetIndex) {
    const d = new Date(currentWeekStart);
    d.setDate(d.getDate() + offsetIndex);
    return d;
}

function changeWeek(offset) {
    currentWeekStart.setDate(currentWeekStart.getDate() + (offset * 7));
    loadCalendar();
}

async function loadCalendar() {
    const startStr = formatDateForDB(currentWeekStart);
    const endStr = formatDateForDB(getDayDate(4));
    
    document.getElementById('currentWeekLabel').textContent = `Semana del ${startStr} al ${endStr}`;

    const res = await fetch(`api/get_reservas.php?start=${startStr}&end=${endStr}`);
    const data = await res.json();
    currentWeekReservations = data.success ? data.data : [];
    
    renderGrid();
}

function selectMobileDay(index) {
    document.getElementById('calendarGrid').setAttribute('data-active-day', index);
    
    // Actualizar botones visualmente
    document.querySelectorAll('.mobile-tab').forEach((btn, i) => {
        if(i === index) btn.classList.add('active');
        else btn.classList.remove('active');
    });
}

function renderGrid() {
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '<div class="grid-header">Hora</div>';
    
    DAYS.forEach((d, i) => {
        const date = getDayDate(i);
        const header = document.createElement('div');
        header.className = `grid-header day-col day-${i}`;
        header.textContent = `${d} ${date.getDate()}`;
        grid.appendChild(header);
    });

    BLOCKS.forEach(block => {
        const timeHeader = document.createElement('div');
        timeHeader.className = 'grid-time';
        timeHeader.innerHTML = `Bloque ${block.id}<br><small>${block.time}</small>`;
        grid.appendChild(timeHeader);
        
        DAYS.forEach((d, i) => {
            const dateStr = formatDateForDB(getDayDate(i));
            const reserva = currentWeekReservations.find(r => r.fecha === dateStr && parseInt(r.bloque_id) === block.id);
            
            let html = '';
            let classes = `grid-cell day-col day-${i} `;
            
            if(!reserva) {
                classes += 'empty';
                html = `<div style="text-align:center;color:#3b82f6;font-size:14px;font-weight:600;opacity:0.7;">+ Asignar</div>`;
            } else if (reserva.estado === 'disponible') {
                classes += 'available';
                html = `<div class="cell-title">Disponible</div><div class="cell-subtitle">Liberado</div>`;
            } else if (reserva.estado === 'reservado') {
                classes += 'reserved';
                html = `<div class="cell-title">${reserva.profesor_nombre}</div><div class="cell-subtitle">${reserva.curso || ''}</div>`;
            } else if (reserva.estado === 'confirmado') {
                classes += 'confirmed';
                html = `<div class="cell-title">${reserva.profesor_nombre}</div><div class="cell-subtitle">${reserva.curso || ''}</div>`;
            }

            const div = document.createElement('div');
            div.className = classes;
            div.innerHTML = html;
            div.onclick = () => openModal(dateStr, i+1, block.id, reserva);
            grid.appendChild(div);
        });
    });
}

function openModal(dateStr, dayNum, blockId, reserva) {
    try {
        document.getElementById('actionModal').classList.add('active');
        
        document.getElementById('modalDate').value = dateStr;
        document.getElementById('modalDay').value = dayNum;
        document.getElementById('modalBlock').value = blockId;
        
        const title = document.getElementById('modalTitle');
        const statusText = document.getElementById('modalStatusText');
        const groupTeacher = document.getElementById('groupTeacher');
        const groupCourse = document.getElementById('groupCourse');
        const groupRepeat = document.getElementById('groupRepeat');
        
        // Reset inputs
        if(currentUser && currentUser.rol === 'admin') {
            const select = document.getElementById('modalTeacher');
            if (select) select.value = currentUser.id;
        }
        document.getElementById('modalCourse').value = '';
        
        // Hide all buttons
        ['btnReserve', 'btnConfirm', 'btnMakeAvailable', 'btnCancelRes'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.classList.add('hidden');
        });

        if (!reserva) {
            title.textContent = "Nuevo Bloque";
            statusText.textContent = "Este bloque está vacío en la base de datos.";
            document.getElementById('modalReservaId').value = "";
            
            if (currentUser && currentUser.rol === 'admin') {
                groupTeacher.style.display = 'block';
                groupCourse.style.display = 'block';
                if (groupRepeat) groupRepeat.style.display = 'block';
                document.getElementById('btnReserve').classList.remove('hidden');
            } else {
                groupTeacher.style.display = 'none';
                groupCourse.style.display = 'none';
                if (groupRepeat) groupRepeat.style.display = 'none';
                statusText.textContent = "Bloque no calendarizado. Solo el administrador puede asignar horas nuevas aquí.";
            }
        } else {
            document.getElementById('modalReservaId').value = reserva.id;
            document.getElementById('modalCourse').value = reserva.curso || '';
            if(currentUser && currentUser.rol === 'admin') {
                document.getElementById('modalTeacher').value = reserva.usuario_id;
            }
            
            title.textContent = `Bloque: ${reserva.estado.toUpperCase()}`;
            statusText.textContent = `Asignado a: ${reserva.profesor_nombre}`;

            if (reserva.estado === 'disponible') {
                if (currentUser && currentUser.rol === 'admin') {
                    groupTeacher.style.display = 'block';
                    groupCourse.style.display = 'block';
                    if (groupRepeat) groupRepeat.style.display = 'none'; // No repetimos algo ya marcado libre en un día específico
                    document.getElementById('btnReserve').classList.remove('hidden'); // Admin puede re-reservarlo
                    document.getElementById('btnCancelRes').classList.remove('hidden');
                } else {
                    groupTeacher.style.display = 'none';
                    groupCourse.style.display = 'block';
                    if (groupRepeat) groupRepeat.style.display = 'none';
                    statusText.textContent = "Este bloque está libre. Puedes tomarlo escribiendo tu curso.";
                    document.getElementById('btnReserve').classList.remove('hidden');
                    document.getElementById('btnReserve').textContent = "Tomar Bloque";
                }
            } 
            else if (reserva.estado === 'reservado') {
                if (currentUser && currentUser.rol === 'admin') {
                    groupTeacher.style.display = 'block';
                    groupCourse.style.display = 'block';
                    if (groupRepeat) groupRepeat.style.display = 'block'; // Admin puede guardar y aplicar a futuro
                    document.getElementById('btnReserve').textContent = "Guardar Cambios";
                    document.getElementById('btnReserve').classList.remove('hidden');
                    document.getElementById('btnConfirm').classList.remove('hidden');
                    document.getElementById('btnMakeAvailable').classList.remove('hidden');
                    document.getElementById('btnCancelRes').classList.remove('hidden');
                } else {
                    groupTeacher.style.display = 'none';
                    groupCourse.style.display = 'none';
                    if (reserva.usuario_id == currentUser.id) {
                        statusText.textContent = "Este es tu bloque. Por favor, confirma tu asistencia.";
                        document.getElementById('btnConfirm').classList.remove('hidden');
                    } else {
                        statusText.textContent = `Este bloque está reservado por ${reserva.profesor_nombre}.`;
                    }
                }
            }
            else if (reserva.estado === 'confirmado') {
                groupTeacher.style.display = 'none';
                groupCourse.style.display = 'none';
                
                if (currentUser && currentUser.rol === 'admin') {
                    document.getElementById('btnMakeAvailable').classList.remove('hidden');
                    document.getElementById('btnCancelRes').classList.remove('hidden');
                } else {
                    if (reserva.usuario_id == currentUser.id) {
                        statusText.textContent = "Ya has confirmado tu asistencia a este bloque.";
                    }
                }
            }
        }
    } catch (error) {
        alert("Error de Javascript al abrir la ventana: " + error.message);
    }
}

function closeModal() {
    document.getElementById('actionModal').classList.remove('active');
}

async function submitReservation() {
    const repeatCheckbox = document.getElementById('modalRepeat');
    const propagar = repeatCheckbox ? repeatCheckbox.checked : false;

    const data = {
        fecha: document.getElementById('modalDate').value,
        dia_semana: document.getElementById('modalDay').value,
        bloque_id: document.getElementById('modalBlock').value,
        curso: document.getElementById('modalCourse').value,
        propagar: propagar
    };
    if (currentUser.rol === 'admin') {
        data.usuario_id = document.getElementById('modalTeacher').value;
    }

    const res = await fetch('api/reservar.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if(result.success) {
        closeModal();
        loadCalendar();
    } else {
        alert(result.error);
    }
}

async function changeStatus(estado) {
    if(estado === 'cancelado' && !confirm("¿Seguro que deseas eliminar esta reserva por completo?")) return;
    
    const res = await fetch('api/cambiar_estado.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            reserva_id: document.getElementById('modalReservaId').value,
            estado: estado
        })
    });
    const result = await res.json();
    if(result.success) {
        closeModal();
        loadCalendar();
    } else {
        alert(result.error);
    }
}
