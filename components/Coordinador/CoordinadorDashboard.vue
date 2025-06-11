<template>
  <div class="coordinador-dashboard">
    <div class="dashboard-header">
      <h1>Panel de Coordinación</h1>
      <div class="header-stats">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.citasPendientes }}</div>
            <div class="stat-label">Citas Pendientes</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-user-md"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.doctoresActivos }}</div>
            <div class="stat-label">Doctores Activos</div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.horariosHoy }}</div>
            <div class="stat-label">Horarios Hoy</div>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <div class="content-row">
        <div class="card">
          <div class="card-header">
            <h3>Horarios de Hoy</h3>
            <router-link to="/coordinador/horarios" class="btn btn-outline">
              Ver Todos
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="loading.horarios" class="loading">
              <div class="spinner"></div>
              <p>Cargando horarios...</p>
            </div>
            <div v-else-if="horariosHoy.length === 0" class="empty-state">
              <p>No hay horarios programados para hoy</p>
            </div>
            <div v-else class="horarios-list">
              <div v-for="horario in horariosHoy" :key="horario.id" class="horario-item">
                <div class="horario-doctor">
                  {{ horario.doctor_nombre }}
                </div>
                <div class="horario-tiempo">
                  {{ horario.hora_inicio }} - {{ horario.hora_fin }}
                </div>
                <div class="horario-tipo" :style="{ backgroundColor: horario.color }">
                  {{ horario.tipo_nombre }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3>Citas Recientes</h3>
            <router-link to="/coordinador/citas" class="btn btn-outline">
              Ver Todas
            </router-link>
          </div>
          <div class="card-body">
            <div v-if="loading.citas" class="loading">
              <div class="spinner"></div>
              <p>Cargando citas...</p>
            </div>
            <div v-else-if="citasRecientes.length === 0" class="empty-state">
              <p>No hay citas recientes</p>
            </div>
            <div v-else class="citas-list">
              <div v-for="cita in citasRecientes" :key="cita.id" class="cita-item">
                <div class="cita-paciente">
                  {{ cita.paciente_nombre }} {{ cita.paciente_apellido }}
                </div>
                <div class="cita-especialidad">
                  {{ cita.especialidad }}
                </div>
                <div class="cita-estado" :class="`estado-${cita.estado}`">
                  {{ formatearEstado(cita.estado) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="content-row">
        <div class="card full-width">
          <div class="card-header">
            <h3>Acciones Rápidas</h3>
          </div>
          <div class="card-body">
            <div class="quick-actions">
              <router-link to="/coordinador/horarios" class="action-button">
                <i class="fas fa-calendar"></i>
                <span>Gestionar Horarios</span>
              </router-link>
              
              <router-link to="/coordinador/doctores" class="action-button">
                <i class="fas fa-user-md"></i>
                <span>Ver Doctores</span>
              </router-link>
              
              <router-link to="/coordinador/especialidades" class="action-button">
                <i class="fas fa-stethoscope"></i>
                <span>Especialidades</span>
              </router-link>
              
              <router-link to="/coordinador/tipos-bloque" class="action-button">
                <i class="fas fa-clock"></i>
                <span>Tipos de Bloque</span>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CoordinadorDashboard',
  data() {
    return {
      stats: {
        citasPendientes: 0,
        doctoresActivos: 0,
        horariosHoy: 0
      },
      horariosHoy: [],
      citasRecientes: [],
      loading: {
        horarios: false,
        citas: false
      }
    };
  },
  mounted() {
    this.cargarDatos();
  },
  methods: {
    async cargarDatos() {
      await Promise.all([
        this.cargarEstadisticas(),
        this.cargarHorariosHoy(),
        this.cargarCitasRecientes()
      ]);
    },

    async cargarEstadisticas() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/coordinador/estadisticas.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.stats = response.data;
      } catch (error) {
        console.error('Error al cargar estadísticas:', error);
      }
    },

    async cargarHorariosHoy() {
      this.loading.horarios = true;
      try {
        const token = localStorage.getItem('token');
        const hoy = new Date().toISOString().split('T')[0];
        const response = await axios.get(`/api/horarios/hoy.php?fecha=${hoy}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.horariosHoy = response.data;
      } catch (error) {
        console.error('Error al cargar horarios:', error);
      } finally {
        this.loading.horarios = false;
      }
    },

    async cargarCitasRecientes() {
      this.loading.citas = true;
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/citas/recientes.php?limit=5', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.citasRecientes = response.data;
      } catch (error) {
        console.error('Error al cargar citas:', error);
      } finally {
        this.loading.citas = false;
      }
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
    }
  }
};
</script>

<style scoped>
.coordinador-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.dashboard-header {
  margin-bottom: 30px;
}

.dashboard-header h1 {
  margin: 0 0 20px 0;
  color: var(--dark-color);
}

.header-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.stat-number {
  font-size: 24px;
  font-weight: bold;
  color: var(--dark-color);
}

.stat-label {
  font-size: 14px;
  color: var(--secondary-color);
}

.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.content-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.content-row .full-width {
  grid-column: 1 / -1;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  color: var(--dark-color);
}

.card-body {
  padding: 20px;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px 0;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  color: var(--secondary-color);
  padding: 20px 0;
}

.horarios-list, .citas-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.horario-item, .cita-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  border: 1px solid #e9ecef;
  border-radius: 4px;
}

.horario-tipo {
  padding: 4px 8px;
  border-radius: 12px;
  color: white;
  font-size: 12px;
  font-weight: 500;
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

.quick-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.action-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  text-decoration: none;
  color: var(--dark-color);
  transition: all 0.2s;
}

.action-button:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  transform: translateY(-2px);
}

.action-button i {
  font-size: 24px;
  margin-bottom: 10px;
}

.btn {
  padding: 8px 12px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-outline:hover {
  background-color: #e9ecef;
}

/* Responsive */
@media (max-width: 768px) {
  .content-row {
    grid-template-columns: 1fr;
  }
  
  .header-stats {
    grid-template-columns: 1fr;
  }
  
  .horario-item, .cita-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }
}
</style>