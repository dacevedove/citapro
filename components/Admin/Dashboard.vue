<template>
    <div class="admin-dashboard">
      <h1>Dashboard de Dirección Médica</h1>
      
      <div class="stats-container">
        <div class="stat-card">
          <h3>Citas Pendientes</h3>
          <div class="stat-value">{{ estadisticas.citasPendientes }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Citas Asignadas</h3>
          <div class="stat-value">{{ estadisticas.citasAsignadas }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Citas Confirmadas</h3>
          <div class="stat-value">{{ estadisticas.citasConfirmadas }}</div>
        </div>
        
        <div class="stat-card">
          <h3>Total Aseguradoras</h3>
          <div class="stat-value">{{ estadisticas.totalAseguradoras }}</div>
        </div>
      </div>
      
      <div class="filters-container">
        <h2>Solicitudes de citas</h2>
        
        <div class="filters">
          <div class="filter-group">
            <label>Tipo de solicitante:</label>
            <select v-model="filtros.tipoSolicitante" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option value="aseguradora">Aseguradora</option>
              <option value="direccion_medica">Dirección Médica</option>
              <option value="paciente">Paciente</option>
            </select>
          </div>
          
          <div class="filter-group" v-if="filtros.tipoSolicitante === 'aseguradora'">
            <label>Aseguradora:</label>
            <select v-model="filtros.aseguradoraId" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option v-for="aseg in aseguradoras" :key="aseg.id" :value="aseg.id">
                {{ aseg.nombre_comercial }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Estado:</label>
            <select v-model="filtros.estado" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option value="solicitada">Solicitada</option>
              <option value="asignada">Asignada</option>
              <option value="confirmada">Confirmada</option>
              <option value="cancelada">Cancelada</option>
              <option value="completada">Completada</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>Especialidad:</label>
            <select v-model="filtros.especialidadId" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                {{ esp.nombre }}
              </option>
            </select>
          </div>
          
          <button @click="resetearFiltros" class="btn btn-secondary">
            Resetear filtros
          </button>
        </div>
      </div>
      
      <div class="citas-container">
        <div v-if="cargando" class="loading">
          Cargando solicitudes de citas...
        </div>
        
        <div v-else-if="citas.length === 0" class="empty-state">
          No hay citas que coincidan con los filtros aplicados.
        </div>
        
        <div v-else class="citas-grid">
          <div v-for="cita in citas" :key="cita.id" class="cita-card">
            <div class="cita-header">
              <span class="cita-id">#{{ cita.id }}</span>
              <span :class="['cita-estado', `estado-${cita.estado}`]">
                {{ cita.estado }}
              </span>
            </div>
            
            <div class="cita-paciente">
              <h4>{{ cita.paciente_nombre }} {{ cita.paciente_apellido }}</h4>
              <p v-if="cita.paciente_telefono">
                <i class="fas fa-phone"></i> {{ cita.paciente_telefono }}
              </p>
              <p v-if="cita.paciente_email">
                <i class="fas fa-envelope"></i> {{ cita.paciente_email }}
              </p>
            </div>
            
            <div class="cita-detalles">
              <p><strong>Especialidad:</strong> {{ cita.especialidad }}</p>
              <p><strong>Solicitante:</strong> {{ formatearSolicitante(cita.tipo_solicitante) }}</p>
              <p v-if="cita.aseguradora_nombre">
                <strong>Aseguradora:</strong> {{ cita.aseguradora_nombre }}
              </p>
              <p><strong>Descripción:</strong> {{ cita.descripcion }}</p>
              <p v-if="cita.doctor_nombre">
                <strong>Doctor asignado:</strong> {{ cita.doctor_nombre }}
              </p>
              <p v-else-if="cita.asignacion_libre">
                <strong>Asignación:</strong> <span class="libre">Libre</span>
              </p>
              <p v-if="cita.fecha && cita.hora">
                <strong>Fecha y hora:</strong> {{ formatearFechaHora(cita.fecha, cita.hora) }}
              </p>
              <p v-if="cita.consultorio_nombre">
                <strong>Consultorio:</strong> {{ cita.consultorio_nombre }} ({{ cita.consultorio_ubicacion }})
              </p>
            </div>
            
            <div class="cita-acciones">
              <button 
                v-if="cita.estado === 'solicitada'" 
                @click="abrirModalAsignar(cita)" 
                class="btn btn-primary"
              >
                Asignar
              </button>
              <button 
                v-if="['solicitada', 'asignada'].includes(cita.estado)" 
                @click="abrirModalCancelar(cita)" 
                class="btn btn-danger"
              >
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal para asignar cita -->
      <div v-if="modalAsignar" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Asignar cita #{{ citaSeleccionada.id }}</h2>
            <button @click="cerrarModalAsignar" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="form-group">
              <label>Especialidad:</label>
              <select v-model="asignacion.especialidad_id" @change="cargarDoctoresPorEspecialidad" required>
                <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                  {{ esp.nombre }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Tipo de asignación:</label>
              <div class="radio-group">
                <label>
                  <input type="radio" v-model="asignacion.tipo" value="doctor" checked />
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
              <select v-model="asignacion.doctor_id" required>
                <option v-for="doc in doctoresFiltrados" :key="doc.id" :value="doc.id">
                  {{ doc.nombre }} {{ doc.apellido }}
                </option>
              </select>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalAsignar" class="btn btn-secondary">Cancelar</button>
            <button @click="asignarCita" :disabled="asignando" class="btn btn-primary">
              {{ asignando ? 'Asignando...' : 'Asignar' }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Modal para cancelar cita -->
      <div v-if="modalCancelar" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Cancelar cita #{{ citaSeleccionada.id }}</h2>
            <button @click="cerrarModalCancelar" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>¿Está seguro que desea cancelar esta cita?</p>
            <p>
              <strong>Paciente:</strong> 
              {{ citaSeleccionada.paciente_nombre }} {{ citaSeleccionada.paciente_apellido }}
            </p>
            <p><strong>Especialidad:</strong> {{ citaSeleccionada.especialidad }}</p>
            
            <div class="form-group">
              <label>Motivo de cancelación:</label>
              <textarea v-model="cancellationReason" rows="3" required></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalCancelar" class="btn btn-secondary">Volver</button>
            <button @click="cancelarCita" :disabled="cancelando" class="btn btn-danger">
              {{ cancelando ? 'Cancelando...' : 'Cancelar cita' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'AdminDashboard',
    data() {
      return {
        citas: [],
        especialidades: [],
        doctores: [],
        aseguradoras: [],
        estadisticas: {
          citasPendientes: 0,
          citasAsignadas: 0,
          citasConfirmadas: 0,
          totalAseguradoras: 0
        },
        filtros: {
          tipoSolicitante: '',
          aseguradoraId: '',
          estado: '',
          especialidadId: ''
        },
        modalAsignar: false,
        modalCancelar: false,
        citaSeleccionada: {},
        asignacion: {
          especialidad_id: '',
          tipo: 'doctor',
          doctor_id: ''
        },
        cancellationReason: '',
        cargando: false,
        asignando: false,
        cancelando: false
      }
    },
    computed: {
      doctoresFiltrados() {
        if (!this.asignacion.especialidad_id) return [];
        return this.doctores.filter(doc => doc.especialidad_id == this.asignacion.especialidad_id);
      }
    },
    mounted() {
      this.cargarDatos();
    },
    methods: {
      async cargarDatos() {
        this.cargando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          // Cargar especialidades
          const espResponse = await axios.get('https://localhost/lgm/api/doctores/especialidades.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          this.especialidades = espResponse.data;
          
          // Cargar doctores
          const docResponse = await axios.get('https://localhost/lgm/api/doctores/listar.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          this.doctores = docResponse.data;
          
          // Cargar aseguradoras
          const asegResponse = await axios.get('https://localhost/lgm/api/aseguradoras/listar.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          this.aseguradoras = asegResponse.data;
          this.estadisticas.totalAseguradoras = this.aseguradoras.length;
          
          // Cargar citas
          await this.cargarCitas();
          
          // Calcular estadísticas
          this.calcularEstadisticas();
          
        } catch (error) {
          console.error('Error al cargar datos:', error);
        } finally {
          this.cargando = false;
        }
      },
      
      async cargarCitas() {
        try {
          const token = localStorage.getItem('token');
          
          // Construir URL con filtros
          let url = 'https://localhost/lgm/api/citas/filtrar.php';
          const params = new URLSearchParams();
          
          if (this.filtros.tipoSolicitante) {
            params.append('tipo_solicitante', this.filtros.tipoSolicitante);
          }
          
          if (this.filtros.aseguradoraId) {
            params.append('aseguradora_id', this.filtros.aseguradoraId);
          }
          
          if (this.filtros.estado) {
            params.append('estado', this.filtros.estado);
          }
          
          if (this.filtros.especialidadId) {
            params.append('especialidad_id', this.filtros.especialidadId);
          }
          
          if (params.toString()) {
            url += '?' + params.toString();
          }
          
          const response = await axios.get(url, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.citas = response.data;
          
        } catch (error) {
          console.error('Error al cargar citas:', error);
        }
      },
      
      calcularEstadisticas() {
        // Calcular estadísticas basadas en las citas cargadas
        this.estadisticas.citasPendientes = this.citas.filter(c => c.estado === 'solicitada').length;
        this.estadisticas.citasAsignadas = this.citas.filter(c => c.estado === 'asignada').length;
        this.estadisticas.citasConfirmadas = this.citas.filter(c => c.estado === 'confirmada').length;
      },
      
      aplicarFiltros() {
        this.cargarCitas();
      },
      
      resetearFiltros() {
        this.filtros = {
          tipoSolicitante: '',
          aseguradoraId: '',
          estado: '',
          especialidadId: ''
        };
        this.cargarCitas();
      },
      
      formatearSolicitante(tipo) {
        switch (tipo) {
          case 'aseguradora': return 'Aseguradora';
          case 'direccion_medica': return 'Dirección Médica';
          case 'paciente': return 'Paciente';
          default: return tipo;
        }
      },
      
      formatearFechaHora(fecha, hora) {
        const fechaObj = new Date(fecha + 'T' + hora);
        return new Intl.DateTimeFormat('es', {
          dateStyle: 'medium',
          timeStyle: 'short'
        }).format(fechaObj);
      },
      
      abrirModalAsignar(cita) {
        this.citaSeleccionada = cita;
        this.asignacion = {
          especialidad_id: cita.especialidad_id,
          tipo: 'doctor',
          doctor_id: ''
        };
        this.cargarDoctoresPorEspecialidad();
        this.modalAsignar = true;
      },
      
      cerrarModalAsignar() {
        this.modalAsignar = false;
        this.citaSeleccionada = {};
        this.asignacion = {
          especialidad_id: '',
          tipo: 'doctor',
          doctor_id: ''
        };
      },
      
      abrirModalCancelar(cita) {
        this.citaSeleccionada = cita;
        this.cancellationReason = '';
        this.modalCancelar = true;
      },
      
      cerrarModalCancelar() {
        this.modalCancelar = false;
        this.citaSeleccionada = {};
        this.cancellationReason = '';
      },
      
      cargarDoctoresPorEspecialidad() {
        // Ya se tiene el computed doctoresFiltrados
        if (this.doctoresFiltrados.length > 0) {
          this.asignacion.doctor_id = this.doctoresFiltrados[0].id;
        } else {
          this.asignacion.doctor_id = '';
        }
      },
      
      async asignarCita() {
        this.asignando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          const payload = {
            cita_id: this.citaSeleccionada.id,
            especialidad_id: this.asignacion.especialidad_id
          };
          
          if (this.asignacion.tipo === 'doctor') {
            payload.doctor_id = this.asignacion.doctor_id;
          } else {
            payload.asignacion_libre = true;
          }
          
          const response = await axios.put('https://localhost/lgm/api/citas/asignar.php', payload, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            // Actualizar la lista de citas
            await this.cargarCitas();
            this.calcularEstadisticas();
            this.cerrarModalAsignar();
          }
          
        } catch (error) {
          console.error('Error al asignar cita:', error);
        } finally {
          this.asignando = false;
        }
      },
      
      async cancelarCita() {
        if (!this.cancellationReason) {
          alert('Por favor, ingrese un motivo de cancelación');
          return;
        }
        
        this.cancelando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          const response = await axios.put('https://localhost/lgm/api/citas/actualizar.php', {
            cita_id: this.citaSeleccionada.id,
            estado: 'cancelada',
            motivo_cancelacion: this.cancellationReason
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            // Actualizar la lista de citas
            await this.cargarCitas();
            this.calcularEstadisticas();
            this.cerrarModalCancelar();
          }
          
        } catch (error) {
          console.error('Error al cancelar cita:', error);
        } finally {
          this.cancelando = false;
        }
      }
    }
  }
  </script>
  
  <style scoped>
  .admin-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
  }
  
  .stats-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
  }
  
  .stat-card {
    flex: 1;
    min-width: 200px;
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
  }
  
  .stat-value {
    font-size: 32px;
    font-weight: bold;
    color: var(--primary-color);
    margin-top: 10px;
  }
  
  .filters-container {
    margin-bottom: 30px;
  }
  
  .filters {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    background-color: #f8f9fa;
    padding: 15px;
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
  
  .citas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
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
  padding: 15px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.cita-id {
  font-weight: bold;
  color: #495057;
}

.cita-estado {
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
  text-transform: uppercase;
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

.cita-paciente {
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
}

.cita-paciente h4 {
  margin: 0 0 10px 0;
  color: #343a40;
}

.cita-paciente p {
  margin: 5px 0;
  color: #6c757d;
}

.cita-detalles {
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
}

.cita-detalles p {
  margin: 8px 0;
}

.cita-acciones {
  padding: 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
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

.modal-footer {
  padding: 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #e9ecef;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.radio-group label {
  display: flex;
  align-items: center;
  gap: 8px;
}

.libre {
  background-color: #17a2b8;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background-color: #007bff;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>