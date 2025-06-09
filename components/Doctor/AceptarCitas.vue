<template>
    <div class="aceptar-citas">
      <h1>Gestión de Citas</h1>
      
      <div class="tabs">
        <button 
          :class="['tab-btn', { active: activeTab === 'assigned' }]" 
          @click="activeTab = 'assigned'"
        >
          Citas asignadas a mí
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'available' }]" 
          @click="activeTab = 'available'"
        >
          Citas de libre asignación
        </button>
      </div>
      
      <!-- Citas asignadas -->
      <div v-if="activeTab === 'assigned'" class="tab-content">
        <h2>Citas asignadas para mi aprobación</h2>
        
        <div v-if="loading" class="loading">
          <i class="fas fa-spinner fa-spin"></i> Cargando citas asignadas...
        </div>
        
        <div v-else-if="citasAsignadas.length === 0" class="empty-state">
          <i class="fas fa-clipboard-check"></i>
          <p>No tiene citas asignadas pendientes de confirmación</p>
        </div>
        
        <div v-else class="citas-grid">
          <div v-for="cita in citasAsignadas" :key="cita.id" class="cita-card">
            <div class="cita-header">
              <span class="cita-id">#{{ cita.id }}</span>
              <span class="cita-estado estado-asignada">Asignada</span>
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
              <p><strong>Descripción:</strong> {{ cita.descripcion || "Sin descripción" }}</p>
              <p><strong>Fecha solicitud:</strong> {{ formatDate(cita.creado_en) }}</p>
            </div>
            
            <div class="cita-acciones">
              <button @click="abrirModalProgramar(cita)" class="btn btn-primary">
                <i class="fas fa-calendar-check"></i> Programar
              </button>
              <button @click="abrirModalRechazar(cita)" class="btn btn-danger">
                <i class="fas fa-times"></i> Rechazar
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Citas disponibles para asignación libre -->
      <div v-if="activeTab === 'available'" class="tab-content">
        <h2>Citas disponibles para asignación libre</h2>
        
        <div v-if="loading" class="loading">
          <i class="fas fa-spinner fa-spin"></i> Cargando citas disponibles...
        </div>
        
        <div v-else-if="citasDisponibles.length === 0" class="empty-state">
          <i class="fas fa-calendar-alt"></i>
          <p>No hay citas disponibles para asignación libre en su especialidad</p>
        </div>
        
        <div v-else class="citas-grid">
          <div v-for="cita in citasDisponibles" :key="cita.id" class="cita-card">
            <div class="cita-header">
              <span class="cita-id">#{{ cita.id }}</span>
              <span class="cita-estado estado-disponible">Disponible</span>
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
              <p><strong>Descripción:</strong> {{ cita.descripcion || "Sin descripción" }}</p>
              <p><strong>Fecha solicitud:</strong> {{ formatDate(cita.creado_en) }}</p>
            </div>
            
            <div class="cita-acciones">
              <button @click="abrirModalTomar(cita)" class="btn btn-success">
                <i class="fas fa-hand-pointer"></i> Tomar cita
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal para programar cita -->
      <div v-if="modalProgramar" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Programar cita #{{ citaSeleccionada.id }}</h2>
            <button @click="cerrarModalProgramar" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="form-group">
              <label>Fecha:</label>
              <input type="date" v-model="programacion.fecha" required />
            </div>
            
            <div class="form-group">
              <label>Hora:</label>
              <input type="time" v-model="programacion.hora" required />
            </div>
            
            <div class="form-group">
              <label>Consultorio:</label>
              <select v-model="programacion.consultorio_id" required>
                <option v-for="cons in consultorios" :key="cons.id" :value="cons.id">
                  {{ cons.nombre }} ({{ cons.ubicacion }})
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Notas adicionales:</label>
              <textarea v-model="programacion.notas" rows="3"></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalProgramar" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button @click="programarCita" :disabled="procesando" class="btn btn-primary">
              <i class="fas fa-check"></i> {{ procesando ? 'Procesando...' : 'Confirmar cita' }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Modal para rechazar cita -->
      <div v-if="modalRechazar" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Rechazar cita #{{ citaSeleccionada.id }}</h2>
            <button @click="cerrarModalRechazar" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>¿Está seguro que desea rechazar esta cita?</p>
            
            <div class="form-group">
              <label>Motivo del rechazo:</label>
              <textarea v-model="motivo" rows="3" required></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalRechazar" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> Cancelar
            </button>
            <button @click="rechazarCita" :disabled="procesando" class="btn btn-danger">
              <i class="fas fa-times"></i> {{ procesando ? 'Procesando...' : 'Rechazar' }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Modal para tomar cita -->
      <div v-if="modalTomar" class="modal">
        <div class="modal-content">
          <div class="modal-header">
            <h2>Tomar cita #{{ citaSeleccionada.id }}</h2>
            <button @click="cerrarModalTomar" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>¿Desea tomar esta cita y programarla?</p>
            
            <div class="form-group">
              <label>Fecha:</label>
              <input type="date" v-model="programacion.fecha" required />
            </div>
            
            <div class="form-group">
              <label>Hora:</label>
              <input type="time" v-model="programacion.hora" required />
            </div>
            
            <div class="form-group">
              <label>Consultorio:</label>
              <select v-model="programacion.consultorio_id" required>
                <option v-for="cons in consultorios" :key="cons.id" :value="cons.id">
                  {{ cons.nombre }} ({{ cons.ubicacion }})
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label>Notas adicionales:</label>
              <textarea v-model="programacion.notas" rows="3"></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalTomar" class="btn btn-secondary">
              <i class="fas fa-times"></i> Cancelar
            </button>
            <button @click="tomarCita" :disabled="procesando" class="btn btn-success">
              <i class="fas fa-check"></i> {{ procesando ? 'Procesando...' : 'Tomar y programar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'AceptarCitas',
    data() {
      return {
        activeTab: 'assigned',
        citasAsignadas: [],
        citasDisponibles: [],
        consultorios: [],
        loading: false,
        procesando: false,
        
        // Información del doctor
        doctorId: null,
        especialidadId: null,
        
        // Modales
        modalProgramar: false,
        modalRechazar: false,
        modalTomar: false,
        
        // Cita seleccionada
        citaSeleccionada: {},
        
        // Datos de programación
        programacion: {
          fecha: '',
          hora: '',
          consultorio_id: '',
          notas: ''
        },
        
        // Motivo
        motivo: ''
      }
    },
    mounted() {
      this.obtenerInfoDoctor();
      this.cargarConsultorios();
    },
    watch: {
      activeTab() {
        this.cargarCitas();
      }
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
            
            // Cargar citas después de obtener la información del doctor
            this.cargarCitas();
          }
        } catch (error) {
          console.error('Error al obtener información del doctor:', error);
        }
      },
      
      async cargarConsultorios() {
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/consultorios/listar.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.consultorios = response.data;
          
          if (this.consultorios.length > 0) {
            this.programacion.consultorio_id = this.consultorios[0].id;
          }
        } catch (error) {
          console.error('Error al cargar consultorios:', error);
        }
      },
      
      async cargarCitas() {
        if (!this.doctorId) return;
        
        this.loading = true;
        
        try {
          const token = localStorage.getItem('token');
          
          if (this.activeTab === 'assigned') {
            // Cargar citas asignadas al doctor
            const asignadasResponse = await axios.get(`/api/citas/listar.php?doctor_id=${this.doctorId}&estado=asignada`, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            this.citasAsignadas = asignadasResponse.data;
          } else {
            // Cargar citas de libre asignación para la especialidad del doctor
            const disponiblesResponse = await axios.get(`/api/citas/listar.php?asignacion_libre=1&especialidad_id=${this.especialidadId}&estado=solicitada`, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            this.citasDisponibles = disponiblesResponse.data;
          }
        } catch (error) {
          console.error(`Error al cargar citas (${this.activeTab}):`, error);
        } finally {
          this.loading = false;
        }
      },
      
      formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('es', { 
          dateStyle: 'medium',
          timeStyle: 'short'
        }).format(date);
      },
      
      // Métodos para modales
      abrirModalProgramar(cita) {
        this.citaSeleccionada = cita;
        
        // Establecer fecha mínima como hoy
        const hoy = new Date();
        const fechaMin = hoy.toISOString().split('T')[0];
        
        // Inicializar programación con valores predeterminados
        this.programacion = {
          fecha: fechaMin,
          hora: '09:00',
          consultorio_id: this.consultorios.length > 0 ? this.consultorios[0].id : '',
          notas: ''
        };
        
        this.modalProgramar = true;
      },
      
      cerrarModalProgramar() {
        this.modalProgramar = false;
        this.citaSeleccionada = {};
        this.programacion = {
          fecha: '',
          hora: '',
          consultorio_id: '',
          notas: ''
        };
      },
      
      abrirModalRechazar(cita) {
        this.citaSeleccionada = cita;
        this.motivo = '';
        this.modalRechazar = true;
      },
      
      cerrarModalRechazar() {
        this.modalRechazar = false;
        this.citaSeleccionada = {};
        this.motivo = '';
      },
      
      abrirModalTomar(cita) {
        this.citaSeleccionada = cita;
        
        // Establecer fecha mínima como hoy
        const hoy = new Date();
        const fechaMin = hoy.toISOString().split('T')[0];
        
        // Inicializar programación con valores predeterminados
        this.programacion = {
          fecha: fechaMin,
          hora: '09:00',
          consultorio_id: this.consultorios.length > 0 ? this.consultorios[0].id : '',
          notas: ''
        };
        
        this.modalTomar = true;
      },
      
      cerrarModalTomar() {
        this.modalTomar = false;
        this.citaSeleccionada = {};
        this.programacion = {
          fecha: '',
          hora: '',
          consultorio_id: '',
          notas: ''
        };
      },
      
      // Métodos para acciones
      async programarCita() {
        if (!this.programacion.fecha || !this.programacion.hora || !this.programacion.consultorio_id) {
          alert('Por favor, complete todos los campos obligatorios');
          return;
        }
        
        this.procesando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          const response = await axios.put('/api/citas/actualizar.php', {
            cita_id: this.citaSeleccionada.id,
            fecha: this.programacion.fecha,
            hora: this.programacion.hora,
            consultorio_id: this.programacion.consultorio_id,
            notas: this.programacion.notas,
            estado: 'confirmada'
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            await this.cargarCitas();
            this.cerrarModalProgramar();
            this.$emit('cita-actualizada');
          }
        } catch (error) {
          console.error('Error al programar cita:', error);
          alert('Error al programar la cita. Por favor, intente nuevamente.');
        } finally {
          this.procesando = false;
        }
      },
      
      async rechazarCita() {
        if (!this.motivo) {
          alert('Por favor, ingrese el motivo del rechazo');
          return;
        }
        
        this.procesando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          const response = await axios.put('/api/citas/rechazar.php', {
            cita_id: this.citaSeleccionada.id,
            motivo: this.motivo
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            await this.cargarCitas();
            this.cerrarModalRechazar();
            this.$emit('cita-actualizada');
          }
        } catch (error) {
          console.error('Error al rechazar cita:', error);
          alert('Error al rechazar la cita. Por favor, intente nuevamente.');
        } finally {
          this.procesando = false;
        }
      },
      
      async tomarCita() {
        if (!this.programacion.fecha || !this.programacion.hora || !this.programacion.consultorio_id) {
          alert('Por favor, complete todos los campos obligatorios');
          return;
        }
        
        this.procesando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          // Primero tomar la cita
          const tomarResponse = await axios.put('/api/citas/tomar.php', {
            cita_id: this.citaSeleccionada.id,
            doctor_id: this.doctorId
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (tomarResponse.data && tomarResponse.data.message) {
            // Luego programar la cita
            const programarResponse = await axios.put('/api/citas/actualizar.php', {
              cita_id: this.citaSeleccionada.id,
              fecha: this.programacion.fecha,
              hora: this.programacion.hora,
              consultorio_id: this.programacion.consultorio_id,
              notas: this.programacion.notas,
              estado: 'confirmada'
            }, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            
            if (programarResponse.data && programarResponse.data.message) {
              await this.cargarCitas();
              this.cerrarModalTomar();
              this.$emit('cita-actualizada');
            }
          }
        } catch (error) {
          console.error('Error al tomar y programar cita:', error);
          alert('Error al tomar y programar la cita. Por favor, intente nuevamente.');
        } finally {
          this.procesando = false;
        }
      }
    }
  }
  </script>
  
  <style scoped>
  .aceptar-citas {
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
  
  /* Tabs */
  .tabs {
    display: flex;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 20px;
  }
  
  .tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6c757d;
    transition: all 0.2s ease;
  }
  
  .tab-btn:hover {
    color: #495057;
  }
  
  .tab-btn.active {
    color: #007bff;
    border-bottom: 3px solid #007bff;
  }
  
  .tab-content {
    margin-top: 20px;
  }
  
  /* Citas Grid */
  .citas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }
  
  .cita-card {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  
  .cita-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
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
  
  .estado-asignada {
    background-color: #17a2b8;
    color: white;
  }
  
  .estado-disponible {
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
  
  /* Estado vacío y carga */
  .loading, .empty-state {
    padding: 40px;
    text-align: center;
    color: #6c757d;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-top: 20px;
  }
  
  .empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #dee2e6;
  }
  
  .loading i {
    margin-right: 10px;
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
  
  .form-group {
    margin-bottom: 15px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #495057;
  }
  
  .form-group input, 
  .form-group select, 
  .form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.95rem;
  }
  
  .form-group input:focus, 
  .form-group select:focus, 
  .form-group textarea:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
    padding: 10px 18px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
  }
  
  .btn i {
    font-size: 0.9rem;
  }
  
  .btn-primary {
    background-color: #007bff;
    color: white;
  }
  
  .btn-primary:hover {
    background-color: #0069d9;
  }
  
  .btn-secondary {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-secondary:hover {
    background-color: #5a6268;
  }
  
  .btn-danger {
    background-color: #dc3545;
    color: white;
  }
  
  .btn-danger:hover {
    background-color: #c82333;
  }
  
  .btn-success {
    background-color: #28a745;
    color: white;
  }
  
  .btn-success:hover {
    background-color: #218838;
  }
  
  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  @media (max-width: 768px) {
    .citas-grid {
      grid-template-columns: 1fr;
    }
  }
  </style>