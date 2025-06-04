<template>
    <div>
      <!-- Botón para abrir el modal -->
      <button @click="abrirModal" class="btn-crear">
        <i class="fas fa-plus-circle"></i> Nueva Cita
      </button>
      
      <!-- Modal para crear cita -->
      <div v-if="mostrarModal" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Crear Nueva Cita</h2>
            <button @click="cerrarModal" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="form-section">
              <h3 class="section-title">Información del Paciente</h3>
              
              <!-- Búsqueda por cédula -->
              <div class="form-group">
                <label>Cédula del paciente</label>
                <div class="input-group">
                  <input 
                    type="text" 
                    v-model="cedulaPaciente" 
                    placeholder="Ingrese la cédula del paciente" 
                    class="form-control"
                    @keyup.enter="buscarPacientePorCedula"
                  />
                  <button @click="buscarPacientePorCedula" class="btn-search" :disabled="buscandoPaciente">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
                <p v-if="errorBusqueda" class="error-message">{{ errorBusqueda }}</p>
              </div>
              
              <!-- Paciente encontrado -->
              <div v-if="pacienteEncontrado" class="paciente-card">
                <div class="paciente-info">
                  <h4>{{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellido }}</h4>
                  <p><strong>Cédula:</strong> {{ pacienteSeleccionado.cedula }}</p>
                  <p v-if="pacienteSeleccionado.telefono"><strong>Teléfono:</strong> {{ pacienteSeleccionado.telefono }}</p>
                  <p v-if="pacienteSeleccionado.email"><strong>Email:</strong> {{ pacienteSeleccionado.email }}</p>
                  <p><strong>Tipo:</strong> {{ pacienteSeleccionado.tipo === 'asegurado' ? 'Asegurado' : 'Particular' }}</p>
                  <p v-if="pacienteSeleccionado.aseguradora_nombre"><strong>Aseguradora:</strong> {{ pacienteSeleccionado.aseguradora_nombre }}</p>
                </div>
              </div>
              
              <!-- Formulario para nuevo paciente (se muestra si el paciente no existe) -->
              <div v-if="mostrarFormularioNuevoPaciente" class="form-group-container">
                <p class="info-message">El paciente no existe en el sistema. Por favor, complete los siguientes datos:</p>
                
                <div class="form-row">
                  <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" v-model="nuevoPaciente.nombre" class="form-control" required />
                  </div>
                  
                  <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" v-model="nuevoPaciente.apellido" class="form-control" required />
                  </div>
                </div>
                
                <div class="form-row">
                  <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" v-model="nuevoPaciente.telefono" class="form-control" />
                  </div>
                  
                  <div class="form-group">
                    <label>Email</label>
                    <input type="email" v-model="nuevoPaciente.email" class="form-control" />
                  </div>
                </div>
                
                <div class="form-group">
                  <label class="radio-label">Tipo de paciente</label>
                  <div class="radio-buttons">
                    <label class="radio-button">
                      <input type="radio" v-model="nuevoPaciente.tipo" value="asegurado" />
                      <span class="radio-text">Asegurado</span>
                    </label>
                    
                    <label class="radio-button">
                      <input type="radio" v-model="nuevoPaciente.tipo" value="particular" />
                      <span class="radio-text">Particular</span>
                    </label>
                  </div>
                </div>
                
                <div v-if="nuevoPaciente.tipo === 'asegurado'" class="form-group">
                  <label>Cédula del Titular</label>
                  <div class="input-group">
                    <input 
                      type="text" 
                      v-model="cedulaTitular" 
                      placeholder="Ingrese la cédula del titular" 
                      class="form-control"
                      @keyup.enter="buscarTitularPorCedula"
                    />
                    <button @click="buscarTitularPorCedula" class="btn-search" :disabled="buscandoTitular">
                      <i class="fas fa-search"></i>
                    </button>
                  </div>
                  <p v-if="errorBusquedaTitular" class="error-message">{{ errorBusquedaTitular }}</p>
                </div>
                
                <div v-if="titularSeleccionado.id" class="paciente-card">
                  <div class="paciente-info">
                    <h4>{{ titularSeleccionado.nombre }} {{ titularSeleccionado.apellido }}</h4>
                    <p><strong>Cédula:</strong> {{ titularSeleccionado.cedula }}</p>
                    <p v-if="titularSeleccionado.telefono"><strong>Teléfono:</strong> {{ titularSeleccionado.telefono }}</p>
                    <p v-if="titularSeleccionado.email"><strong>Email:</strong> {{ titularSeleccionado.email }}</p>
                    <p v-if="titularSeleccionado.aseguradora_nombre"><strong>Aseguradora:</strong> {{ titularSeleccionado.aseguradora_nombre }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="pacienteSeleccionado.id || mostrarFormularioNuevoPaciente" class="form-section">
              <h3 class="section-title">Información de la Cita</h3>
              
              <div class="form-group">
                <label>Especialidad</label>
                <select v-model="cita.especialidad_id" class="form-control" required>
                  <option value="">Seleccione una especialidad</option>
                  <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                    {{ esp.nombre }}
                  </option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Motivo/Descripción</label>
                <textarea v-model="cita.descripcion" rows="3" class="form-control" required></textarea>
              </div>
              
              <div class="form-group">
                <label class="radio-label">Asignación inicial</label>
                <div class="radio-buttons">
                  <label class="radio-button">
                    <input type="radio" v-model="cita.asignacion_inicial" value="ninguna" />
                    <span class="radio-text">Sin asignar</span>
                  </label>
                  
                  <label class="radio-button">
                    <input type="radio" v-model="cita.asignacion_inicial" value="doctor" />
                    <span class="radio-text">Asignar a un doctor</span>
                  </label>
                  
                  <label class="radio-button">
                    <input type="radio" v-model="cita.asignacion_inicial" value="libre" />
                    <span class="radio-text">Asignación libre</span>
                  </label>
                </div>
              </div>
              
              <div v-if="cita.asignacion_inicial === 'doctor'" class="form-group">
                <label>Doctor</label>
                <select v-model="cita.doctor_id" class="form-control">
                  <option value="">Seleccione un doctor</option>
                  <option v-for="doctor in doctoresFiltrados" :key="doctor.id" :value="doctor.id">
                    {{ doctor.nombre }} {{ doctor.apellido }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModal" class="btn-cancel">Cancelar</button>
            <button @click="guardarCita" :disabled="procesando || !puedeGuardar" class="btn-success">
              {{ procesando ? 'Procesando...' : 'Crear Cita' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'CrearCita',
    data() {
      return {
        mostrarModal: false,
        procesando: false,
        
        // Datos para búsqueda de paciente
        cedulaPaciente: '',
        buscandoPaciente: false,
        errorBusqueda: '',
        pacienteEncontrado: false,
        mostrarFormularioNuevoPaciente: false,
        
        // Datos para búsqueda de titular
        cedulaTitular: '',
        buscandoTitular: false,
        errorBusquedaTitular: '',
        
        especialidades: [],
        doctores: [],
        
        pacienteSeleccionado: {},
        titularSeleccionado: {},
        
        // Datos de la cita
        cita: {
          paciente_id: '',
          especialidad_id: '',
          descripcion: '',
          asignacion_inicial: 'ninguna',
          doctor_id: ''
        },
        
        // Datos del nuevo paciente
        nuevoPaciente: {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          tipo: 'asegurado',
          es_titular: false,
          titular_id: null
        }
      };
    },
    
    computed: {
      doctoresFiltrados() {
        if (!this.cita.especialidad_id) return [];
        return this.doctores.filter(doctor => doctor.especialidad_id == this.cita.especialidad_id);
      },
      
      puedeGuardar() {
        // Verificar si existe un paciente seleccionado o si se ha completado el formulario de nuevo paciente
        const pacienteValido = this.pacienteSeleccionado.id || 
                           (this.mostrarFormularioNuevoPaciente && 
                            this.nuevoPaciente.nombre && 
                            this.nuevoPaciente.apellido && 
                            (this.nuevoPaciente.tipo !== 'asegurado' || this.nuevoPaciente.titular_id));
        
        // Verificar si se han completado los campos de la cita
        const citaValida = this.cita.especialidad_id && 
                         this.cita.descripcion && 
                         (this.cita.asignacion_inicial !== 'doctor' || this.cita.doctor_id);
        
        return pacienteValido && citaValida;
      }
    },
    
    methods: {
      abrirModal() {
        this.mostrarModal = true;
        this.cargarEspecialidades();
        this.cargarDoctores();
        
        // Enfocar el campo de cédula
        this.$nextTick(() => {
          const input = document.querySelector('.input-group input');
          if (input) {
            input.focus();
          }
        });
      },
      
      cerrarModal() {
        this.mostrarModal = false;
        this.resetearFormulario();
      },
      
      resetearFormulario() {
        this.cedulaPaciente = '';
        this.pacienteEncontrado = false;
        this.mostrarFormularioNuevoPaciente = false;
        this.errorBusqueda = '';
        this.pacienteSeleccionado = {};
        
        this.cedulaTitular = '';
        this.errorBusquedaTitular = '';
        this.titularSeleccionado = {};
        
        this.cita = {
          paciente_id: '',
          especialidad_id: '',
          descripcion: '',
          asignacion_inicial: 'ninguna',
          doctor_id: ''
        };
        
        this.nuevoPaciente = {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          tipo: 'asegurado',
          es_titular: false,
          titular_id: null
        };
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
      
      async buscarPacientePorCedula() {
        if (!this.cedulaPaciente) {
          this.errorBusqueda = 'Por favor, ingrese una cédula';
          return;
        }
        
        this.buscandoPaciente = true;
        this.errorBusqueda = '';
        this.pacienteSeleccionado = {};
        this.pacienteEncontrado = false;
        this.mostrarFormularioNuevoPaciente = false;
        this.cita.paciente_id = '';
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`/api/pacientes/buscar.php?cedula=${this.cedulaPaciente}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && !response.data.error) {
            // Paciente encontrado
            this.pacienteSeleccionado = response.data;
            this.cita.paciente_id = response.data.id;
            this.pacienteEncontrado = true;
          } else {
            // Paciente no encontrado, mostrar formulario para crear
            this.errorBusqueda = '';
            this.mostrarFormularioNuevoPaciente = true;
            this.nuevoPaciente.cedula = this.cedulaPaciente;
          }
        } catch (error) {
          console.error('Error al buscar paciente:', error);
          if (error.response && error.response.status === 404) {
            // Paciente no encontrado, mostrar formulario para crear
            this.errorBusqueda = '';
            this.mostrarFormularioNuevoPaciente = true;
            this.nuevoPaciente.cedula = this.cedulaPaciente;
          } else {
            this.errorBusqueda = 'Error al buscar paciente. Por favor, intente nuevamente.';
          }
        } finally {
          this.buscandoPaciente = false;
        }
      },
      
      async buscarTitularPorCedula() {
        if (!this.cedulaTitular) {
          this.errorBusquedaTitular = 'Por favor, ingrese una cédula';
          return;
        }
        
        this.buscandoTitular = true;
        this.errorBusquedaTitular = '';
        this.titularSeleccionado = {};
        this.nuevoPaciente.titular_id = null;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`/api/titulares/buscar.php?cedula=${this.cedulaTitular}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && !response.data.error) {
            this.titularSeleccionado = response.data;
            this.nuevoPaciente.titular_id = response.data.id;
          } else {
            this.errorBusquedaTitular = 'No se encontró ningún titular con esa cédula';
          }
        } catch (error) {
          console.error('Error al buscar titular:', error);
          this.errorBusquedaTitular = 'Error al buscar titular. Por favor, intente nuevamente.';
        } finally {
          this.buscandoTitular = false;
        }
      },
      
      async guardarCita() {
  // Validaciones básicas ya se realizan con el computed puedeGuardar
  this.procesando = true;
  
  try {
    const token = localStorage.getItem('token');
    
    // Si es un nuevo paciente, crearlo primero
    let pacienteId = this.cita.paciente_id;
    
    if (this.mostrarFormularioNuevoPaciente) {
      console.log('Creando nuevo paciente:', this.nuevoPaciente);
      
      try {
        const nuevoPacienteResponse = await axios.post('/api/pacientes/crear.php', this.nuevoPaciente, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        console.log('Respuesta de crear paciente:', nuevoPacienteResponse.data);
        pacienteId = nuevoPacienteResponse.data.id;
      } catch (pacienteError) {
        console.error('Error al crear paciente:', pacienteError);
        console.log('Datos del error:', pacienteError.response?.data);
        throw new Error('Error al crear el paciente. Verifique los datos e intente nuevamente.');
      }
    }
    
    // Crear la cita
    const payload = {
      paciente_id: pacienteId,
      especialidad_id: this.cita.especialidad_id,
      descripcion: this.cita.descripcion
    };
    
    console.log('Enviando datos de cita:', payload);
    
    try {
      const citaResponse = await axios.post('/api/citas/crear.php', payload, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      console.log('Respuesta de crear cita:', citaResponse.data);
      const citaId = citaResponse.data.id;
      
      // Si se debe asignar, hacer la asignación
      if (this.cita.asignacion_inicial !== 'ninguna') {
        const asignacionPayload = {
          cita_id: citaId,
          especialidad_id: this.cita.especialidad_id
        };
        
        if (this.cita.asignacion_inicial === 'doctor') {
          asignacionPayload.doctor_id = this.cita.doctor_id;
        } else {
          asignacionPayload.asignacion_libre = true;
        }
        
        console.log('Enviando datos de asignación:', asignacionPayload);
        
        try {
          const asignacionResponse = await axios.put('/api/citas/asignar.php', asignacionPayload, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          console.log('Respuesta de asignar cita:', asignacionResponse.data);
        } catch (asignacionError) {
          console.error('Error al asignar cita:', asignacionError);
          console.log('Datos del error:', asignacionError.response?.data);
          throw new Error('La cita se creó, pero hubo un error al asignarla. Por favor, verifique en el sistema.');
        }
      }
      
      alert('Cita creada exitosamente');
      this.cerrarModal();
      this.$emit('cita-creada');
      
    } catch (citaError) {
      console.error('Error al crear cita:', citaError);
      console.log('Datos del error:', citaError.response?.data);
      throw new Error('Error al crear la cita. Verifique los datos e intente nuevamente.');
    }
    
  } catch (error) {
    console.error('Error general:', error);
    alert(error.message || 'Error al crear la cita. Por favor, intente nuevamente.');
  } finally {
    this.procesando = false;
  }
}
    }
  };
  </script>
  
  <style scoped>
  .btn-crear {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 4px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-crear:hover {
    background-color: #218838;
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
    padding: 16px 20px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-radius: 8px 8px 0 0;
  }
  
  .modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #343a40;
  }
  
  .close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
    transition: color 0.2s;
  }
  
  .close-btn:hover {
    color: #343a40;
  }
  
  .modal-body {
    padding: 20px;
  }
  
  .modal-footer {
    padding: 16px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-radius: 0 0 8px 8px;
  }
  
  .form-section {
    margin-bottom: 24px;
  }
  
  .section-title {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 16px;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 8px;
  }
  
  .form-group {
    margin-bottom: 16px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #343a40;
  }
  
  .form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
  }
  
  .form-control:focus {
    border-color: #80bdff;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }
  
  .input-group {
    display: flex;
    align-items: center;
  }
  
  .input-group .form-control {
    border-radius: 4px 0 0 4px;
    flex: 1;
  }
  
  .btn-search {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 12px;
    border-radius: 0 4px 4px 0;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-search:hover {
    background-color: #0069d9;
  }
  
  .btn-search:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
  }
  
  .error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 4px;
  }
  
  .info-message {
    color: #0c5460;
    background-color: #d1ecf1;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 16px;
    font-size: 14px;
  }
  
  .paciente-card {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 12px 16px;
    margin-top: 12px;
    border-left: 3px solid #28a745;
  }
  
  .paciente-info h4 {
    margin: 0 0 8px 0;
    color: #343a40;
    font-size: 16px;
  }
  
  .paciente-info p {
    margin: 4px 0;
    font-size: 14px;
    color: #6c757d;
  }
  
  .form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 0;
  }
  
  .form-row .form-group {
    flex: 1;
  }
  
  .radio-label {
    margin-bottom: 10px;
  }
  
  .radio-buttons {
    display: flex;
    gap: 16px;
  }
  
  .radio-button {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
  }
  
  .radio-text {
    font-size: 14px;
    color: #495057;
  }
  
  .btn-cancel {
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #ced4da;
    padding: 10px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-cancel:hover {
    background-color: #e9ecef;
  }
  
  .btn-success {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-success:hover {
    background-color: #218838;
  }
  
  .btn-success:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
  }
  
  .form-group-container {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 16px;
    background-color: #f8f9fa;
  }
  </style>