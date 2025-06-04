<template>
    <div class="solicitar-cita">
      <h2>Solicitar nueva cita</h2>
      
      <div class="form-container">
        <div class="form-section">
          <h3>Información del titular</h3>
          
          <div class="form-group">
            <label>Buscar titular por cédula:</label>
            <div class="search-container">
              <input 
                type="text" 
                v-model="cedulaTitular" 
                @input="buscarTitular"
                placeholder="Ingrese cédula del titular" 
              />
              <button @click="buscarTitular" class="btn btn-secondary">Buscar</button>
            </div>
          </div>
          
          <div v-if="titular">
            <p><strong>Titular encontrado:</strong> {{ titular.nombre }} {{ titular.apellido }}</p>
            <p><strong>Número de afiliado:</strong> {{ titular.numero_afiliado }}</p>
            <p><strong>Estado:</strong> {{ titular.estado }}</p>
          </div>
          
          <div v-if="!titular" class="mt-3">
            <p>Titular no encontrado. Por favor, complete la información:</p>
            
            <div class="form-group">
              <label>Nombre:</label>
              <input type="text" v-model="nuevoTitular.nombre" required />
            </div>
            
            <div class="form-group">
              <label>Apellido:</label>
              <input type="text" v-model="nuevoTitular.apellido" required />
            </div>
            
            <div class="form-group">
              <label>Cédula:</label>
              <input type="text" v-model="nuevoTitular.cedula" required />
            </div>
            
            <div class="form-group">
              <label>Teléfono:</label>
              <input type="tel" v-model="nuevoTitular.telefono" required />
            </div>
            
            <div class="form-group">
              <label>Email:</label>
              <input type="email" v-model="nuevoTitular.email" />
            </div>
            
            <div class="form-group">
              <label>Estado:</label>
              <input type="text" v-model="nuevoTitular.estado" required />
            </div>
            
            <div class="form-group">
              <label>Número de afiliado:</label>
              <input type="text" v-model="nuevoTitular.numero_afiliado" required />
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h3>Información del paciente</h3>
          
          <div class="form-group">
            <label>
              <input type="checkbox" v-model="esTitularPaciente" />
              El paciente es el mismo titular
            </label>
          </div>
          
          <template v-if="!esTitularPaciente">
            <div class="form-group">
              <label>Buscar paciente por cédula:</label>
              <div class="search-container">
                <input 
                  type="text" 
                  v-model="cedulaPaciente" 
                  @input="buscarPaciente"
                  placeholder="Ingrese cédula del paciente" 
                />
                <button @click="buscarPaciente" class="btn btn-secondary">Buscar</button>
              </div>
            </div>
            
            <div v-if="paciente">
              <p><strong>Paciente encontrado:</strong> {{ paciente.nombre }} {{ paciente.apellido }}</p>
              <p><strong>Teléfono:</strong> {{ paciente.telefono }}</p>
            </div>
            
            <div v-if="!paciente" class="mt-3">
              <p>Paciente no encontrado. Por favor, complete la información:</p>
              
              <div class="form-group">
                <label>Nombre:</label>
                <input type="text" v-model="nuevoPaciente.nombre" required />
              </div>
              
              <div class="form-group">
                <label>Apellido:</label>
                <input type="text" v-model="nuevoPaciente.apellido" required />
              </div>
              
              <div class="form-group">
                <label>Cédula:</label>
                <input type="text" v-model="nuevoPaciente.cedula" required />
              </div>
              
              <div class="form-group">
                <label>Teléfono:</label>
                <input type="tel" v-model="nuevoPaciente.telefono" required />
              </div>
              
              <div class="form-group">
                <label>Email:</label>
                <input type="email" v-model="nuevoPaciente.email" />
              </div>
            </div>
          </template>
        </div>
        
        <div class="form-section">
          <h3>Información de la cita</h3>
          
          <div class="form-group">
            <label>Especialidad:</label>
            <select v-model="cita.especialidad_id" required>
              <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                {{ esp.nombre }}
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Descripción breve del caso:</label>
            <textarea v-model="cita.descripcion" rows="4" required></textarea>
          </div>
        </div>
      </div>
      
      <div class="action-buttons">
        <button @click="cancelar" class="btn btn-secondary">Cancelar</button>
        <button @click="enviarSolicitud" :disabled="enviando" class="btn btn-primary">
          {{ enviando ? 'Enviando...' : 'Enviar solicitud de cita' }}
        </button>
      </div>
      
      <div v-if="mensaje" :class="['alert', mensaje.tipo === 'error' ? 'alert-danger' : 'alert-success']">
        {{ mensaje.texto }}
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'SolicitarCita',
    data() {
      return {
        cedulaTitular: '',
        cedulaPaciente: '',
        titular: null,
        paciente: null,
        esTitularPaciente: false,
        nuevoTitular: {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          estado: '',
          numero_afiliado: '',
          es_paciente: false
        },
        nuevoPaciente: {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: ''
        },
        cita: {
          especialidad_id: '',
          descripcion: ''
        },
        especialidades: [],
        enviando: false,
        mensaje: null
      }
    },
    watch: {
    esTitularPaciente(val) {
      if (val && this.titular) {
        // Si el paciente es el titular, copiar los datos del titular al paciente
        this.paciente = {
          id: null,
          nombre: this.titular.nombre,
          apellido: this.titular.apellido,
          cedula: this.titular.cedula,
          telefono: this.titular.telefono,
          email: this.titular.email
        };
      } else {
        // Si no es el titular, reiniciar los datos del paciente
        this.paciente = null;
        this.cedulaPaciente = '';
        this.nuevoPaciente = {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: ''
        };
      }
    }
  },
  mounted() {
    this.cargarEspecialidades();
  },
  methods: {
    async cargarEspecialidades() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('https://localhost/api/doctores/especialidades.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.especialidades = response.data;
      } catch (error) {
        console.error('Error al cargar especialidades:', error);
        this.mensaje = {
          tipo: 'error',
          texto: 'Error al cargar las especialidades. Por favor, intente nuevamente.'
        };
      }
    },
    async buscarTitular() {
      if (!this.cedulaTitular) return;
      
      try {
        this.titular = null;
        const token = localStorage.getItem('token');
        const response = await axios.get(`https://localhost/api/titulares/buscar.php?cedula=${this.cedulaTitular}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.id) {
          this.titular = response.data;
          
          // Si está marcado que el paciente es el titular, copiar datos
          if (this.esTitularPaciente) {
            this.paciente = {
              id: null,
              nombre: this.titular.nombre,
              apellido: this.titular.apellido,
              cedula: this.titular.cedula,
              telefono: this.titular.telefono,
              email: this.titular.email
            };
          }
        }
      } catch (error) {
        console.error('Error al buscar titular:', error);
        this.mensaje = {
          tipo: 'error',
          texto: 'Error al buscar el titular. Por favor, intente nuevamente.'
        };
      }
    },
    async buscarPaciente() {
      if (!this.cedulaPaciente || this.esTitularPaciente) return;
      
      try {
        this.paciente = null;
        const token = localStorage.getItem('token');
        const response = await axios.get(`https://localhost/api/pacientes/buscar.php?cedula=${this.cedulaPaciente}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.id) {
          this.paciente = response.data;
        }
      } catch (error) {
        console.error('Error al buscar paciente:', error);
        this.mensaje = {
          tipo: 'error',
          texto: 'Error al buscar el paciente. Por favor, intente nuevamente.'
        };
      }
    },
    async enviarSolicitud() {
      this.enviando = true;
      this.mensaje = null;
      
      try {
        const token = localStorage.getItem('token');
        
        // 1. Validar si hay que crear un nuevo titular
        let titularId = this.titular ? this.titular.id : null;
        
        if (!titularId) {
          // Crear nuevo titular
          const titularResponse = await axios.post('https://localhost/api/titulares/crear.php', 
            {
              ...this.nuevoTitular,
              es_paciente: this.esTitularPaciente
            },
            {
              headers: { 'Authorization': `Bearer ${token}` }
            }
          );
          
          titularId = titularResponse.data.id;
        }
        
        // 2. Validar si hay que crear un nuevo paciente
        let pacienteId = this.paciente ? this.paciente.id : null;
        
        if (!pacienteId) {
          if (this.esTitularPaciente) {
            // Si el paciente es el titular, buscar el paciente asociado
            const pacienteResponse = await axios.get(`https://localhost/api/pacientes/buscar.php?titular_id=${titularId}&es_titular=1`, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            
            if (pacienteResponse.data && pacienteResponse.data.id) {
              pacienteId = pacienteResponse.data.id;
            }
          } else {
            // Crear nuevo paciente
            const pacienteResponse = await axios.post('https://localhost/api/pacientes/crear.php',
              {
                ...this.nuevoPaciente,
                titular_id: titularId,
                tipo: 'asegurado'
              },
              {
                headers: { 'Authorization': `Bearer ${token}` }
              }
            );
            
            pacienteId = pacienteResponse.data.id;
          }
        }
        
        // 3. Crear la cita
        const citaResponse = await axios.post('https://localhost/api/citas/crear.php',
          {
            paciente_id: pacienteId,
            especialidad_id: this.cita.especialidad_id,
            descripcion: this.cita.descripcion
          },
          {
            headers: { 'Authorization': `Bearer ${token}` }
          }
        );
        
        this.mensaje = {
          tipo: 'exito',
          texto: 'Solicitud de cita enviada exitosamente. ID de cita: ' + citaResponse.data.id
        };
        
        // Limpiar formulario
        this.limpiarFormulario();
        
      } catch (error) {
        console.error('Error al enviar solicitud:', error);
        this.mensaje = {
          tipo: 'error',
          texto: 'Error al enviar la solicitud. Por favor, intente nuevamente.'
        };
      } finally {
        this.enviando = false;
      }
    },
    limpiarFormulario() {
      this.cedulaTitular = '';
      this.cedulaPaciente = '';
      this.titular = null;
      this.paciente = null;
      this.esTitularPaciente = false;
      this.nuevoTitular = {
        nombre: '',
        apellido: '',
        cedula: '',
        telefono: '',
        email: '',
        estado: '',
        numero_afiliado: '',
        es_paciente: false
      };
      this.nuevoPaciente = {
        nombre: '',
        apellido: '',
        cedula: '',
        telefono: '',
        email: ''
      };
      this.cita = {
        especialidad_id: '',
        descripcion: ''
      };
    },
    cancelar() {
      this.limpiarFormulario();
      this.$router.push('/aseguradora/dashboard');
    }
  }
}
</script>

<style scoped>
.solicitar-cita {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.form-container {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 20px;
}

.form-section {
  flex: 1;
  min-width: 300px;
  background-color: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input, 
.form-group select, 
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
}

.search-container {
  display: flex;
  gap: 10px;
}

.search-container input {
  flex: 1;
}

.action-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
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

.alert {
  padding: 15px;
  border-radius: 4px;
  margin-top: 20px;
}

.alert-success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}
</style>