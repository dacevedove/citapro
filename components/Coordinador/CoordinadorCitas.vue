// Archivo: components/Coordinador/CoordinadorCitas.vue
<template>
  <div class="coordinador-citas">
    <div class="header-section">
      <h1>Gestión de Citas - Coordinación</h1>
      <div class="header-actions">
        <button @click="cargarCitas" class="btn btn-outline">
          <i class="fas fa-sync"></i> Actualizar
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filters-section">
      <div class="filter-group">
        <label>Estado:</label>
        <select v-model="filtros.estado" @change="aplicarFiltros">
          <option value="">Todos</option>
          <option value="solicitada">Solicitadas</option>
          <option value="asignada">Asignadas</option>
          <option value="confirmada">Confirmadas</option>
          <option value="completada">Completadas</option>
          <option value="cancelada">Canceladas</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Doctor:</label>
        <select v-model="filtros.doctor_id" @change="aplicarFiltros">
          <option value="">Todos</option>
          <option v-for="doctor in doctores" :key="doctor.id" :value="doctor.id">
            {{ doctor.nombre }} {{ doctor.apellido }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label>Fecha desde:</label>
        <input 
          type="date" 
          v-model="filtros.fecha_desde" 
          @change="aplicarFiltros"
          class="form-control"
        />
      </div>

      <div class="filter-group">
        <label>Fecha hasta:</label>
        <input 
          type="date" 
          v-model="filtros.fecha_hasta" 
          @change="aplicarFiltros"
          class="form-control"
        />
      </div>
    </div>

    <!-- Lista de citas -->
    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando citas...</p>
    </div>

    <div v-else-if="citas.length === 0" class="empty-state">
      <i class="fas fa-calendar-times"></i>
      <p>No se encontraron citas con los filtros aplicados</p>
    </div>

    <div v-else class="citas-container">
      <div class="citas-grid">
        <div v-for="cita in citas" :key="cita.id" class="cita-card">
          <div class="cita-header">
            <span class="cita-id">Cita #{{ cita.id }}</span>
            <span class="cita-fecha">{{ formatearFecha(cita.creado_en) }}</span>
            <span :class="`cita-estado estado-${cita.estado}`">
              {{ formatearEstado(cita.estado) }}
            </span>
          </div>

          <div class="cita-body">
            <div class="cita-paciente">
              <h3>{{ cita.paciente_nombre }} {{ cita.paciente_apellido }}</h3>
              <p>{{ cita.especialidad }}</p>
            </div>

            <div v-if="cita.doctor_nombre" class="cita-doctor">
              <p><strong>Doctor:</strong> {{ cita.doctor_nombre }}</p>
            </div>

            <div v-if="cita.fecha && cita.hora" class="cita-programada">
              <p><strong>Programada:</strong> {{ formatearFechaHora(cita.fecha, cita.hora) }}</p>
            </div>

            <div class="cita-descripcion">
              <p>{{ cita.descripcion }}</p>
            </div>
          </div>

          <div class="cita-actions">
            <button 
              v-if="cita.estado === 'solicitada'"
              @click="asignarCita(cita)"
              class="btn btn-primary btn-sm"
            >
              Asignar
            </button>
            
            <button 
              v-if="cita.estado === 'asignada'"
              @click="confirmarCita(cita)"
              class="btn btn-success btn-sm"
            >
              Confirmar
            </button>
            
            <button 
              @click="verDetalles(cita)"
              class="btn btn-outline btn-sm"
            >
              Detalles
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de detalles -->
    <div v-if="showModalDetalles" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>Detalles de Cita #{{ citaSeleccionada.id }}</h2>
          <button @click="cerrarModalDetalles" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="detalles-grid">
            <div class="detalle-item">
              <label>Paciente:</label>
              <span>{{ citaSeleccionada.paciente_nombre }} {{ citaSeleccionada.paciente_apellido }}</span>
            </div>

            <div class="detalle-item">
              <label>Teléfono:</label>
              <span>{{ citaSeleccionada.paciente_telefono || 'No disponible' }}</span>
            </div>

            <div class="detalle-item">
              <label>Email:</label>
              <span>{{ citaSeleccionada.paciente_email || 'No disponible' }}</span>
            </div>

            <div class="detalle-item">
              <label>Especialidad:</label>
              <span>{{ citaSeleccionada.especialidad }}</span>
            </div>

            <div v-if="citaSeleccionada.doctor_nombre" class="detalle-item">
              <label>Doctor:</label>
              <span>{{ citaSeleccionada.doctor_nombre }}</span>
            </div>

            <div v-if="citaSeleccionada.consultorio_nombre" class="detalle-item">
              <label>Consultorio:</label>
              <span>{{ citaSeleccionada.consultorio_nombre }}</span>
            </div>

            <div class="detalle-item">
              <label>Estado:</label>
              <span :class="`estado-badge estado-${citaSeleccionada.estado}`">
                {{ formatearEstado(citaSeleccionada.estado) }}
              </span>
            </div>

            <div class="detalle-item full-width">
              <label>Descripción:</label>
              <p>{{ citaSeleccionada.descripcion }}</p>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="cerrarModalDetalles" class="btn btn-primary">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CoordinadorCitas',
  data() {
    return {
      citas: [],
      doctores: [],
      loading: false,
      showModalDetalles: false,
      citaSeleccionada: {},
      
      filtros: {
        estado: '',
        doctor_id: '',
        fecha_desde: '',
        fecha_hasta: ''
      }
    };
  },
  mounted() {
    this.cargarDoctores();
    this.cargarCitas();
  },
  methods: {
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
      this.loading = true;
      try {
        const token = localStorage.getItem('token');
        let url = '/api/citas/listar.php';
        
        const params = new URLSearchParams();
        if (this.filtros.estado) params.append('estado', this.filtros.estado);
        if (this.filtros.doctor_id) params.append('doctor_id', this.filtros.doctor_id);
        if (this.filtros.fecha_desde) params.append('fecha_desde', this.filtros.fecha_desde);
        if (this.filtros.fecha_hasta) params.append('fecha_hasta', this.filtros.fecha_hasta);
        
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
        this.loading = false;
      }
    },

    aplicarFiltros() {
      this.cargarCitas();
    },

    asignarCita(cita) {
      this.$router.push({
        name: 'AdminCitas',
        query: { cita_id: cita.id }
      });
    },

    async confirmarCita(cita) {
      if (!confirm('¿Confirmar esta cita?')) return;

      try {
        const token = localStorage.getItem('token');
        await axios.put('/api/citas/actualizar.php', {
          cita_id: cita.id,
          estado: 'confirmada'
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });

        await this.cargarCitas();
      } catch (error) {
        console.error('Error al confirmar cita:', error);
        alert('Error al confirmar la cita');
      }
    },

    verDetalles(cita) {
      this.citaSeleccionada = cita;
      this.showModalDetalles = true;
    },

    cerrarModalDetalles() {
      this.showModalDetalles = false;
      this.citaSeleccionada = {};
    },

    formatearEstado(estado) {
      const estados = {
        'solicitada': 'Solicitada',
        'asignada': 'Asignada', 
        'confirmada': 'Confirmada',
        'completada': 'Completada',
        'cancelada': 'Cancelada'
      };
      return estados[estado] || estado;
    },

    formatearFecha(fechaString) {
      const fecha = new Date(fechaString);
      return fecha.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
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
    }
  }
};
</script>

<style scoped>
.coordinador-citas {
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

.header-section h1 {
  margin: 0;
  color: var(--dark-color);
}

.filters-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  margin-bottom: 5px;
  font-weight: 500;
  color: var(--dark-color);
}

.form-control, select {
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 14px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 50px 0;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 50px 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.empty-state i {
  font-size: 48px;
  color: var(--secondary-color);
  margin-bottom: 15px;
}

.citas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.cita-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s;
}

.cita-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  flex-wrap: wrap;
  gap: 10px;
}

.cita-id {
  font-weight: 500;
  color: var(--dark-color);
}

.cita-fecha {
  font-size: 12px;
  color: var(--secondary-color);
}

.cita-estado {
  padding: 4px 8px;
  border-radius: 12px;
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

.estado-completada {
  background-color: var(--primary-color);
  color: white;
}

.estado-cancelada {
  background-color: var(--danger-color);
  color: white;
}

.cita-body {
  padding: 15px;
}

.cita-paciente h3 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.cita-paciente p {
  margin: 0 0 10px 0;
  color: var(--primary-color);
  font-weight: 500;
}

.cita-doctor, .cita-programada {
  margin-bottom: 10px;
}

.cita-descripcion {
  margin-bottom: 15px;
  padding: 10px;
  background-color: #f8f9fa;
  border-radius: 4px;
  border-left: 3px solid var(--primary-color);
}

.cita-actions {
  display: flex;
  gap: 10px;
  padding: 15px;
  border-top: 1px solid #e9ecef;
}

.btn {
  padding: 8px 12px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  border: none;
  font-size: 14px;
}

.btn-sm {
  padding: 6px 10px;
  font-size: 12px;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-success {
  background-color: var(--success-color);
  color: white;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

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
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
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
  padding: 20px;
}

.modal-footer {
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #e9ecef;
}

.detalles-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.detalle-item {
  display: flex;
  flex-direction: column;
}

.detalle-item.full-width {
  grid-column: 1 / -1;
}

.detalle-item label {
  font-weight: 500;
  color: var(--dark-color);
  margin-bottom: 5px;
}

.detalle-item span, .detalle-item p {
  color: var(--secondary-color);
}

.estado-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
  width: fit-content;
}

@media (max-width: 768px) {
  .filters-section {
    grid-template-columns: 1fr;
  }
  
  .citas-grid {
    grid-template-columns: 1fr;
  }
  
  .cita-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .cita-actions {
    flex-direction: column;
  }
  
  .detalles-grid {
    grid-template-columns: 1fr;
  }
}
</style>