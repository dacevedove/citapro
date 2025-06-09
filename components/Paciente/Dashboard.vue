<template>
    <div class="paciente-dashboard">
      <h1>Mis citas médicas</h1>
      
      <div class="action-bar">
        <button @click="$router.push('/paciente/solicitar-cita')" class="btn btn-primary">
          <i class="fas fa-plus"></i> Solicitar nueva cita
        </button>
      </div>
      
      <div class="tabs">
        <button 
          :class="['tab-btn', { active: activeTab === 'pending' }]"
          @click="activeTab = 'pending'"
        >
          Próximas citas
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'past' }]"
          @click="activeTab = 'past'"
        >
          Historial de citas
        </button>
      </div>
      
      <div class="dashboard-content">
        <!-- Citas próximas -->
        <div v-if="activeTab === 'pending'" class="tab-content">
          <div v-if="loading" class="loading">
            Cargando citas...
          </div>
          
          <div v-else-if="citasPendientes.length === 0" class="empty-state">
            No tiene citas próximas programadas.
            <p>
              <button @click="$router.push('/paciente/solicitar-cita')" class="btn btn-outline-primary">
                Solicitar una cita
              </button>
            </p>
          </div>
          
          <div v-else class="citas-list">
            <div v-for="cita in citasPendientes" :key="cita.id" class="cita-card">
              <div class="cita-header">
                <div class="cita-estado-container">
                  <span :class="['cita-estado', `estado-${cita.estado}`]">
                    {{ formatearEstado(cita.estado) }}
                  </span>
                </div>
                <div class="cita-fecha" v-if="cita.fecha && cita.hora">
                  {{ formatearFechaHora(cita.fecha, cita.hora) }}
                </div>
              </div>
              
              <div class="cita-body">
                <h3>{{ cita.especialidad }}</h3>
                
                <div class="cita-info">
                  <div v-if="cita.estado === 'confirmada'" class="cita-doctor">
                    <p><strong>Doctor:</strong> {{ cita.doctor_nombre }}</p>
                    <p><strong>Consultorio:</strong> {{ cita.consultorio_nombre }} ({{ cita.consultorio_ubicacion }})</p>
                  </div>
                  
                  <div class="cita-descripcion">
                    <p>{{ cita.descripcion }}</p>
                  </div>
                </div>
              </div>
              
              <div class="cita-footer">
                <button 
                  v-if="cita.estado === 'solicitada' || cita.estado === 'asignada'"
                  @click="abrirModalCancelar(cita)" 
                  class="btn btn-sm btn-danger"
                >
                  Cancelar cita
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Historial de citas -->
        <div v-if="activeTab === 'past'" class="tab-content">
          <div v-if="loading" class="loading">
            Cargando historial...
          </div>
          
          <div v-else-if="citasHistorial.length === 0" class="empty-state">
            No tiene historial de citas anteriores.
          </div>
          
          <div v-else class="citas-list">
            <div v-for="cita in citasHistorial" :key="cita.id" class="cita-card">
              <div class="cita-header">
                <div class="cita-estado-container">
                  <span :class="['cita-estado', `estado-${cita.estado}`]">
                    {{ formatearEstado(cita.estado) }}
                  </span>
                </div>
                <div class="cita-fecha" v-if="cita.fecha && cita.hora">
                  {{ formatearFechaHora(cita.fecha, cita.hora) }}
                </div>
              </div>
              
              <div class="cita-body">
                <h3>{{ cita.especialidad }}</h3>
                
                <div class="cita-info">
                  <div v-if="cita.doctor_nombre" class="cita-doctor">
                    <p><strong>Doctor:</strong> {{ cita.doctor_nombre }}</p>
                  </div>
                  
                  <div class="cita-descripcion">
                    <p>{{ cita.descripcion }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal para cancelar cita -->
      <div v-if="modalCancelar" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Cancelar cita</h2>
            <button @click="cerrarModalCancelar" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>¿Está seguro que desea cancelar esta cita?</p>
            
            <div v-if="citaSeleccionada.fecha && citaSeleccionada.hora" class="alert alert-warning">
              <p>
                <strong>Advertencia:</strong> Esta cita está programada para el 
                {{ formatearFechaHora(citaSeleccionada.fecha, citaSeleccionada.hora) }}.
              </p>
            </div>
            
            <div class="form-group">
              <label>Motivo de cancelación:</label>
              <textarea v-model="motivoCancelacion" rows="3" required></textarea>
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
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'PacienteDashboard',
    data() {
      return {
        activeTab: 'pending',
        citasPendientes: [],
        citasHistorial: [],
        loading: false,
        modalCancelar: false,
        citaSeleccionada: {},
        motivoCancelacion: '',
        procesando: false
      }
    },
    mounted() {
      this.cargarCitas();
    },
    watch: {
      activeTab() {
        this.cargarCitas();
      }
    },
    methods: {
      async cargarCitas() {
        this.loading = true;
        
        try {
          const token = localStorage.getItem('token');
          
          if (this.activeTab === 'pending') {
            const response = await axios.get('/api/citas/listar-paciente.php?tipo=pendientes', {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            
            this.citasPendientes = response.data;
          } else {
            const response = await axios.get('/api/citas/listar-paciente.php?tipo=historial', {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            
            this.citasHistorial = response.data;
          }
        } catch (error) {
          console.error('Error al cargar citas:', error);
        } finally {
          this.loading = false;
        }
      },
      
      formatearEstado(estado) {
        switch (estado) {
          case 'solicitada': return 'Pendiente de asignación';
          case 'asignada': return 'Asignada, pendiente de confirmación';
          case 'confirmada': return 'Confirmada';
          case 'cancelada': return 'Cancelada';
          case 'completada': return 'Completada';
          default: return estado;
        }
      },
      
      formatearFechaHora(fecha, hora) {
        const fechaObj = new Date(`${fecha}T${hora}`);
        return new Intl.DateTimeFormat('es', {
          dateStyle: 'medium',
          timeStyle: 'short'
        }).format(fechaObj);
      },
      
      abrirModalCancelar(cita) {
        this.citaSeleccionada = cita;
        this.motivoCancelacion = '';
        this.modalCancelar = true;
      },
      
      cerrarModalCancelar() {
        this.modalCancelar = false;
        this.citaSeleccionada = {};
        this.motivoCancelacion = '';
      },
      
      async cancelarCita() {
        if (!this.motivoCancelacion) {
          alert('Por favor, ingrese un motivo de cancelación');
          return;
        }
        
        this.procesando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          const response = await axios.put('/api/citas/cancelar-paciente.php', {
            cita_id: this.citaSeleccionada.id,
            motivo_cancelacion: this.motivoCancelacion
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            await this.cargarCitas();
            this.cerrarModalCancelar();
          }
        } catch (error) {
          console.error('Error al cancelar cita:', error);
          alert('Error al cancelar la cita. Por favor, intente nuevamente.');
        } finally {
          this.procesando = false;
        }
      }
    }
  }
  </script>
  
  <style scoped>
  .paciente-dashboard {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
  }
  
  .action-bar {
    margin-bottom: 20px;
    display: flex;
    justify-content: flex-end;
  }
  
  .tabs {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
  }
  
  .tab-btn {
    padding: 10px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6c757d;
  }
  
  .tab-btn.active {
    color: #007bff;
    border-bottom: 3px solid #007bff;
  }
  
  .citas-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }
  
  .cita-card {
    background-color: #ffffff;
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
  
  .cita-estado {
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
  }
  
  .estado-solicitada {
    background-color: #ffc107;
    color: #212529;
  }
  
  .estado-asignada {
    background-color: #17a2b8;
    color: white;
  }
  
  .estado-confirmada {
    background-color: #28a745;
    color: white;
  }
  
  .estado-cancelada {
    background-color: #dc3545;
    color: white;
  }
  
  .estado-completada {
    background-color: #6c757d;
    color: white;
  }
  
  .cita-fecha {
    font-weight: 500;
    color: #495057;
  }
  
  .cita-body {
    padding: 15px;
  }
  
  .cita-body h3 {
    margin: 0 0 15px 0;
    color: #343a40;
  }
  
  .cita-info {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }
  
  .cita-doctor {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
  }
  
  .cita-doctor p {
    margin: 5px 0;
  }
  
  .cita-descripcion {
    color: #6c757d;
  }
  
  .cita-footer {
    padding: 15px;
    border-top: 1px solid #e9ecef;
    display: flex;
    justify-content: flex-end;
  }
  
  .loading, .empty-state {
    padding: 40px;
    text-align: center;
    color: #6c757d;
    background-color: #f8f9fa;
    border-radius: 8px;
  }
  
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
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }
  
  .modal-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e9ecef;
  }
  
  .modal-header h2 {
    margin: 0;
    font-size: 20px;
  }
  
  .close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
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
  
  .form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
  }
  
  .modal-footer {
    padding: 15px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e9ecef;
  }
  
  .alert {
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 15px;
  }
  
  .alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
  }
  
  .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
  }
  
  .btn-sm {
    padding: 5px 10px;
    font-size: 12px;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
  }
  
  .btn-outline-primary {
    background-color: transparent;
    color: #007bff;
    border: 1px solid #007bff;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-danger {
    background-color: #dc3545;
    color: white;
  }
  
  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  </style>