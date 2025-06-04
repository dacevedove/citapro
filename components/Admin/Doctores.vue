<template>
  <div class="doctores-container">
    <h1>Gestión de Doctores</h1>
    
    <div class="header-section">
      <button @click="abrirModalNuevo" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Doctor
      </button>
    </div>
    
    <div class="filters-section">
      <div class="search-box">
        <input 
          type="text" 
          v-model="filtros.busqueda"
          placeholder="Buscar por nombre o especialidad" 
          class="form-control"
          @input="aplicarFiltros"
        >
        <button class="search-btn">
          <i class="fas fa-search"></i>
        </button>
      </div>
      
      <div class="filter-options">
        <div class="filter-item">
          <label>Especialidad:</label>
          <select v-model="filtros.especialidad_id" class="form-control" @change="aplicarFiltros">
            <option value="">Todas</option>
            <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
              {{ esp.nombre }}
            </option>
          </select>
        </div>
        
        <button @click="resetearFiltros" class="btn btn-outline">
          <i class="fas fa-sync"></i> Resetear
        </button>
      </div>
    </div>
    
    <div v-if="cargando" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando doctores...</p>
    </div>
    
    <div v-else-if="doctores.length === 0" class="empty-state">
      <i class="fas fa-user-md"></i>
      <p>No hay doctores registrados</p>
      <button @click="abrirModalNuevo" class="btn btn-primary">
        Agregar Nuevo Doctor
      </button>
    </div>
    
    <div v-else class="table-responsive">
      <table class="doctores-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Especialidad</th>
            <th>Subespecialidad</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Citas Pendientes</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="doctor in doctores" :key="doctor.id">
            <td>{{ doctor.nombre }} {{ doctor.apellido }}</td>
            <td>{{ doctor.especialidad_nombre }}</td>
            <td>{{ doctor.subespecialidad_nombre || '-' }}</td>
            <td>{{ doctor.email }}</td>
            <td>{{ doctor.telefono }}</td>
            <td>{{ doctor.citas_pendientes || 0 }}</td>
            <td>
              <div class="action-buttons">
                <button @click="verDetalles(doctor)" class="btn-icon" title="Ver detalles">
                  <i class="fas fa-eye"></i>
                </button>
                <button @click="editarDoctor(doctor)" class="btn-icon" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Modal para crear/editar doctor -->
    <div v-if="mostrarModal" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>{{ editando ? 'Editar Doctor' : 'Nuevo Doctor' }}</h2>
          <button @click="cerrarModal" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="guardarDoctor">
            <div class="form-row">
              <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input 
                  type="text" 
                  id="nombre" 
                  v-model="doctorForm.nombre" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input 
                  type="text" 
                  id="apellido" 
                  v-model="doctorForm.apellido" 
                  required
                  class="form-control"
                >
              </div>
            </div>
            
            <div class="form-group">
              <label for="cedula">Cédula de Identidad:</label>
              <input 
                type="text" 
                id="cedula" 
                v-model="doctorForm.cedula" 
                required
                class="form-control"
                :disabled="editando"
              >
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="email">Email:</label>
                <input 
                  type="email" 
                  id="email" 
                  v-model="doctorForm.email" 
                  required
                  class="form-control"
                  :disabled="editando"
                >
              </div>
              
              <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input 
                  type="tel" 
                  id="telefono" 
                  v-model="doctorForm.telefono" 
                  required
                  class="form-control"
                >
              </div>
            </div>
            
            <div class="form-group">
              <label for="especialidad">Especialidad:</label>
              <select 
                id="especialidad" 
                v-model="doctorForm.especialidad_id" 
                required
                class="form-control"
              >
                <option value="" disabled>Seleccione una especialidad</option>
                <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                  {{ esp.nombre }}
                </option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="subespecialidad">Subespecialidad:</label>
              <select 
                id="subespecialidad" 
                v-model="doctorForm.subespecialidad_id" 
                class="form-control"
                :disabled="!doctorForm.especialidad_id || subespecialidades.length === 0"
              >
                <option value="">Ninguna</option>
                <option v-for="sub in subespecialidades" :key="sub.id" :value="sub.id">
                  {{ sub.nombre }}
                </option>
              </select>
            </div>
            
            <div v-if="!editando" class="form-group">
              <label for="password">Contraseña:</label>
              <input 
                type="password" 
                id="password" 
                v-model="doctorForm.password" 
                required
                class="form-control"
                placeholder="Mínimo 8 caracteres"
                minlength="8"
              >
            </div>
            
            <div v-if="!editando" class="form-group">
              <label for="confirmPassword">Confirmar Contraseña:</label>
              <input 
                type="password" 
                id="confirmPassword" 
                v-model="doctorForm.confirmPassword" 
                required
                class="form-control"
                placeholder="Repita la contraseña"
                minlength="8"
              >
            </div>
            
            <div v-if="error" class="alert alert-danger">
              {{ error }}
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="cerrarModal" class="btn btn-outline">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="guardando">
                {{ guardando ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Modal de detalles del doctor -->
    <div v-if="mostrarModalDetalles" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>Detalles del Doctor</h2>
          <button @click="cerrarModalDetalles" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="doctor-info">
            <h3>{{ doctorSeleccionado.nombre }} {{ doctorSeleccionado.apellido }}</h3>
            <p class="doctor-especialidad">
              {{ doctorSeleccionado.especialidad_nombre }}
              <span v-if="doctorSeleccionado.subespecialidad_nombre">
                - {{ doctorSeleccionado.subespecialidad_nombre }}
              </span>
            </p>
            
            <div class="info-section">
              <div class="info-row">
                <div class="info-label">Cédula:</div>
                <div class="info-value">{{ doctorSeleccionado.cedula }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ doctorSeleccionado.email }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ doctorSeleccionado.telefono }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Citas Pendientes:</div>
                <div class="info-value">{{ doctorSeleccionado.citas_pendientes || 0 }}</div>
              </div>
              
              <div class="info-row">
                <div class="info-label">Citas Completadas:</div>
                <div class="info-value">{{ doctorSeleccionado.citas_completadas || 0 }}</div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="cerrarModalDetalles" class="btn btn-primary">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Doctores',
  data() {
    return {
      doctores: [],
      especialidades: [],
      subespecialidades: [],
      cargando: false,
      mostrarModal: false,
      mostrarModalDetalles: false,
      editando: false,
      guardando: false,
      error: null,
      doctorSeleccionado: {},
      doctorForm: {
        nombre: '',
        apellido: '',
        cedula: '',
        email: '',
        telefono: '',
        especialidad_id: '',
        subespecialidad_id: '',
        password: '',
        confirmPassword: ''
      },
      filtros: {
        busqueda: '',
        especialidad_id: ''
      }
    }
  },
  watch: {
    'doctorForm.especialidad_id': function(newValue) {
      if (newValue) {
        this.cargarSubespecialidades(newValue);
      } else {
        this.subespecialidades = [];
        this.doctorForm.subespecialidad_id = '';
      }
    }
  },
  mounted() {
    this.cargarEspecialidades();
    this.cargarDoctores();
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
      }
    },
    
    async cargarSubespecialidades(especialidadId) {
      if (!especialidadId) {
        this.subespecialidades = [];
        return;
      }
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/doctores/get_subespecialidades.php?especialidad_id=${especialidadId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.subespecialidades = response.data;
        
        // Limpiar selección de subespecialidad si cambia la especialidad
        this.doctorForm.subespecialidad_id = '';
      } catch (error) {
        console.error('Error al cargar subespecialidades:', error);
      }
    },
    
    async cargarDoctores() {
      this.cargando = true;
      
      try {
        const token = localStorage.getItem('token');
        let url = '/api/doctores/listar.php';
        
        // Aplicar filtros
        const params = new URLSearchParams();
        if (this.filtros.especialidad_id) {
          params.append('especialidad_id', this.filtros.especialidad_id);
        }
        
        if (params.toString()) {
          url += `?${params.toString()}`;
        }
        
        const response = await axios.get(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        // Si hay término de búsqueda, filtrar resultados
        if (this.filtros.busqueda) {
          const busqueda = this.filtros.busqueda.toLowerCase();
          this.doctores = response.data.filter(doctor => 
            `${doctor.nombre} ${doctor.apellido}`.toLowerCase().includes(busqueda) ||
            doctor.especialidad_nombre.toLowerCase().includes(busqueda) ||
            (doctor.subespecialidad_nombre && doctor.subespecialidad_nombre.toLowerCase().includes(busqueda))
          );
        } else {
          this.doctores = response.data;
        }
      } catch (error) {
        console.error('Error al cargar doctores:', error);
      } finally {
        this.cargando = false;
      }
    },
    
    aplicarFiltros() {
      this.cargarDoctores();
    },
    
    resetearFiltros() {
      this.filtros = {
        busqueda: '',
        especialidad_id: ''
      };
      this.cargarDoctores();
    },
    
    abrirModalNuevo() {
      this.doctorForm = {
        nombre: '',
        apellido: '',
        cedula: '',
        email: '',
        telefono: '',
        especialidad_id: '',
        subespecialidad_id: '',
        password: '',
        confirmPassword: ''
      };
      this.subespecialidades = [];
      this.editando = false;
      this.error = null;
      this.mostrarModal = true;
    },
    
    editarDoctor(doctor) {
      this.doctorForm = {
        id: doctor.id,
        nombre: doctor.nombre,
        apellido: doctor.apellido,
        cedula: doctor.cedula,
        email: doctor.email,
        telefono: doctor.telefono,
        especialidad_id: doctor.especialidad_id,
        subespecialidad_id: doctor.subespecialidad_id || ''
      };
      this.editando = true;
      this.error = null;
      
      // Cargar subespecialidades si hay especialidad seleccionada
      if (doctor.especialidad_id) {
        this.cargarSubespecialidades(doctor.especialidad_id);
      }
      
      this.mostrarModal = true;
    },
    
    cerrarModal() {
      this.mostrarModal = false;
    },
    
    verDetalles(doctor) {
      this.doctorSeleccionado = doctor;
      this.mostrarModalDetalles = true;
    },
    
    cerrarModalDetalles() {
      this.mostrarModalDetalles = false;
    },
    
    async guardarDoctor() {
      // Validar formulario
      if (!this.doctorForm.nombre || !this.doctorForm.apellido || !this.doctorForm.cedula || 
          !this.doctorForm.email || !this.doctorForm.telefono || !this.doctorForm.especialidad_id) {
        this.error = "Por favor, complete todos los campos obligatorios.";
        return;
      }
      
      // Validar contraseñas si es nuevo doctor
      if (!this.editando) {
        if (!this.doctorForm.password || this.doctorForm.password.length < 8) {
          this.error = "La contraseña debe tener al menos 8 caracteres.";
          return;
        }
        
        if (this.doctorForm.password !== this.doctorForm.confirmPassword) {
          this.error = "Las contraseñas no coinciden.";
          return;
        }
      }
      
      this.guardando = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        
        if (this.editando) {
          // Actualizar doctor existente
          const response = await axios.put('/api/doctores/actualizar.php', this.doctorForm, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            this.cerrarModal();
            this.cargarDoctores();
          }
        } else {
          // Crear nuevo doctor
          const response = await axios.post('/api/doctores/crear.php', this.doctorForm, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.id) {
            this.cerrarModal();
            this.cargarDoctores();
          }
        }
      } catch (error) {
        console.error('Error al guardar doctor:', error);
        this.error = error.response?.data?.error || "Error al guardar doctor. Por favor, intente nuevamente.";
      } finally {
        this.guardando = false;
      }
    }
  }
}
</script>

<style scoped>
.doctores-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  margin-bottom: 20px;
  color: var(--dark-color);
}

.header-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 20px;
}

.filters-section {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.search-box {
  display: flex;
  margin-bottom: 15px;
}

.search-box input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px 0 0 4px;
  font-size: 16px;
}

.search-btn {
  padding: 10px 15px;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
}

.filter-options {
  display: flex;
  align-items: center;
  gap: 15px;
}

.filter-item {
  flex: 1;
}

.filter-item label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 16px;
}

.doctores-table {
  width: 100%;
  border-collapse: collapse;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.doctores-table th,
.doctores-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.doctores-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: var(--dark-color);
}

.table-responsive {
  overflow-x: auto;
}

.text-center {
  text-align: center;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: transparent;
  border: 1px solid #ced4da;
  border-radius: 4px;
  color: var(--secondary-color);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background-color: #e9ecef;
  color: var(--primary-color);
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
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
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.empty-state i {
  font-size: 48px;
  color: var(--secondary-color);
  margin-bottom: 15px;
}

.empty-state p {
  margin-bottom: 20px;
  color: var(--secondary-color);
}

/* Modal styles */
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
}

.modal-footer {
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #e9ecef;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  flex: 1;
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.alert {
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.alert-danger {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.btn {
  padding: 10px 15px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background-color: var(--primary-color);
  border: 1px solid var(--primary-color);
  color: white;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Doctor details */
.doctor-info h3 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.doctor-especialidad {
  color: var(--primary-color);
  font-weight: 500;
  margin-bottom: 20px;
}

.info-section {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
}

.info-row {
  display: flex;
  margin-bottom: 10px;
}

.info-label {
  width: 150px;
  font-weight: 500;
  color: var(--dark-color);
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .filter-options {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>