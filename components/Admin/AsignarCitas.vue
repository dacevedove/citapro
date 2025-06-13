<template>
  <div class="asignar-citas-container">
    <div class="header-section">
      <h1>Asignación de Citas</h1>
      <div class="actions-container">
        <crear-cita @cita-creada="cargarCitas" />
      </div>
    </div>
    
    <div class="filter-container">
      <div class="filter-group">
        <label>Filtrar por estado:</label>
        <select v-model="filtros.estado" @change="cargarCitas">
          <option value="">Todos</option>
          <option value="solicitada">Solicitadas</option>
          <option value="asignada">Asignadas</option>
          <option value="confirmada">Confirmadas</option>
          <option value="cancelada">Canceladas</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Filtrar por especialidad:</label>
        <select v-model="filtros.especialidad_id" @change="cargarCitas">
          <option value="">Todas</option>
          <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
            {{ esp.nombre }}
          </option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Filtrar por origen:</label>
        <select v-model="filtros.tipo_solicitante" @change="cargarCitas">
          <option value="">Todos</option>
          <option value="aseguradora">Aseguradora</option>
          <option value="direccion_medica">Dirección Médica</option>
          <option value="paciente">Paciente</option>
        </select>
      </div>
      
      <button @click="resetearFiltros" class="btn btn-outline">Resetear filtros</button>
    </div>
    
    <div class="citas-container">
      <div v-if="cargando" class="loading-spinner">
        <div class="spinner"></div>
        <p>Cargando citas...</p>
      </div>
      
      <div v-else-if="citas.length === 0" class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <p>No hay citas que coincidan con los filtros aplicados.</p>
      </div>
      
      <div v-else class="citas-list">
        <div v-for="cita in citas" :key="cita.id" class="cita-card">
          <div class="cita-header">
            <span class="cita-id">Cita #{{ cita.id }}</span>
            <div class="cita-meta">
              <span class="tiempo-transcurrido" :class="{ 'alerta': horasSinAsignar(cita) > 3 }">
                {{ formatearTiempoTranscurrido(cita.creado_en) }}
                <i v-if="horasSinAsignar(cita) > 3" class="fas fa-triangle-exclamation text-danger"></i>
              </span>
              <span :class="['cita-estado', `estado-${cita.estado}`]">{{ formatearEstado(cita.estado) }}</span>
            </div>
          </div>
          
          <div class="cita-body">
            <div class="cita-info">
              <h3>{{ cita.paciente_nombre }} {{ cita.paciente_apellido }}</h3>
              <p class="cita-especialidad">{{ cita.especialidad }}</p>
              
              <div class="cita-detalles">
                <p><strong>Solicitante:</strong> {{ formatearSolicitante(cita.tipo_solicitante) }}</p>
                <p><strong>Fecha solicitud:</strong> {{ formatearFecha(cita.creado_en) }}</p>
                <p><strong>Descripción:</strong> {{ cita.descripcion }}</p>
              </div>
              
              <div v-if="cita.doctor_nombre" class="cita-doctor">
                <p><strong>Doctor asignado:</strong> {{ cita.doctor_nombre }}</p>
                <p v-if="cita.fecha && cita.hora"><strong>Programada:</strong> {{ formatearFechaHora(cita.fecha, cita.hora) }}</p>
              </div>
              
              <div v-if="cita.asignacion_libre" class="cita-libre">
                <p><strong>Asignación libre para cualquier doctor de la especialidad</strong></p>
              </div>
            </div>
            
            <div class="cita-actions" v-if="cita.estado === 'solicitada'">
              <button @click="abrirModalAsignar(cita)" class="btn btn-primary">Asignar</button>
              <button @click="abrirModalCancelar(cita)" class="btn btn-danger">Cancelar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal para asignar cita -->
    <div v-if="mostrarModalAsignar" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>
            <i class="fas fa-user-md"></i>
            Asignar Cita #{{ citaSeleccionada.id }}
          </h2>
          <button @click="cerrarModalAsignar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>
              <i class="fas fa-stethoscope"></i>
              Especialidad:
            </label>
            <select v-model="asignacion.especialidad_id" @change="filtrarDoctores" class="form-control">
              <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                {{ esp.nombre }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>
              <i class="fas fa-clipboard-list"></i>
              Tipo de asignación:
            </label>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" v-model="asignacion.tipo" value="especifico" />
                <span class="radio-text">
                  <strong>Asignar a horario específico</strong>
                  <small>Selecciona doctor, fecha y hora exacta</small>
                </span>
              </label>
              
              <label class="radio-option">
                <input type="radio" v-model="asignacion.tipo" value="doctor" />
                <span class="radio-text">
                  <strong>Asignar a un doctor</strong>
                  <small>Doctor específico sin horario definido</small>
                </span>
              </label>
              
              <label class="radio-option">
                <input type="radio" v-model="asignacion.tipo" value="libre" />
                <span class="radio-text">
                  <strong>Asignación libre</strong>
                  <small>Cualquier doctor de la especialidad</small>
                </span>
              </label>
            </div>
          </div>
          
          <!-- Selección de fecha para horarios específicos -->
          <div v-if="asignacion.tipo === 'especifico'" class="form-group">
            <label>
              <i class="fas fa-calendar"></i>
              Fecha deseada:
            </label>
            <input 
              type="date" 
              v-model="asignacion.fecha" 
              @change="cargarHorariosDisponibles"
              class="form-control"
              :min="fechaMinima"
            />
          </div>
          
          <!-- Mostrar horarios disponibles -->
          <div v-if="asignacion.tipo === 'especifico' && asignacion.fecha" class="form-group">
            <label>
              <i class="fas fa-clock"></i>
              Horarios disponibles:
            </label>
            
            <div v-if="cargandoHorarios" class="loading-small">
              <div class="spinner-small"></div>
              <span>Cargando horarios disponibles...</span>
            </div>
            
            <div v-else-if="horariosDisponibles.length === 0" class="no-horarios">
              <i class="fas fa-calendar-times"></i>
              <p>No hay horarios disponibles para la fecha seleccionada</p>
              <small>Intenta con otra fecha o usa asignación general</small>
            </div>
            
            <div v-else class="horarios-disponibles">
              <div class="horarios-header">
                <span>{{ horariosDisponibles.length }} horario{{ horariosDisponibles.length !== 1 ? 's' : '' }} disponible{{ horariosDisponibles.length !== 1 ? 's' : '' }}</span>
              </div>
              <div class="horarios-grid">
                <div 
                  v-for="slot in horariosDisponibles" 
                  :key="`${slot.doctor_id}-${slot.hora}`"
                  class="horario-slot"
                  :class="{ 'selected': asignacion.slot_seleccionado === slot }"
                  @click="seleccionarSlot(slot)"
                >
                  <div class="slot-header">
                    <span class="slot-hora">{{ formatearHora(slot.hora) }}</span>
                    <span class="slot-tipo" :style="{ backgroundColor: slot.color }">
                      {{ slot.tipo_bloque }}
                    </span>
                  </div>
                  <div class="slot-doctor">
                    <i class="fas fa-user-md"></i>
                    Dr. {{ slot.doctor_nombre }}
                  </div>
                  <div class="slot-especialidad">
                    <i class="fas fa-stethoscope"></i>
                    {{ slot.especialidad_nombre }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Selección de doctor para asignación general -->
          <div v-if="asignacion.tipo === 'doctor'" class="form-group">
            <label>
              <i class="fas fa-user-md"></i>
              Doctor:
            </label>
            <select v-model="asignacion.doctor_id" class="form-control">
              <option value="">Seleccione un doctor</option>
              <option v-for="doctor in doctoresFiltrados" :key="doctor.id" :value="doctor.id">
                Dr. {{ doctor.nombre }} {{ doctor.apellido }}
              </option>
            </select>
          </div>
          
          <!-- Información de asignación libre -->
          <div v-if="asignacion.tipo === 'libre'" class="info-asignacion-libre">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              <div>
                <strong>Asignación libre activada</strong>
                <p>La cita quedará disponible para que cualquier doctor de la especialidad <strong>{{ nombreEspecialidadSeleccionada }}</strong> pueda tomarla.</p>
              </div>
            </div>
          </div>
          
          <div class="form-group">
            <label>
              <i class="fas fa-sticky-note"></i>
              Notas adicionales:
            </label>
            <textarea 
              v-model="asignacion.notas" 
              rows="3" 
              class="form-control"
              placeholder="Instrucciones especiales, observaciones, etc."
            ></textarea>
          </div>
          
          <div v-if="errorAsignacion" class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            {{ errorAsignacion }}
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalAsignar" class="btn btn-outline">
            <i class="fas fa-times"></i>
            Cancelar
          </button>
          <button 
            @click="asignarCita" 
            :disabled="procesandoAsignacion || !puedeAsignar" 
            class="btn btn-primary"
          >
            <i class="fas fa-spinner fa-spin" v-if="procesandoAsignacion"></i>
            <i class="fas fa-check" v-else></i>
            {{ procesandoAsignacion ? 'Procesando...' : 'Asignar Cita' }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modal para cancelar cita -->
    <div v-if="mostrarModalCancelar" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>
            <i class="fas fa-times-circle"></i>
            Cancelar Cita #{{ citaSeleccionada.id }}
          </h2>
          <button @click="cerrarModalCancelar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
              <strong>¿Está seguro que desea cancelar esta cita?</strong>
              <p>Esta acción no se puede deshacer. El paciente será notificado de la cancelación.</p>
            </div>
          </div>
          
          <div class="cita-resumen">
            <h4>Datos de la cita:</h4>
            <p><strong>Paciente:</strong> {{ citaSeleccionada.paciente_nombre }} {{ citaSeleccionada.paciente_apellido }}</p>
            <p><strong>Especialidad:</strong> {{ citaSeleccionada.especialidad }}</p>
            <p><strong>Solicitada:</strong> {{ formatearFecha(citaSeleccionada.creado_en) }}</p>
          </div>
          
          <div class="form-group">
            <label>
              <i class="fas fa-comment"></i>
              Motivo de cancelación: *
            </label>
            <textarea 
              v-model="motivoCancelacion" 
              rows="4" 
              required 
              class="form-control"
              placeholder="Explique el motivo de la cancelación (este mensaje será visible para el paciente y el equipo médico)"
            ></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalCancelar" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Volver
          </button>
          <button 
            @click="cancelarCita" 
            :disabled="procesandoCancelacion || !motivoCancelacion.trim()" 
            class="btn btn-danger"
          >
            <i class="fas fa-spinner fa-spin" v-if="procesandoCancelacion"></i>
            <i class="fas fa-times-circle" v-else></i>
            {{ procesandoCancelacion ? 'Procesando...' : 'Confirmar Cancelación' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import CrearCita from './CrearCita.vue';

export default {
  name: 'AsignarCitas',
  components: {
    CrearCita
  },
  data() {
    return {
      citas: [],
      especialidades: [],
      doctores: [],
      horariosDisponibles: [],
      cargando: false,
      cargandoHorarios: false,
      
      filtros: {
        estado: 'solicitada',
        especialidad_id: '',
        tipo_solicitante: ''
      },
      
      // Modal asignar cita
      mostrarModalAsignar: false,
      citaSeleccionada: {},
      asignacion: {
        especialidad_id: '',
        tipo: 'especifico',
        doctor_id: '',
        fecha: '',
        slot_seleccionado: null,
        notas: ''
      },
      procesandoAsignacion: false,
      errorAsignacion: '',
      
      // Modal cancelar cita
      mostrarModalCancelar: false,
      motivoCancelacion: '',
      procesandoCancelacion: false
    };
  },
  
  computed: {
    doctoresFiltrados() {
      if (!this.asignacion.especialidad_id) return [];
      return this.doctores.filter(doctor => doctor.especialidad_id == this.asignacion.especialidad_id);
    },
    
    fechaMinima() {
      return new Date().toISOString().split('T')[0];
    },
    
    puedeAsignar() {
      if (this.asignacion.tipo === 'especifico') {
        return this.asignacion.slot_seleccionado !== null;
      } else if (this.asignacion.tipo === 'doctor') {
        return this.asignacion.doctor_id !== '';
      } else {
        return true; // Asignación libre siempre se puede hacer
      }
    },
    
    nombreEspecialidadSeleccionada() {
      const especialidad = this.especialidades.find(esp => esp.id == this.asignacion.especialidad_id);
      return especialidad?.nombre || '';
    }
  },
  
  mounted() {
    this.cargarEspecialidades();
    this.cargarDoctores();
    this.cargarCitas();
  },
  
  methods: {
    horasSinAsignar(cita) {
      const fechaSolicitud = new Date(cita.creado_en);
      const ahora = new Date();
      const diferenciaMilisegundos = ahora - fechaSolicitud;
      return diferenciaMilisegundos / (1000 * 60 * 60);
    },

    formatearTiempoTranscurrido(fechaString) {
      const fechaSolicitud = new Date(fechaString);
      const ahora = new Date();
      const diferenciaMilisegundos = ahora - fechaSolicitud;
      
      const minutos = Math.floor(diferenciaMilisegundos / (1000 * 60));
      const horas = Math.floor(minutos / 60);
      const dias = Math.floor(horas / 24);
      
      if (dias > 0) {
        return `hace ${dias} ${dias === 1 ? 'día' : 'días'}`;
      } else if (horas > 0) {
        return `hace ${horas} ${horas === 1 ? 'hora' : 'horas'}`;
      } else if (minutos > 0) {
        return `hace ${minutos} ${minutos === 1 ? 'minuto' : 'minutos'}`;
      } else {
        return 'hace unos segundos';
      }
    },
    
    async cargarEspecialidades() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/especialidades.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.especialidades = response.data;
      } catch (error) {
        console.error('Error al cargar especialidades:', error);
      }
    },
    
    async cargarDoctores() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/listar.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.doctores = response.data;
      } catch (error) {
        console.error('Error al cargar doctores:', error);
      }
    },
    
    async cargarCitas() {
      this.cargando = true;
      
      try {
        const token = localStorage.getItem('token');
        let url = '/api/citas/filtrar.php';
        const params = new URLSearchParams();
        
        if (this.filtros.estado) {
          params.append('estado', this.filtros.estado);
        }
        
        if (this.filtros.especialidad_id) {
          params.append('especialidad_id', this.filtros.especialidad_id);
        }
        
        if (this.filtros.tipo_solicitante) {
          params.append('tipo_solicitante', this.filtros.tipo_solicitante);
        }
        
        if (params.toString()) {
          url += `?${params.toString()}`;
        }
        
        const response = await axios.get(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.citas = response.data;
      } catch (error) {
        console.error('Error al cargar citas:', error);
      } finally {
        this.cargando = false;
      }
    },
    
    async cargarHorariosDisponibles() {
      if (!this.asignacion.fecha || !this.asignacion.especialidad_id) return;
      
      this.cargandoHorarios = true;
      this.horariosDisponibles = [];
      this.asignacion.slot_seleccionado = null;
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/horarios/disponibles.php', {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            fecha: this.asignacion.fecha,
            especialidad_id: this.asignacion.especialidad_id
          }
        });
        
        this.horariosDisponibles = response.data.slots_disponibles || [];
        console.log('Horarios disponibles cargados:', this.horariosDisponibles.length);
      } catch (error) {
        console.error('Error al cargar horarios disponibles:', error);
        this.errorAsignacion = 'Error al cargar horarios disponibles';
      } finally {
        this.cargandoHorarios = false;
      }
    },
    
    seleccionarSlot(slot) {
      this.asignacion.slot_seleccionado = slot;
      this.asignacion.doctor_id = slot.doctor_id;
    },
    
    resetearFiltros() {
      this.filtros = {
        estado: 'solicitada',
        especialidad_id: '',
        tipo_solicitante: ''
      };
      this.cargarCitas();
    },
    
    formatearEstado(estado) {
      switch (estado) {
        case 'solicitada': return 'Solicitada';
        case 'asignada': return 'Asignada';
        case 'confirmada': return 'Confirmada';
        case 'cancelada': return 'Cancelada';
        case 'completada': return 'Completada';
        default: return estado;
      }
    },
    
    formatearSolicitante(tipo) {
      switch (tipo) {
        case 'aseguradora': return 'Aseguradora';
        case 'direccion_medica': return 'Dirección Médica';
        case 'paciente': return 'Paciente';
        default: return tipo;
      }
    },
    
    formatearFecha(fechaString) {
      const fecha = new Date(fechaString);
      return fecha.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
    },
    
    formatearFechaHora(fecha, hora) {
      const fechaCompleta = new Date(`${fecha} ${hora}`);
      return fechaCompleta.toLocaleDateString('es-ES', {
        weekday: 'long',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    formatearHora(hora) {
      const [h, m] = hora.split(':');
      return `${h}:${m}`;
    },
    
    // Métodos para modal de asignación
    abrirModalAsignar(cita) {
      this.citaSeleccionada = cita;
      this.asignacion = {
        especialidad_id: cita.especialidad_id,
        tipo: 'especifico',
        doctor_id: '',
        fecha: '',
        slot_seleccionado: null,
        notas: ''
      };
      
      this.horariosDisponibles = [];
      this.errorAsignacion = '';
      this.mostrarModalAsignar = true;
    },
    
    cerrarModalAsignar() {
      this.mostrarModalAsignar = false;
      this.citaSeleccionada = {};
      this.asignacion = {
        especialidad_id: '',
        tipo: 'especifico',
        doctor_id: '',
        fecha: '',
        slot_seleccionado: null,
        notas: ''
      };
      this.horariosDisponibles = [];
      this.errorAsignacion = '';
    },
    
    filtrarDoctores() {
      this.horariosDisponibles = [];
      this.asignacion.slot_seleccionado = null;
      this.asignacion.doctor_id = '';
      
      if (this.asignacion.fecha) {
        this.cargarHorariosDisponibles();
      }
    },
    
    async asignarCita() {
      if (!this.puedeAsignar) return;
      
      this.procesandoAsignacion = true;
      this.errorAsignacion = '';
      
      try {
        const token = localStorage.getItem('token');
        
        const payload = {
          cita_id: this.citaSeleccionada.id,
          especialidad_id: this.asignacion.especialidad_id,
          notas: this.asignacion.notas
        };
        
        if (this.asignacion.tipo === 'especifico' && this.asignacion.slot_seleccionado) {
          payload.doctor_id = this.asignacion.slot_seleccionado.doctor_id;
          payload.fecha = this.asignacion.fecha;
          payload.hora = this.asignacion.slot_seleccionado.hora;
        } else if (this.asignacion.tipo === 'doctor') {
          payload.doctor_id = this.asignacion.doctor_id;
        } else {
          payload.asignacion_libre = true;
        }
        
        console.log('Enviando asignación:', payload);
        
        const response = await axios.put('/api/citas/asignar.php', payload, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.success) {
          alert(response.data.message || 'Cita asignada correctamente');
          this.cerrarModalAsignar();
          this.cargarCitas();
        } else {
          this.errorAsignacion = response.data.error || 'Error desconocido';
        }
      } catch (error) {
        console.error('Error al asignar cita:', error);
        this.errorAsignacion = error.response?.data?.error || 'Error al asignar la cita';
      } finally {
        this.procesandoAsignacion = false;
      }
    },
    
    // Métodos para modal de cancelación
    abrirModalCancelar(cita) {
      this.citaSeleccionada = cita;
      this.motivoCancelacion = '';
      this.mostrarModalCancelar = true;
    },
    
    cerrarModalCancelar() {
      this.mostrarModalCancelar = false;
      this.citaSeleccionada = {};
      this.motivoCancelacion = '';
    },
    
    async cancelarCita() {
      if (!this.motivoCancelacion.trim()) {
        alert('Por favor, ingrese un motivo de cancelación');
        return;
      }
      
      this.procesandoCancelacion = true;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.put('/api/citas/actualizar.php', {
          cita_id: this.citaSeleccionada.id,
          estado: 'cancelada',
          motivo_cancelacion: this.motivoCancelacion
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.message) {
          alert('Cita cancelada correctamente');
          this.cerrarModalCancelar();
          this.cargarCitas();
        }
      } catch (error) {
        console.error('Error al cancelar cita:', error);
        alert('Error al cancelar la cita. Por favor, intente nuevamente.');
      } finally {
        this.procesandoCancelacion = false;
      }
    }
  }
};
</script>

<style scoped>
/* Variables CSS para consistencia */
:root {
  --primary-color: #007bff;
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
  --info-color: #17a2b8;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --secondary-color: #6c757d;
  --border-color: #dee2e6;
}

.asignar-citas-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-section h1 {
  margin: 0;
  color: var(--dark-color);
  font-size: 28px;
  font-weight: 600;
}

.actions-container {
  display: flex;
  gap: 12px;
}

.filter-container {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 24px;
  padding: 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--dark-color);
  font-size: 14px;
}

.filter-group select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border-color);
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.filter-group select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.btn {
  padding: 10px 16px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-outline {
  background-color: transparent;
  border: 1px solid var(--border-color);
  color: var(--dark-color);
}

.btn-outline:hover {
  background-color: var(--light-color);
  border-color: var(--secondary-color);
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-danger {
  background-color: var(--danger-color);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #c12e2a;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.citas-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
}

.citas-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 24px;
}

.cita-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.cita-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: var(--light-color);
  border-bottom: 1px solid var(--border-color);
}

.cita-id {
  font-weight: 600;
  color: var(--dark-color);
  font-size: 16px;
}

.cita-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.tiempo-transcurrido {
  font-size: 12px;
  color: var(--secondary-color);
  display: flex;
  align-items: center;
  gap: 4px;
}

.tiempo-transcurrido.alerta {
  color: var(--danger-color);
  font-weight: 500;
}

.cita-estado {
  padding: 4px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.estado-solicitada {
  background-color: rgba(255, 193, 7, 0.2);
  color: #856404;
  border: 1px solid rgba(255, 193, 7, 0.5);
}

.estado-asignada {
  background-color: rgba(23, 162, 184, 0.2);
  color: #0c5460;
  border: 1px solid rgba(23, 162, 184, 0.5);
}

.estado-confirmada {
  background-color: rgba(40, 167, 69, 0.2);
  color: #155724;
  border: 1px solid rgba(40, 167, 69, 0.5);
}

.estado-cancelada {
  background-color: rgba(220, 53, 69, 0.2);
  color: #721c24;
  border: 1px solid rgba(220, 53, 69, 0.5);
}

.cita-body {
  padding: 20px;
}

.cita-info h3 {
  margin: 0 0 8px 0;
  color: var(--dark-color);
  font-size: 18px;
  font-weight: 600;
}

.cita-especialidad {
  color: var(--primary-color);
  font-weight: 500;
  margin-bottom: 16px;
  font-size: 14px;
}

.cita-detalles {
  margin-bottom: 16px;
}

.cita-detalles p {
  margin: 6px 0;
  font-size: 14px;
  color: var(--secondary-color);
}

.cita-detalles strong {
  color: var(--dark-color);
}

.cita-doctor, .cita-libre {
  padding: 12px 16px;
  margin-bottom: 16px;
  border-radius: 8px;
  font-size: 14px;
}

.cita-doctor {
  background-color: rgba(23, 162, 184, 0.1);
  border-left: 4px solid var(--info-color);
}

.cita-libre {
  background-color: rgba(255, 193, 7, 0.1);
  border-left: 4px solid var(--warning-color);
}

.cita-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--border-color);
}

.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border-color);
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

.loading-small {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: var(--light-color);
  border-radius: 8px;
  margin: 16px 0;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid var(--border-color);
  border-top: 2px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  padding: 60px 20px;
  text-align: center;
  color: var(--secondary-color);
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 16px;
  color: var(--secondary-color);
}

.no-horarios {
  padding: 24px;
  text-align: center;
  background: rgba(255, 193, 7, 0.1);
  border-radius: 8px;
  border: 1px solid rgba(255, 193, 7, 0.3);
  margin: 16px 0;
}

.no-horarios i {
  font-size: 32px;
  color: var(--warning-color);
  margin-bottom: 12px;
}

/* Estilos para los modales */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color);
  background: var(--light-color);
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--dark-color);
  display: flex;
  align-items: center;
  gap: 10px;
}

.close-btn {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: var(--secondary-color);
  border-radius: 50%;
  transition: background-color 0.2s;
}

.close-btn:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

.modal-body {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--dark-color);
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-control {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid var(--border-color);
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.radio-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.radio-option:hover {
  border-color: var(--primary-color);
  background-color: rgba(0, 123, 255, 0.02);
}

.radio-option input[type="radio"] {
  margin-top: 2px;
}

.radio-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.radio-text strong {
  color: var(--dark-color);
  font-size: 14px;
}

.radio-text small {
  color: var(--secondary-color);
  font-size: 12px;
}

.modal-footer {
  padding: 20px 24px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: var(--light-color);
}

/* Estilos para horarios disponibles */
.horarios-disponibles {
  border: 1px solid var(--border-color);
  border-radius: 8px;
  overflow: hidden;
}

.horarios-header {
  padding: 12px 16px;
  background: var(--light-color);
  border-bottom: 1px solid var(--border-color);
  font-weight: 500;
  color: var(--dark-color);
  font-size: 14px;
}

.horarios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 12px;
  padding: 16px;
  max-height: 320px;
  overflow-y: auto;
}

.horario-slot {
  border: 2px solid var(--border-color);
  border-radius: 8px;
  padding: 16px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.horario-slot:hover {
  border-color: var(--primary-color);
  box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.horario-slot.selected {
  border-color: var(--primary-color);
  background: rgba(0, 123, 255, 0.05);
  box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}

.slot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.slot-hora {
  font-weight: 600;
  font-size: 18px;
  color: var(--dark-color);
}

.slot-tipo {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 500;
  color: white;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.slot-doctor {
  font-weight: 500;
  color: var(--dark-color);
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.slot-especialidad {
  color: var(--secondary-color);
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.info-asignacion-libre {
  margin: 16px 0;
}

.alert {
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  border: 1px solid;
}

.alert-danger {
  background-color: rgba(220, 53, 69, 0.1);
  border-color: rgba(220, 53, 69, 0.3);
  color: #721c24;
}

.alert-warning {
  background-color: rgba(255, 193, 7, 0.1);
  border-color: rgba(255, 193, 7, 0.3);
  color: #856404;
}

.alert-info {
  background-color: rgba(23, 162, 184, 0.1);
  border-color: rgba(23, 162, 184, 0.3);
  color: #0c5460;
}

.alert i {
  margin-top: 2px;
  font-size: 16px;
}

.alert div {
  flex: 1;
}

.alert p {
  margin: 4px 0 0 0;
  font-size: 14px;
}

.cita-resumen {
  background: var(--light-color);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.cita-resumen h4 {
  margin: 0 0 12px 0;
  color: var(--dark-color);
  font-size: 16px;
}

.cita-resumen p {
  margin: 6px 0;
  font-size: 14px;
  color: var(--secondary-color);
}

/* Responsive */
@media (max-width: 768px) {
  .asignar-citas-container {
    padding: 16px;
  }
  
  .filter-container {
    flex-direction: column;
    gap: 16px;
  }
  
  .filter-group {
    min-width: auto;
  }
  
  .citas-list {
    grid-template-columns: 1fr;
  }
  
  .modal-container {
    width: 95%;
    margin: 10px;
  }
  
  .horarios-grid {
    grid-template-columns: 1fr;
  }
  
  .cita-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .cita-actions {
    flex-direction: column;
  }
  
  .modal-footer {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .header-section {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .modal-body {
    padding: 16px;
  }
  
  .radio-option {
    padding: 12px;
  }
  
  .slot-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>