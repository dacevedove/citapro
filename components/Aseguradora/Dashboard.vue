<template>
    <div class="aseguradora-dashboard">
      <h1>Panel de Control - Aseguradora</h1>
      
      <div class="stats-container">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-calendar-plus"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.citasPendientes }}</span>
            <span class="stat-label">Citas Pendientes</span>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-calendar-check"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.citasConfirmadas }}</span>
            <span class="stat-label">Citas Confirmadas</span>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-user-friends"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.totalTitulares }}</span>
            <span class="stat-label">Titulares</span>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-info">
            <span class="stat-value">{{ estadisticas.totalPacientes }}</span>
            <span class="stat-label">Pacientes</span>
          </div>
        </div>
      </div>
      
      <div class="actions-container">
        <router-link to="/aseguradora/solicitar-cita" class="action-card">
          <div class="action-icon">
            <i class="fas fa-plus-circle"></i>
          </div>
          <div class="action-info">
            <h3>Solicitar Nueva Cita</h3>
            <p>Registre una nueva solicitud de cita para un titular o beneficiario</p>
          </div>
        </router-link>
        
        <router-link to="/aseguradora/titulares" class="action-card">
          <div class="action-icon">
            <i class="fas fa-user-friends"></i>
          </div>
          <div class="action-info">
            <h3>Gestionar Titulares</h3>
            <p>Administre los titulares de pólizas asociados a su aseguradora</p>
          </div>
        </router-link>
        
        <router-link to="/aseguradora/pacientes" class="action-card">
          <div class="action-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="action-info">
            <h3>Gestionar Pacientes</h3>
            <p>Administre los pacientes beneficiarios asociados a los titulares</p>
          </div>
        </router-link>
      </div>
      
      <div class="recent-citas">
        <div class="section-header">
          <h2>Citas Recientes</h2>
          <router-link to="/aseguradora/historial" class="view-all">
            Ver todas <i class="fas fa-arrow-right"></i>
          </router-link>
        </div>
        
        <div v-if="cargando" class="loading">
          <div class="spinner"></div>
          <p>Cargando citas recientes...</p>
        </div>
        
        <div v-else-if="citasRecientes.length === 0" class="empty-state">
          <p>No hay citas recientes para mostrar.</p>
        </div>
        
        <div v-else class="citas-list">
          <div v-for="cita in citasRecientes" :key="cita.id" class="cita-card">
            <div class="cita-header">
              <span class="cita-id">Cita #{{ cita.id }}</span>
              <span :class="['cita-estado', `estado-${cita.estado}`]">{{ formatearEstado(cita.estado) }}</span>
            </div>
            
            <div class="cita-body">
              <h3>{{ cita.paciente_nombre }} {{ cita.paciente_apellido }}</h3>
              <p class="cita-especialidad">{{ cita.especialidad }}</p>
              
              <div class="cita-info">
                <p><i class="fas fa-calendar"></i> {{ formatearFecha(cita.creado_en) }}</p>
                
                <template v-if="cita.fecha && cita.hora">
                  <p><i class="fas fa-clock"></i> {{ formatearFechaHora(cita.fecha, cita.hora) }}</p>
                </template>
                
                <template v-if="cita.doctor_nombre">
                  <p><i class="fas fa-user-md"></i> Dr. {{ cita.doctor_nombre }}</p>
                </template>
              </div>
            </div>
            
            <div class="cita-footer">
              <router-link :to="`/aseguradora/cita/${cita.id}`" class="btn btn-outline">
                Ver detalles
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'AseguradoraDashboard',
    data() {
      return {
        estadisticas: {
          citasPendientes: 0,
          citasConfirmadas: 0,
          totalTitulares: 0,
          totalPacientes: 0
        },
        citasRecientes: [],
        cargando: false
      };
    },
    mounted() {
      this.cargarEstadisticas();
      this.cargarCitasRecientes();
    },
    methods: {
      async cargarEstadisticas() {
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/aseguradoras/estadisticas.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.estadisticas = response.data;
        } catch (error) {
          console.error('Error al cargar estadísticas:', error);
        }
      },
      
      async cargarCitasRecientes() {
        this.cargando = true;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/citas/listar-recientes.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.citasRecientes = response.data.slice(0, 5); // Mostrar solo las 5 más recientes
        } catch (error) {
          console.error('Error al cargar citas recientes:', error);
        } finally {
          this.cargando = false;
        }
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
      
      formatearFecha(fechaString) {
        const fecha = new Date(fechaString);
        return fecha.toLocaleDateString('es-ES', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric'
        });
      },
      
      formatearFechaHora(fecha, hora) {
        const fechaHora = new Date(`${fecha}T${hora}`);
        return fechaHora.toLocaleDateString('es-ES', {
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
  .aseguradora-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
  }
  
  h1 {
    margin-bottom: 30px;
    color: var(--dark-color);
  }
  
  /* Estilos para las tarjetas de estadísticas */
  .stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }
  
  .stat-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .stat-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    border-radius: 8px;
    margin-right: 15px;
    font-size: 24px;
  }
  
  .stat-info {
    display: flex;
    flex-direction: column;
  }
  
  .stat-value {
    font-size: 24px;
    font-weight: bold;
    color: var(--dark-color);
  }
  
  .stat-label {
    font-size: 14px;
    color: var(--secondary-color);
  }
  
  /* Estilos para las tarjetas de acciones */
  .actions-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }
  
  .action-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  
  .action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  }
  
  .action-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e8f4f8;
    color: var(--primary-color);
    border-radius: 12px;
    margin-right: 15px;
    font-size: 28px;
  }
  
  .action-info h3 {
    margin: 0 0 5px 0;
    color: var(--dark-color);
  }
  
  .action-info p {
    margin: 0;
    color: var(--secondary-color);
    font-size: 14px;
  }
  
  /* Estilos para la sección de citas recientes */
  .recent-citas {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 20px;
  }
  
  .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .section-header h2 {
    margin: 0;
    font-size: 20px;
    color: var(--dark-color);
  }
  
  .view-all {
    color: var(--primary-color);
    text-decoration: none;
  }
  
  .view-all i {
    margin-left: 5px;
  }
  
  .citas-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
  }
  
  .cita-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
  }
  
  .cita-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }
  
  .cita-id {
    font-size: 14px;
    color: var(--secondary-color);
  }
  
  .cita-estado {
    padding: 3px 8px;
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
  
  .estado-completada {
    background-color: var(--secondary-color);
    color: white;
  }
  
  .cita-body {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
  }
  
  .cita-body h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: var(--dark-color);
  }
  
  .cita-especialidad {
    color: var(--primary-color);
    font-weight: 500;
    margin-bottom: 10px;
    font-size: 14px;
  }
  
  .cita-info p {
    margin: 5px 0;
    font-size: 14px;
    color: var(--secondary-color);
  }
  
  .cita-info i {
    width: 20px;
    text-align: center;
    margin-right: 5px;
  }
  
  .cita-footer {
    padding: 10px 15px;
    text-align: right;
  }
  
  .btn {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-outline {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
    text-decoration: none;
  }
  
  .btn-outline:hover {
    background-color: var(--primary-color);
    color: white;
  }
  
  .loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px 0;
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
    padding: 30px;
    text-align: center;
    color: var(--secondary-color);
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .actions-container {
      grid-template-columns: 1fr;
    }
    
    .citas-list {
      grid-template-columns: 1fr;
    }
  }
  </style>