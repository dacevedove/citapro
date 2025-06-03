<template>
    <div class="solicitar-cita-container">
      <h1>Solicitar Nueva Cita</h1>
      
      <div class="form-container">
        <form @submit.prevent="enviarSolicitud">
          <div class="form-group">
            <label for="especialidad">Especialidad médica:</label>
            <select 
              id="especialidad" 
              v-model="cita.especialidad_id" 
              required
              class="form-control"
            >
              <option value="" disabled selected>Seleccione una especialidad</option>
              <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                {{ esp.nombre }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="descripcion">Descripción de los síntomas o motivo de consulta:</label>
            <textarea 
              id="descripcion" 
              v-model="cita.descripcion" 
              required
              class="form-control"
              rows="5"
              placeholder="Describa brevemente sus síntomas o el motivo de su visita..."
            ></textarea>
          </div>
          
          <div class="form-group">
            <label>Fechas preferidas (opcional):</label>
            <div class="fechas-preferidas">
              <div class="fecha-item" v-for="(fecha, index) in fechasPreferidas" :key="index">
                <input 
                  type="date" 
                  v-model="fechasPreferidas[index]" 
                  class="form-control"
                  :min="fechaMinima"
                >
                <button 
                  type="button" 
                  @click="eliminarFecha(index)" 
                  class="btn-eliminar"
                  title="Eliminar fecha"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
              
              <button 
                type="button" 
                @click="agregarFecha" 
                class="btn btn-outline"
                v-if="fechasPreferidas.length < 3"
              >
                <i class="fas fa-plus"></i> Agregar fecha preferida
              </button>
            </div>
            <small class="text-muted">
              Puede seleccionar hasta 3 fechas preferidas. Dirección médica intentará asignar su cita en una de estas fechas, si es posible.
            </small>
          </div>
          
          <div class="form-group">
            <label for="notas">Notas adicionales (opcional):</label>
            <textarea 
              id="notas" 
              v-model="cita.notas" 
              class="form-control"
              rows="3"
              placeholder="Información adicional que pueda ser relevante para su cita..."
            ></textarea>
          </div>
          
          <div class="form-actions">
            <router-link to="/paciente/dashboard" class="btn btn-outline">Cancelar</router-link>
            <button type="submit" class="btn btn-primary" :disabled="enviando">
              {{ enviando ? 'Enviando...' : 'Solicitar Cita' }}
            </button>
          </div>
        </form>
      </div>
      
      <!-- Modal de confirmación -->
      <div v-if="mostrarModalConfirmacion" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Solicitud Enviada</h2>
            <button @click="cerrarModal" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="success-icon">
              <i class="fas fa-check-circle"></i>
            </div>
            
            <p>Su solicitud de cita ha sido enviada exitosamente.</p>
            <p>Número de solicitud: <strong>{{ citaCreada.id }}</strong></p>
            
            <p>Pronto el personal de dirección médica asignará un doctor y horario para su cita. Recibirá una notificación cuando esto ocurra.</p>
          </div>
          
          <div class="modal-footer">
            <button @click="irADashboard" class="btn btn-primary">Volver al Dashboard</button>
          </div>
        </div>
      </div>
      
      <!-- Modal de error -->
      <div v-if="mostrarModalError" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Error</h2>
            <button @click="cerrarModalError" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="error-icon">
              <i class="fas fa-exclamation-circle"></i>
            </div>
            
            <p>Ha ocurrido un error al procesar su solicitud:</p>
            <p class="error-message">{{ mensajeError }}</p>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalError" class="btn btn-primary">Entendido</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'PacienteSolicitarCita',
    data() {
      return {
        especialidades: [],
        cita: {
          especialidad_id: '',
          descripcion: '',
          notas: ''
        },
        fechasPreferidas: [''],
        enviando: false,
        mostrarModalConfirmacion: false,
        mostrarModalError: false,
        mensajeError: '',
        citaCreada: { id: null }
      };
    },
    computed: {
      fechaMinima() {
        const hoy = new Date();
        return hoy.toISOString().split('T')[0];
      }
    },
    mounted() {
      this.cargarEspecialidades();
    },
    methods: {
      async cargarEspecialidades() {
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/doctores/especialidades.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.especialidades = response.data;
        } catch (error) {
          console.error('Error al cargar especialidades:', error);
          this.mostrarError('No se pudieron cargar las especialidades médicas. Por favor, intente nuevamente más tarde.');
        }
      },
      
      agregarFecha() {
        if (this.fechasPreferidas.length < 3) {
          this.fechasPreferidas.push('');
        }
      },
      
      eliminarFecha(index) {
        this.fechasPreferidas.splice(index, 1);
      },
      
      async enviarSolicitud() {
        if (!this.cita.especialidad_id || !this.cita.descripcion) {
          this.mostrarError('Por favor, complete todos los campos obligatorios.');
          return;
        }
        
        this.enviando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          // Filtrar fechas vacías
          const fechasValidas = this.fechasPreferidas.filter(fecha => fecha);
          
          const payload = {
            ...this.cita,
            fechas_preferidas: fechasValidas
          };
          
          const response = await axios.post('/api/citas/solicitar-paciente.php', payload, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.id) {
            this.citaCreada = {
              id: response.data.id
            };
            
            this.mostrarModalConfirmacion = true;
          }
        } catch (error) {
          console.error('Error al enviar solicitud:', error);
          
          if (error.response && error.response.data && error.response.data.error) {
            this.mostrarError(error.response.data.error);
          } else {
            this.mostrarError('Ha ocurrido un error al procesar su solicitud. Por favor, intente nuevamente más tarde.');
          }
        } finally {
          this.enviando = false;
        }
      },
      
      mostrarError(mensaje) {
        this.mensajeError = mensaje;
        this.mostrarModalError = true;
      },
      
      cerrarModal() {
        this.mostrarModalConfirmacion = false;
      },
      
      cerrarModalError() {
        this.mostrarModalError = false;
      },
      
      irADashboard() {
        this.$router.push('/paciente/dashboard');
      }
    }
  };
  </script>
  
  <style scoped>
  .solicitar-cita-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
  }
  
  h1 {
    margin-bottom: 30px;
    color: var(--dark-color);
  }
  
  .form-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    padding: 30px;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-color);
  }
  
  .form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 16px;
  }
  
  .form-control:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
  }
  
  textarea.form-control {
    resize: vertical;
  }
  
  .text-muted {
    color: var(--secondary-color);
    font-size: 14px;
    margin-top: 5px;
  }
  
  .fechas-preferidas {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 10px;
  }
  
  .fecha-item {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .btn-eliminar {
    background: none;
    border: none;
    color: var(--danger-color);
    cursor: pointer;
    font-size: 18px;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
  }
  
  .btn {
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    font-size: 16px;
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
  
  .btn-primary {
    background-color: var(--primary-color);
    border: 1px solid var(--primary-color);
    color: white;
  }
  
  .btn-primary:hover {
    background-color: #0069d9;
  }
  
  .btn:disabled {
    background-color: var(--secondary-color);
    border-color: var(--secondary-color);
    cursor: not-allowed;
    opacity: 0.7;
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
    width: 90%;
    max-width: 500px;
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
    font-size: 20px;
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
    text-align: center;
  }
  
  .success-icon, .error-icon {
    font-size: 60px;
    margin-bottom: 20px;
  }
  
  .success-icon {
    color: var(--success-color);
  }
  
  .error-icon {
    color: var(--danger-color);
  }
  
  .error-message {
    color: var(--danger-color);
    font-weight: 500;
  }
  
  .modal-footer {
    padding: 15px 20px;
    display: flex;
    justify-content: center;
    border-top: 1px solid #e9ecef;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .form-container {
      padding: 20px;
    }
    
    .form-actions {
      flex-direction: column-reverse;
    }
    
    .form-actions .btn {
      width: 100%;
    }
  }
  </style>