<template>
  <div class="doctor-dashboard">
    <h1>Dashboard del Doctor</h1>
    
    <!-- Tarjetas KPI -->
    <div class="kpi-cards">
      <div class="kpi-card">
        <div class="kpi-icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <div class="kpi-content">
          <h3>{{ citasHoy.length }}</h3>
          <p>Citas para hoy</p>
        </div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-icon">
          <i class="fas fa-user-clock"></i>
        </div>
        <div class="kpi-content">
          <h3>{{ citasAsignadas.length }}</h3>
          <p>Citas pendientes</p>
        </div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-icon">
          <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="kpi-content">
          <h3>{{ citasDisponibles.length }}</h3>
          <p>Citas disponibles</p>
        </div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="kpi-content">
          <h3>{{ totalCitasCompletadas }}</h3>
          <p>Citas completadas</p>
        </div>
      </div>
    </div>
    
    <!-- Próximas citas -->
    <div class="upcoming-section">
      <h2>Próximas Citas</h2>
      
      <div v-if="loading" class="loading">
        <i class="fas fa-spinner fa-spin"></i> Cargando citas...
      </div>
      
      <div v-else-if="proximasCitas.length === 0" class="empty-state">
        No tiene citas programadas próximas.
      </div>
      
      <div v-else class="table-responsive">
        <table class="citas-table">
          <thead>
            <tr>
              <th>Fecha y hora</th>
              <th>Paciente</th>
              <th>Especialidad</th>
              <th>Consultorio</th>
              <th>Descripción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cita in proximasCitas" :key="cita.id">
              <td>{{ formatDateTime(cita.fecha, cita.hora) }}</td>
              <td>{{ cita.paciente_nombre }} {{ cita.paciente_apellido }}</td>
              <td>{{ cita.especialidad }}</td>
              <td>{{ cita.consultorio_nombre }}</td>
              <td>{{ cita.descripcion }}</td>
              <td class="acciones">
                <button @click="verDetalle(cita)" class="btn btn-info btn-sm">
                  <i class="fas fa-eye"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Modal de detalles -->
    <div v-if="modalDetalle" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Detalles de Cita #{{ citaSeleccionada.id }}</h2>
          <button @click="cerrarModalDetalle" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="detalle-cita">
            <div class="detalle-grupo">
              <h4>Información del Paciente</h4>
              <p><strong>Nombre:</strong> {{ citaSeleccionada.paciente_nombre }} {{ citaSeleccionada.paciente_apellido }}</p>
              <p v-if="citaSeleccionada.paciente_telefono"><strong>Teléfono:</strong> {{ citaSeleccionada.paciente_telefono }}</p>
              <p v-if="citaSeleccionada.paciente_email"><strong>Email:</strong> {{ citaSeleccionada.paciente_email }}</p>
            </div>
            
            <div class="detalle-grupo">
              <h4>Detalles de la Cita</h4>
              <p><strong>Especialidad:</strong> {{ citaSeleccionada.especialidad }}</p>
              <p><strong>Fecha y hora:</strong> {{ formatDateTime(citaSeleccionada.fecha, citaSeleccionada.hora) }}</p>
              <p><strong>Consultorio:</strong> {{ citaSeleccionada.consultorio_nombre }} ({{ citaSeleccionada.consultorio_ubicacion }})</p>
              <p><strong>Descripción:</strong> {{ citaSeleccionada.descripcion || "No hay descripción" }}</p>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalDetalle" class="btn btn-secondary">Cerrar</button>
          <button @click="abrirModalCompletar(citaSeleccionada)" class="btn btn-success">Completar</button>
          <button @click="abrirModalCancelar(citaSeleccionada)" class="btn btn-danger">Cancelar</button>
        </div>
      </div>
    </div>
    
    <!-- Modal para cancelar cita -->
    <div v-if="modalCancelar" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Cancelar Cita #{{ citaSeleccionada.id }}</h2>
          <button @click="cerrarModalCancelar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <p>¿Está seguro que desea cancelar esta cita?</p>
          
          <div class="form-group">
            <label>Motivo de cancelación:</label>
            <textarea v-model="motivo" rows="3" required></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalCancelar" class="btn btn-secondary">Volver</button>
          <button @click="cancelarCita" :disabled="procesando" class="btn btn-danger">
            {{ procesando ? 'Procesando...' : 'Cancelar cita' }}
          </button>
        </div>
      </div>
    </div>
    
    <!-- Modal para completar cita -->
    <div v-if="modalCompletar" class="modal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Completar Cita #{{ citaSeleccionada.id }}</h2>
          <button @click="cerrarModalCompletar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <p>¿Confirma que esta cita ha sido completada?</p>
          
          <div class="form-group">
            <label>Observaciones:</label>
            <textarea v-model="observaciones" rows="3"></textarea>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalCompletar" class="btn btn-secondary">Cancelar</button>
          <button @click="completarCita" :disabled="procesando" class="btn btn-success">
            {{ procesando ? 'Procesando...' : 'Marcar como completada' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DoctorDashboard',
  data() {
    return {
      // Información del doctor
      doctorId: null,
      especialidadId: null,
      
      // Datos de citas
      citasAsignadas: [],
      citasDisponibles: [],
      citasProgramadas: [],
      citasHoy: [],
      totalCitasCompletadas: 0,
      proximasCitas: [],
      
      // Estado de modales
      modalDetalle: false,
      modalCancelar: false,
      modalCompletar: false,
      
      // Cita seleccionada
      citaSeleccionada: {},
      
      // Campos de formulario
      motivo: '',
      observaciones: '',
      
      // Estados de carga
      loading: false,
      procesando: false
    }
  },
  mounted() {
    this.obtenerInfoDoctor();
  },
  methods: {
    async obtenerInfoDoctor() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/perfil.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data) {
          this.doctorId = response.data.id;
          this.especialidadId = response.data.especialidad_id;
          
          // Cargar datos despues de obtener info del doctor
          this.cargarDatos();
        }
      } catch (error) {
        console.error('Error al obtener información del doctor:', error);
      }
    },
    
    async cargarDatos() {
      if (!this.doctorId) return;
      
      this.loading = true;
      
      try {
        const token = localStorage.getItem('token');
        
        // Cargar citas asignadas
        const asignadasResponse = await axios.get(`/api/citas/listar.php?doctor_id=${this.doctorId}&estado=asignada`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.citasAsignadas = asignadasResponse.data;
        
        // Cargar citas disponibles
        const disponiblesResponse = await axios.get(`/api/citas/listar.php?asignacion_libre=1&especialidad_id=${this.especialidadId}&estado=asignada`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.citasDisponibles = disponiblesResponse.data;
        
        // Cargar citas programadas
        const programadasResponse = await axios.get(`/api/citas/listar.php?doctor_id=${this.doctorId}&estado=confirmada`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.citasProgramadas = programadasResponse.data;
        
        // Calcular citas para hoy
        const hoy = new Date();
        const fechaHoy = hoy.toISOString().split('T')[0];
        this.citasHoy = this.citasProgramadas.filter(cita => cita.fecha === fechaHoy);
        
        // Calcular próximas citas (ordenadas por fecha)
        this.proximasCitas = [...this.citasProgramadas]
          .sort((a, b) => {
            // Ordenar por fecha y hora
            const fechaA = new Date(`${a.fecha}T${a.hora}`);
            const fechaB = new Date(`${b.fecha}T${b.hora}`);
            return fechaA - fechaB;
          })
          .slice(0, 5); // Mostrar solo las 5 más próximas
        
        // Cargar citas completadas (contador)
        const completadasResponse = await axios.get(`/api/citas/listar.php?doctor_id=${this.doctorId}&estado=completada`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.totalCitasCompletadas = completadasResponse.data.length;
        
      } catch (error) {
        console.error('Error al cargar datos:', error);
      } finally {
        this.loading = false;
      }
    },
    
    formatDateTime(dateString, timeString) {
      if (!dateString) return '';
      
      const dateTime = new Date(`${dateString}T${timeString || '00:00:00'}`);
      return new Intl.DateTimeFormat('es', { 
        dateStyle: 'medium',
        timeStyle: timeString ? 'short' : undefined
      }).format(dateTime);
    },
    
    // Métodos para modales
    verDetalle(cita) {
      this.citaSeleccionada = cita;
      this.modalDetalle = true;
    },
    
    cerrarModalDetalle() {
      this.modalDetalle = false;
      this.citaSeleccionada = {};
    },
    
    abrirModalCancelar(cita) {
      this.citaSeleccionada = cita;
      this.motivo = '';
      this.modalCancelar = true;
      this.modalDetalle = false;
    },
    
    cerrarModalCancelar() {
      this.modalCancelar = false;
      this.citaSeleccionada = {};
      this.motivo = '';
    },
    
    abrirModalCompletar(cita) {
      this.citaSeleccionada = cita;
      this.observaciones = '';
      this.modalCompletar = true;
      this.modalDetalle = false;
    },
    
    cerrarModalCompletar() {
      this.modalCompletar = false;
      this.citaSeleccionada = {};
      this.observaciones = '';
    },
    
    // Métodos para acciones
    async cancelarCita() {
      if (!this.motivo) {
        alert('Por favor, ingrese el motivo de cancelación');
        return;
      }
      
      this.procesando = true;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.put('/api/citas/actualizar.php', {
          cita_id: this.citaSeleccionada.id,
          estado: 'cancelada',
          motivo_cancelacion: this.motivo
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.message) {
          await this.cargarDatos();
          this.cerrarModalCancelar();
        }
      } catch (error) {
        console.error('Error al cancelar cita:', error);
        alert('Error al cancelar la cita. Por favor, intente nuevamente.');
      } finally {
        this.procesando = false;
      }
    },
    
    async completarCita() {
      this.procesando = true;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.put('/api/citas/actualizar.php', {
          cita_id: this.citaSeleccionada.id,
          estado: 'completada',
          observaciones: this.observaciones
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.message) {
          await this.cargarDatos();
          this.cerrarModalCompletar();
        }
      } catch (error) {
        console.error('Error al completar cita:', error);
        alert('Error al completar la cita. Por favor, intente nuevamente.');
      } finally {
        this.procesando = false;
      }
    }
  }
}
</script>

<style scoped>
.doctor-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  font-size: 2rem;
  margin-bottom: 1.5rem;
  color: #333;
}

h2 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  color: #444;
}

/* Tarjetas KPI */
.kpi-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.kpi-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  padding: 20px;
  display: flex;
  align-items: center;
  transition: transform 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-5px);
}

.kpi-icon {
  width: 50px;
  height: 50px;
  background-color: #f3f7ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  font-size: 1.5rem;
  color: #007bff;
}

.kpi-content h3 {
  font-size: 1.8rem;
  margin: 0;
  color: #333;
}

.kpi-content p {
  margin: 5px 0 0;
  color: #6c757d;
  font-size: 0.9rem;
}

/* Sección de próximas citas */
.upcoming-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  padding: 20px;
  margin-bottom: 30px;
}

.table-responsive {
  overflow-x: auto;
}

.citas-table {
  width: 100%;
  border-collapse: collapse;
}

.citas-table th,
.citas-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.citas-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #495057;
}

.citas-table tbody tr:hover {
  background-color: #f5f5f5;
}

.acciones {
  text-align: center;
}

/* Estado vacío y carga */
.loading, .empty-state {
  padding: 40px;
  text-align: center;
  color: #6c757d;
  background-color: #f8f9fa;
  border-radius: 8px;
}

/* Modales */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.3rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
}

.modal-body {
  padding: 20px;
}

.detalle-cita {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detalle-grupo h4 {
  margin-top: 0;
  margin-bottom: 10px;
  color: #495057;
  font-size: 1.1rem;
}

.detalle-grupo p {
  margin: 5px 0;
  color: #6c757d;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #495057;
}

.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 0.95rem;
}

.modal-footer {
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #e9ecef;
}

/* Botones */
.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-sm {
  padding: 5px 10px;
  font-size: 0.85rem;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-info {
  background-color: #17a2b8;
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.btn-success {
  background-color: #28a745;
  color: white;
}

.btn:hover {
  opacity: 0.9;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>