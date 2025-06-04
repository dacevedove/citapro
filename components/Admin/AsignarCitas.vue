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
          <h2>Asignar Cita #{{ citaSeleccionada.id }}</h2>
          <button @click="cerrarModalAsignar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Especialidad:</label>
            <select v-model="asignacion.especialidad_id" @change="filtrarDoctores">
              <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                {{ esp.nombre }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Tipo de asignación:</label>
            <div class="radio-group">
              <label>
                <input type="radio" v-model="asignacion.tipo" value="doctor" />
                Asignar a un doctor específico
              </label>
              
              <label>
                <input type="radio" v-model="asignacion.tipo" value="libre" />
                Asignación libre (cualquier doctor de la especialidad)
              </label>
            </div>
          </div>
          
          <div v-if="asignacion.tipo === 'doctor'" class="form-group">
            <label>Doctor:</label>
            <select v-model="asignacion.doctor_id">
              <option value="">Seleccione un doctor</option>
              <option v-for="doctor in doctoresFiltrados" :key="doctor.id" :value="doctor.id">
                {{ doctor.nombre }} {{ doctor.apellido }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Notas adicionales:</label>
            <textarea v-model="asignacion.notas" rows="3"></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalAsignar" class="btn btn-outline">Cancelar</button>
          <button @click="asignarCita" :disabled="procesandoAsignacion" class="btn btn-primary">
            {{ procesandoAsignacion ? 'Procesando...' : 'Asignar' }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modal para cancelar cita -->
    <div v-if="mostrarModalCancelar" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>Cancelar Cita #{{ citaSeleccionada.id }}</h2>
          <button @click="cerrarModalCancelar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <p>¿Está seguro que desea cancelar esta cita?</p>
          
          <div class="form-group">
            <label>Motivo de cancelación:</label>
            <textarea v-model="motivoCancelacion" rows="3" required></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalCancelar" class="btn btn-outline">Volver</button>
          <button @click="cancelarCita" :disabled="procesandoCancelacion" class="btn btn-danger">
            {{ procesandoCancelacion ? 'Procesando...' : 'Confirmar cancelación' }}
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
      cargando: false,
      
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
        tipo: 'doctor',
        doctor_id: '',
        notas: ''
      },
      procesandoAsignacion: false,
      
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
  return diferenciaMilisegundos / (1000 * 60 * 60); // Convertir a horas
},

// Formatear tiempo transcurrido de forma amigable
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
    
    // Métodos para modal de asignación
    abrirModalAsignar(cita) {
      this.citaSeleccionada = cita;
      this.asignacion = {
        especialidad_id: cita.especialidad_id,
        tipo: 'doctor',
        doctor_id: '',
        notas: ''
      };
      
      this.filtrarDoctores();
      this.mostrarModalAsignar = true;
    },
    
    cerrarModalAsignar() {
      this.mostrarModalAsignar = false;
      this.citaSeleccionada = {};
      this.asignacion = {
        especialidad_id: '',
        tipo: 'doctor',
        doctor_id: '',
        notas: ''
      };
    },
    
    filtrarDoctores() {
      if (this.doctoresFiltrados.length > 0) {
        this.asignacion.doctor_id = this.doctoresFiltrados[0].id;
      } else {
        this.asignacion.doctor_id = '';
      }
    },
    
    async asignarCita() {
      if (this.asignacion.tipo === 'doctor' && !this.asignacion.doctor_id) {
        alert('Por favor, seleccione un doctor');
        return;
      }
      
      this.procesandoAsignacion = true;
      
      try {
        const token = localStorage.getItem('token');
        
        const payload = {
          cita_id: this.citaSeleccionada.id,
          especialidad_id: this.asignacion.especialidad_id,
          notas: this.asignacion.notas
        };
        
        if (this.asignacion.tipo === 'doctor') {
          payload.doctor_id = this.asignacion.doctor_id;
        } else {
          payload.asignacion_libre = true;
        }
        
        const response = await axios.put('/api/citas/asignar.php', payload, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.message) {
          alert('Cita asignada correctamente');
          this.cerrarModalAsignar();
          this.cargarCitas();
        }
      } catch (error) {
        console.error('Error al asignar cita:', error);
        alert('Error al asignar la cita. Por favor, intente nuevamente.');
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
.asignar-citas-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

h1 {
  margin: 0;
  color: var(--dark-color);
}

.actions-container {
  display: flex;
  gap: 10px;
}

.filter-container {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.filter-group select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
}

.btn {
  padding: 8px 16px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-outline:hover {
  background-color: #e9ecef;
}

.btn-primary {
  background-color: var(--primary-color);
  border: 1px solid var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: #0069d9;
}

.btn-danger {
  background-color: var(--danger-color);
  border: 1px solid var(--danger-color);
  color: white;
}

.btn-danger:hover {
  background-color: #c82333;
}

.citas-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.cita-card {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 15px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.cita-id {
  font-weight: 500;
  color: var(--dark-color);
}

.cita-estado {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.estado-solicitada {
  background-color: var(--warning-color);
  color: var(--dark-color);
}

.estado-asignada {
  background-color: var(--info-color);
  color: white;
}

.estado-confirmada {
  background-color: var(--success-color);
  color: white;
}

.estado-cancelada {
  background-color: var(--danger-color);
  color: white;
}

.cita-body {
  padding: 15px;
}

.cita-info h3 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.cita-especialidad {
  color: var(--primary-color);
  font-weight: 500;
  margin-bottom: 10px;
}

.cita-detalles {
  margin-bottom: 15px;
}

.cita-detalles p {
  margin: 5px 0;
}

.cita-doctor, .cita-libre {
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 4px;
}

.cita-doctor {
  background-color: #e8f4f8;
  border-left: 3px solid var(--info-color);
}

.cita-libre {
  background-color: #fff3cd;
  border-left: 3px solid var(--warning-color);
}

.cita-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 15px;
}

.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  padding: 40px;
  text-align: center;
  background-color: #f8f9fa;
  border-radius: 8px;
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
}

.modal-container {
  background-color: white;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--secondary-color);
}

.modal-body {
  padding: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group select, 
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.modal-footer {
  padding: 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #e9ecef;
}

.cita-meta {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tiempo-transcurrido {
  font-size: 13px;
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 5px;
}

.tiempo-transcurrido.alerta {
  color: #dc3545;
  font-weight: 500;
}

.tiempo-transcurrido i {
  font-size: 14px;
}

/* Actualizar este estilo para adaptarse a los cambios */
.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 15px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}
</style>