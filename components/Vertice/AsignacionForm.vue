<template>
  <div class="asignacion-container">
    <h2>Asignación de Cita</h2>
    
    <!-- Alerta para mensajes -->
    <div v-if="alert.show" :class="`alert alert-${alert.type} alert-dismissible fade show`" role="alert">
      {{ alert.message }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>
    
    <div v-if="isLoading" class="text-center my-4">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>
    
    <!-- Detalles de la solicitud -->
    <div v-if="solicitud" class="solicitud-details card mb-4">
      <div class="card-header">
        Detalles de la Solicitud
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>Nombre del Paciente:</strong> {{ solicitud.nombre_paciente }}</p>
            <p><strong>Cédula Paciente:</strong> {{ solicitud.cedula_paciente }}</p>
            <p><strong>Cédula Titular:</strong> {{ solicitud.cedula_titular }}</p>
          </div>
          <div class="col-md-6">
            <p><strong>Teléfono:</strong> {{ solicitud.telefono }}</p>
            <p><strong>Fecha de Nacimiento:</strong> {{ formatDate(solicitud.fecha_nacimiento) }}</p>
            <p><strong>Sexo:</strong> {{ solicitud.sexo === 'M' ? 'Masculino' : 'Femenino' }}</p>
            <p v-if="especialidadRequerida"><strong>Especialidad Requerida:</strong> {{ especialidadRequerida.nombre }}</p>
          </div>
        </div>
      </div>
    </div>
    
    <div v-else class="alert alert-info">
      Seleccione una solicitud desde la página de Solicitudes
    </div>
    
    <!-- Filtro de especialidad -->
    <div v-if="solicitud" class="card mb-4">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-4">
            <div class="form-group mb-md-0">
              <label for="semana" class="mb-2"><i class="bi bi-calendar-week"></i> Semana:</label>
              <div class="input-group">
                <input 
                  type="date" 
                  class="form-control" 
                  id="semana" 
                  v-model="fechaInicio" 
                  @change="cargarDisponibilidadSemanal"
                />
                <button class="btn btn-outline-secondary" @click="semanaAnterior">
                  <i class="bi bi-arrow-left"></i>
                </button>
                <button class="btn btn-outline-secondary" @click="semanaSiguiente">
                  <i class="bi bi-arrow-right"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="col-md-8 mt-3 mt-md-0">
            <div class="d-flex align-items-center justify-content-between">
              <span><i class="bi bi-bookmark-star"></i> <strong>Especialidad:</strong> {{ especialidadRequerida ? especialidadRequerida.nombre : 'Todas' }}</span>
              <span><i class="bi bi-person-vcard"></i> <strong>Doctores disponibles:</strong> {{ doctoresFiltrados.length }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Vista semanal de disponibilidad -->
    <div v-if="solicitud && !isLoadingCalendario" class="calendar-view card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Horarios Disponibles ({{ formatDateRange(fechaInicio, fechaFin) }})</h5>
      </div>
      <div class="card-body p-0">
        <!-- Tabla de calendario semanal -->
        <div class="table-responsive calendar-container">
          <table class="table table-bordered calendar-table">
            <thead class="sticky-header">
              <tr>
                <th class="text-center" style="width: 100px;"><i class="bi bi-clock"></i></th>
                <th 
                  v-for="(dia, index) in diasSemana" 
                  :key="index" 
                  class="text-center py-2"
                  :class="{ 'today-column': esFechaHoy(dia.fecha) }"
                >
                  <div class="day-name fw-bold">
                    <i :class="getIconoDia(dia.nombre)"></i> {{ dia.nombre }}
                  </div>
                  <div class="day-date">{{ formatDateShort(dia.fecha) }}</div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="hora in horasDelDia" :key="hora.valor">
                <td class="text-center time-cell">
                  {{ hora.etiqueta }}
                </td>
                <td 
                  v-for="(dia, diaIndex) in diasSemana" 
                  :key="`${dia.fecha}-${hora.valor}`" 
                  class="position-relative p-0 calendar-cell"
                  :class="{ 'today-column': esFechaHoy(dia.fecha) }"
                >
                  <!-- Mostrar doctores agrupados por hora -->
                  <div 
                    v-for="(doctorGroup, doctor_id) in getTurnosAgrupados(dia.fecha, hora.valor)" 
                    :key="`${dia.fecha}-${hora.valor}-${doctor_id}`"
                    class="turno-slot"
                    :class="{ 
                      'selected': selectedTurno && getIdTurnoAgrupado(doctorGroup) === selectedTurno.id,
                      'disponible': true
                    }"
                    @click="selectTurnoAgrupado(doctorGroup, dia.fecha)"
                  >
                    <div class="horario"><i class="bi bi-clock"></i> {{ formatTime(doctorGroup[0].hora_inicio) }} - {{ formatTime(getHoraFinCorregida(doctorGroup[0])) }}</div>
                    <div class="doctor-name"><i class="bi bi-person-badge"></i> Dr. {{ getDoctorNombre(doctor_id) }}</div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <div v-if="isLoadingCalendario" class="text-center my-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando calendario...</span>
      </div>
      <p>Cargando disponibilidad...</p>
    </div>
    
    <!-- Resumen de selección -->
    <div v-if="selectedTurno && selectedDoctor" class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-check-circle"></i> Resumen de Cita</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p><i class="bi bi-person"></i> <strong>Paciente:</strong> {{ solicitud.nombre_paciente }}</p>
            <p><i class="bi bi-person-badge"></i> <strong>Doctor:</strong> Dr. {{ selectedDoctor.nombre }} {{ selectedDoctor.apellido }}</p>
          </div>
          <div class="col-md-6">
            <p><i class="bi bi-calendar-event"></i> <strong>Fecha:</strong> {{ formatDate(selectedFecha) }}</p>
            <p><i class="bi bi-clock"></i> <strong>Hora:</strong> {{ formatTime(selectedTurno.hora_inicio) }} - {{ formatTime(getHoraFinCorregida(selectedTurno)) }}</p>
          </div>
        </div>
        
        <div class="mt-3 text-center">
          <button class="btn btn-success" @click="confirmarAsignacion">
            <i class="bi bi-check-lg"></i> Confirmar Asignación
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modal de confirmación -->
    <div class="modal" :class="{ 'show': showConfirmModal }" v-if="showConfirmModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="bi bi-check-circle"></i> Confirmar Asignación</h5>
            <button type="button" class="btn-close btn-close-white" @click="showConfirmModal = false">
            </button>
          </div>
          <div class="modal-body">
            <div class="confirmation-details">
              <p><i class="bi bi-question-circle"></i> ¿Está seguro que desea asignar esta cita?</p>
              <div class="alert alert-info">
                <p><i class="bi bi-person"></i> <strong>Paciente:</strong> {{ solicitud?.nombre_paciente }}</p>
                <p><i class="bi bi-person-badge"></i> <strong>Doctor:</strong> Dr. {{ selectedDoctor?.nombre }} {{ selectedDoctor?.apellido }}</p>
                <p><i class="bi bi-calendar-event"></i> <strong>Fecha:</strong> {{ formatDate(selectedFecha) }}</p>
                <p><i class="bi bi-clock"></i> <strong>Hora:</strong> {{ formatTime(selectedTurno?.hora_inicio) }} - {{ formatTime(getHoraFinCorregida(selectedTurno)) }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showConfirmModal = false">
              <i class="bi bi-x-lg"></i> Cancelar
            </button>
            <button type="button" class="btn btn-primary" :disabled="isSubmitting" @click="guardarAsignacion">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span>
              <i v-else class="bi bi-check-lg"></i> Confirmar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AsignacionForm',
  props: {
    solicitudId: {
      type: String,
      required: false
    }
  },
  data() {
    return {
      isLoading: true,
      isLoadingCalendario: false,
      isSubmitting: false,
      solicitud: null,
      especialidades: [],
      especialidadRequerida: null,
      doctores: [],
      fechaInicio: null,
      fechaFin: null,
      diasSemana: [],
      horasDelDia: [
        { valor: '08:00', etiqueta: '8:00 am' },
        { valor: '09:00', etiqueta: '9:00 am' },
        { valor: '10:00', etiqueta: '10:00 am' },
        { valor: '11:00', etiqueta: '11:00 am' },
        { valor: '12:00', etiqueta: '12:00 pm' },
        { valor: '13:00', etiqueta: '1:00 pm' },
        { valor: '14:00', etiqueta: '2:00 pm' },
        { valor: '15:00', etiqueta: '3:00 pm' },
        { valor: '16:00', etiqueta: '4:00 pm' },
        { valor: '17:00', etiqueta: '5:00 pm' }
      ],
      turnosDisponibles: [],
      selectedTurno: null,
      selectedDoctor: null,
      selectedFecha: null,
      showConfirmModal: false,
      alert: {
        show: false,
        type: 'success',
        message: ''
      }
    };
  },
  computed: {
    doctoresFiltrados() {
      if (!this.solicitud || !this.solicitud.especialidad_requerida) {
        return this.doctores;
      }
      
      return this.doctores.filter(doctor => 
        doctor.activo == 1 && 
        doctor.especialidad_id == this.solicitud.especialidad_requerida
      );
    },
    currentSolicitudId() {
      // Obtener el ID directamente de la URL si está disponible
      return this.$route.params.solicitudId || this.solicitudId;
    }
  },
  created() {
    this.fechaInicio = this.getInicioSemana();
    this.fechaFin = this.calcularFechaFin(this.fechaInicio);
    this.generarDiasSemana();
    this.loadInitialData();
  },
  watch: {
    currentSolicitudId: {
      handler(newId, oldId) {
        if (newId && newId !== oldId) {
          this.loadInitialData();
        }
      },
      immediate: true
    },
    '$route.params.solicitudId': function(newId) {
      if (newId) {
        this.loadInitialData();
      }
    }
  },
  methods: {
    getInicioSemana() {
      const fecha = new Date();
      const dia = fecha.getDay() || 7; // Domingo = 0, Lunes = 1, ..., Sábado = 6
      const diff = fecha.getDate() - dia + 1; // Ajustar al lunes
      const lunesDeSemana = new Date(fecha.setDate(diff));
      return lunesDeSemana.toISOString().split('T')[0];
    },
    calcularFechaFin(fechaInicio) {
      if (!fechaInicio) return '';
      
      const inicio = new Date(fechaInicio);
      const fin = new Date(inicio);
      fin.setDate(inicio.getDate() + 6); // 7 días (hasta el domingo)
      return fin.toISOString().split('T')[0];
    },
    generarDiasSemana() {
      this.diasSemana = [];
      const nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
      
      if (!this.fechaInicio) return;
      
      const inicio = new Date(this.fechaInicio);
      for (let i = 0; i < 7; i++) {
        const fecha = new Date(inicio);
        fecha.setDate(inicio.getDate() + i);
        
        this.diasSemana.push({
          nombre: nombresDias[i],
          fecha: fecha.toISOString().split('T')[0]
        });
      }
    },
    semanaAnterior() {
      if (!this.fechaInicio) return;
      
      const inicio = new Date(this.fechaInicio);
      inicio.setDate(inicio.getDate() - 7);
      this.fechaInicio = inicio.toISOString().split('T')[0];
      this.fechaFin = this.calcularFechaFin(this.fechaInicio);
      this.generarDiasSemana();
      this.cargarDisponibilidadSemanal();
    },
    semanaSiguiente() {
      if (!this.fechaInicio) return;
      
      const inicio = new Date(this.fechaInicio);
      inicio.setDate(inicio.getDate() + 7);
      this.fechaInicio = inicio.toISOString().split('T')[0];
      this.fechaFin = this.calcularFechaFin(this.fechaInicio);
      this.generarDiasSemana();
      this.cargarDisponibilidadSemanal();
    },
    // Métodos nuevos para la vista de tabla
    getTurnosAgrupados(fecha, hora) {
      // Obtener todos los turnos para esta fecha y hora
      const turnos = this.turnosDisponibles.filter(turno => {
        return (
          turno.fecha === fecha && 
          turno.hora_inicio.startsWith(hora)
        );
      });
      
      // Agrupar por doctor_id para eliminar duplicados
      const turnosAgrupados = {};
      turnos.forEach(turno => {
        if (!turnosAgrupados[turno.doctor_id]) {
          turnosAgrupados[turno.doctor_id] = [];
        }
        
        // Solo agregar si no existe ya un turno con la misma hora de inicio
        const existeTurno = turnosAgrupados[turno.doctor_id].some(t => 
          t.hora_inicio === turno.hora_inicio
        );
        
        if (!existeTurno) {
          turnosAgrupados[turno.doctor_id].push(turno);
        }
      });
      
      return turnosAgrupados;
    },
    getIdTurnoAgrupado(doctorGroup) {
      // Devolver el ID del primer turno del grupo
      return doctorGroup && doctorGroup.length > 0 ? doctorGroup[0].id : null;
    },
    selectTurnoAgrupado(doctorGroup, fecha) {
      if (!doctorGroup || doctorGroup.length === 0) return;
      
      // Seleccionar el primer turno del grupo
      const turno = doctorGroup[0];
      this.selectedTurno = turno;
      this.selectedFecha = fecha;
      this.selectedDoctor = this.doctores.find(d => d.id == turno.doctor_id);
    },
    getHoraFinCorregida(turno) {
      // Corregir la hora de fin para mostrar 1 hora después de la hora de inicio
      if (!turno || !turno.hora_inicio) return '';
      
      const [horas, minutos] = turno.hora_inicio.split(':');
      let horaFin = parseInt(horas) + 1;
      
      // Formatear correctamente (mantener 24h para consistencia con el backend)
      if (horaFin < 10) {
        return `0${horaFin}:${minutos}`;
      }
      return `${horaFin}:${minutos}`;
    },
    getIconoDia(nombreDia) {
      // Devolver el ícono correspondiente a cada día de la semana
      const iconos = {
        'Lunes': 'bi-1-circle',
        'Martes': 'bi-2-circle',
        'Miércoles': 'bi-3-circle',
        'Jueves': 'bi-4-circle',
        'Viernes': 'bi-5-circle',
        'Sábado': 'bi-6-circle',
        'Domingo': 'bi-7-circle'
      };
      
      return iconos[nombreDia] || 'bi-calendar-day';
    },
    async loadInitialData() {
      this.isLoading = true;
      this.resetSelections();
      this.solicitud = null;
      this.especialidadRequerida = null;
      this.doctores = [];
      
      try {
        if (!this.currentSolicitudId) {
          this.showAlert('warning', 'No se ha proporcionado un ID de solicitud');
          this.isLoading = false;
          return;
        }
        
        // Cargar solicitud y especialidades en paralelo
        await Promise.all([
          this.loadEspecialidades(),
          this.loadSolicitud()
        ]);
        
        // Verificar que tenemos la solicitud antes de continuar
        if (!this.solicitud) {
          this.isLoading = false;
          return;
        }
        
        // Buscar la especialidad requerida
        if (this.solicitud.especialidad_requerida) {
          this.especialidadRequerida = this.especialidades.find(
            e => e.id == this.solicitud.especialidad_requerida
          );
        }
        
        // Cargar los doctores
        await this.loadDoctores();
        
        // Ahora cargar disponibilidad para la semana actual
        await this.cargarDisponibilidadSemanal();
        
      } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        this.showAlert('danger', 'Error al cargar los datos iniciales: ' + (error.response?.data?.error || error.message));
      } finally {
        this.isLoading = false;
      }
    },
    async loadEspecialidades() {
      try {
        const response = await axios.get('/api/doctores/especialidades.php');
        this.especialidades = response.data;
      } catch (error) {
        console.error('Error cargando especialidades:', error);
        this.showAlert('danger', 'Error al cargar las especialidades');
      }
    },
    async loadSolicitud() {
      try {
        const solicitudId = this.currentSolicitudId;
        
        if (!solicitudId) {
          this.showAlert('warning', 'ID de solicitud no proporcionado');
          return;
        }
        
        const response = await axios.get('/api/vertice/listar_solicitudes.php');
        
        if (response.data.data && response.data.data.length > 0) {
          const solicitudEncontrada = response.data.data.find(s => s.id == solicitudId);
          
          if (solicitudEncontrada) {
            this.solicitud = solicitudEncontrada;
          } else {
            this.showAlert('warning', `Solicitud con ID ${solicitudId} no encontrada`);
          }
        } else {
          this.showAlert('warning', 'No se encontraron solicitudes');
        }
      } catch (error) {
        console.error('Error cargando solicitud:', error);
        this.showAlert('danger', 'Error al cargar la solicitud: ' + (error.response?.data?.error || error.message));
      }
    },
    async loadDoctores() {
      try {
        // Verificar si hay una especialidad requerida para filtrar doctores
        let url = '/api/doctores/listar.php';
        if (this.solicitud && this.solicitud.especialidad_requerida) {
          url += `?especialidad_id=${this.solicitud.especialidad_requerida}`;
        }
        
        // Cargar los doctores
        const response = await axios.get(url);
        this.doctores = response.data;
        
        // También cargar los doctores activos
        const activosResponse = await axios.get('/api/vertice/listar_doctores.php');
        const doctoresActivos = activosResponse.data.data || [];
        
        // Añadir atributo activo a cada doctor
        this.doctores = this.doctores.map(doctor => {
          const activo = doctoresActivos.find(d => d.id == doctor.id);
          return {
            ...doctor,
            activo: activo ? 1 : 0
          };
        });
      } catch (error) {
        console.error('Error cargando doctores:', error);
        this.showAlert('danger', 'Error al cargar los doctores');
      }
    },
    async cargarDisponibilidadSemanal() {
      if (!this.solicitud || !this.doctoresFiltrados.length) return;
      
      this.isLoadingCalendario = true;
      this.turnosDisponibles = [];
      this.selectedTurno = null;
      this.selectedDoctor = null;
      this.selectedFecha = null;
      
      try {
        // Construir un array de promesas para cargar los horarios de todos los doctores
        const promesas = this.doctoresFiltrados.map(doctor => 
          this.cargarHorariosDoctor(doctor.id)
        );
        
        // Ejecutar todas las promesas en paralelo
        await Promise.all(promesas);
        
      } catch (error) {
        console.error('Error cargando disponibilidad semanal:', error);
        this.showAlert('danger', 'Error al cargar la disponibilidad de la semana');
      } finally {
        this.isLoadingCalendario = false;
      }
    },
    async cargarHorariosDoctor(doctorId) {
      try {
        // Cargar los horarios del doctor
        const horariosResponse = await axios.get(`/api/vertice/listar_horarios.php?doctor_id=${doctorId}`);
        const horarios = horariosResponse.data.data || [];
        
        // Para cada horario, cargar sus turnos
        for (const horario of horarios) {
          // Verificar si el horario aplica a la semana actual
          const diaValido = this.verificarHorarioEnSemana(horario);
          
          if (diaValido) {
            // Cargar los turnos de este horario
            const turnosResponse = await axios.get(`/api/vertice/listar_turnos.php?horario_id=${horario.id}`);
            const turnos = turnosResponse.data.data || [];
            
            // Filtrar solo los turnos disponibles y agregar información adicional
            const turnosDisponibles = turnos
              .filter(turno => turno.estado === 'disponible')
              .map(turno => ({
                ...turno,
                doctor_id: doctorId,
                fecha: diaValido,
              }));
            
            // Agregar estos turnos a nuestro array principal
            this.turnosDisponibles = [...this.turnosDisponibles, ...turnosDisponibles];
          }
        }
      } catch (error) {
        console.error(`Error cargando horarios para doctor ${doctorId}:`, error);
      }
    },
    verificarHorarioEnSemana(horario) {
      if (horario.fecha) {
        // Si tiene fecha específica, verificar si está dentro del rango
        return (horario.fecha >= this.fechaInicio && horario.fecha <= this.fechaFin) 
          ? horario.fecha 
          : false;
      } else if (horario.dia_semana) {
        // Si es un día de la semana regular, encontrar esa fecha dentro de la semana actual
        const dia = parseInt(horario.dia_semana);
        const diaEncontrado = this.diasSemana[dia - 1]; // -1 porque dia_semana usa 1=Lunes, etc.
        return diaEncontrado ? diaEncontrado.fecha : false;
      }
      
      return false;
    },
    getTurnosDisponibles(fecha, hora) {
      // Filtrar turnos que corresponden a esta fecha y hora
      return this.turnosDisponibles.filter(turno => {
        return (
          turno.fecha === fecha && 
          turno.hora_inicio.startsWith(hora)
        );
      });
    },
    getDoctorNombre(doctorId) {
      const doctor = this.doctores.find(d => d.id == doctorId);
      return doctor ? `${doctor.nombre} ${doctor.apellido}` : 'Desconocido';
    },
    selectTurno(turno, fecha) {
      this.selectedTurno = turno;
      this.selectedFecha = fecha;
      this.selectedDoctor = this.doctores.find(d => d.id == turno.doctor_id);
    },
    confirmarAsignacion() {
      if (!this.selectedTurno || !this.selectedDoctor) {
        this.showAlert('warning', 'Debe seleccionar un turno');
        return;
      }
      
      this.showConfirmModal = true;
    },

    async guardarAsignacion() {
  if (this.isSubmitting) return;
  
  this.isSubmitting = true;
  
  try {
    // Verificar si hay un número de teléfono válido
    if (!this.solicitud.telefono) {
      console.warn('Advertencia: El paciente no tiene número de teléfono registrado');
    }
    
    const asignacionData = {
      solicitud_id: this.currentSolicitudId,
      turno_id: this.selectedTurno.id,
      doctor_id: this.selectedDoctor.id
    };
    
    const response = await axios.post('/api/vertice/crear_asignacion.php', asignacionData);

    console.log('Respuesta completa:', response);
    
    // Procesar la respuesta según el resultado de la notificación WhatsApp
    if (response.data.notificacion_whatsapp) {
      if (response.data.notificacion_whatsapp.success) {
        this.showAlert('success', 'Asignación creada y notificación WhatsApp enviada exitosamente');
      } else {
        this.showAlert('warning', 'Asignación creada exitosamente, pero hubo un problema al enviar la notificación WhatsApp: ' + 
          (response.data.notificacion_whatsapp.message || 'Error desconocido'));
      }
    } else if (!response.data.telefono) {
      this.showAlert('info', 'Asignación creada exitosamente. No se envió notificación porque el paciente no tiene número de teléfono registrado.');
    } else {
      this.showAlert('success', 'Asignación creada exitosamente');
    }
    
    this.showConfirmModal = false;
    
    // Redireccionar después de un breve retraso
    setTimeout(() => {
      this.$router.push({ name: 'vertice-solicitudes' });
    }, 2000);
  } catch (error) {
    console.error('Error creando asignación:', error);
    this.showAlert('danger', 'Error al crear la asignación: ' + (error.response?.data?.error || error.message));
    this.showConfirmModal = false;
  } finally {
    this.isSubmitting = false;
  }
},
    resetSelections() {
      this.selectedTurno = null;
      this.selectedDoctor = null;
      this.selectedFecha = null;
    },
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES');
    },
    formatDateShort(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.getDate() + '/' + (date.getMonth() + 1);
    },
    formatDateRange(start, end) {
      return `${this.formatDate(start)} - ${this.formatDate(end)}`;
    },
    formatTime(timeString) {
      if (!timeString) return '';
      
      // Convertir formato 24h a 12h con am/pm
      const [hours, minutes] = timeString.split(':');
      const h = parseInt(hours);
      const ampm = h >= 12 ? 'pm' : 'am';
      const h12 = h % 12 || 12; // Convertir 0 a 12
      
      return `${h12}:${minutes} ${ampm}`;
    },
    esFechaHoy(fecha) {
      const hoy = new Date().toISOString().split('T')[0];
      return fecha === hoy;
    },
    showAlert(type, message) {
      this.alert = {
        show: true,
        type: type,
        message: message
      };
      
      setTimeout(() => {
        this.closeAlert();
      }, 5000);
    },
    closeAlert() {
      this.alert.show = false;
    }
  }
};
</script>

<style scoped>
.asignacion-container {
  padding: 20px;
}

.card {
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.calendar-container {
  max-height: 70vh;
  overflow-y: auto;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background-color: #fff;
}

.sticky-header th {
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.calendar-view {
  overflow: hidden;
}

.calendar-table {
  table-layout: fixed;
  margin-bottom: 0;
}

.calendar-table th, 
.calendar-table td {
  vertical-align: middle;
}

.today-column {
  background-color: #e8f4ff;
}

.time-cell {
  font-weight: 500;
  background-color: #f8f9fa;
  position: sticky;
  left: 0;
  z-index: 10;
  box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

.calendar-cell {
  height: 80px;
  padding: 0;
  border: 1px solid #dee2e6;
}

.turno-slot {
  padding: 8px;
  margin: 4px;
  background-color: #9AE6B4;
  border: 1px solid #68D391;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  font-size: 0.85rem;
  height: calc(100% - 8px);
}

.turno-slot:hover {
  background-color: #68D391;
  transform: translateY(-2px);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.turno-slot.selected {
  background-color: #38A169;
  color: white;
  border-color: #2F855A;
}

.horario {
  font-weight: 500;
  margin-bottom: 4px;
}

.doctor-name {
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Estilos para los íconos */
.bi {
  margin-right: 5px;
}

.modal {
  display: none;
  position: fixed;
  z-index: 1050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.4);
}

.modal.show {
  display: block;
}

.modal-dialog {
  margin: 10% auto;
  max-width: 500px;
}
</style>