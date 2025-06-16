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
            <th>Doctor</th>
            <th>Especialidad</th>
            <th>Email</th>
            <th>Teléfono</th>
            <th>Citas Pendientes</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="doctor in doctores" :key="doctor.id">
            <td>
              <div class="doctor-info-completa">
                <ProfilePhoto
                  :photo-url="doctor.foto_perfil"
                  :user-name="doctor.nombre + ' ' + doctor.apellido"
                  :initials="getInitials(doctor.nombre, doctor.apellido)"
                  size="sm"
                  :show-initials="true"
                  :border="true"
                  :show-role="true"
                  user-role="doctor"
                  @click="editarFotoDoctor(doctor)"
                  :clickable="true"
                  class="doctor-avatar"
                />
                <div class="doctor-detalles">
                  <strong class="doctor-nombre">{{ doctor.nombre }} {{ doctor.apellido }}</strong>
                  <small class="doctor-subespecialidad">
                    <i class="fas fa-stethoscope"></i> 
                    {{ doctor.subespecialidad_nombre || 'Sin subespecialidad' }}
                  </small>
                </div>
              </div>
            </td>
            <td>{{ doctor.especialidad_nombre }}</td>
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
                <button @click="editarFotoDoctor(doctor)" class="btn-icon" title="Cambiar foto">
                  <i class="fas fa-camera"></i>
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
            <div class="doctor-header">
              <ProfilePhoto
                :photo-url="doctorSeleccionado.foto_perfil"
                :user-name="doctorSeleccionado.nombre + ' ' + doctorSeleccionado.apellido"
                :initials="getInitials(doctorSeleccionado.nombre, doctorSeleccionado.apellido)"
                size="lg"
                :show-initials="true"
                :border="true"
                :show-role="true"
                user-role="doctor"
                class="detail-profile-photo"
              />
              <div class="doctor-text-info">
                <h3>{{ doctorSeleccionado.nombre }} {{ doctorSeleccionado.apellido }}</h3>
                <p class="doctor-especialidad">
                  {{ doctorSeleccionado.especialidad_nombre }}
                  <span v-if="doctorSeleccionado.subespecialidad_nombre">
                    - {{ doctorSeleccionado.subespecialidad_nombre }}
                  </span>
                </p>
              </div>
            </div>
            
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

    <!-- Modal para cambiar foto de doctor -->
    <div v-if="mostrarModalFoto" class="modal-overlay">
      <div class="modal-container modal-small">
        <div class="modal-header">
          <h2>Cambiar Foto del Doctor</h2>
          <button @click="cerrarModalFoto" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="quick-photo-edit">
            <div class="user-info-quick">
              <ProfilePhoto
                :photo-url="doctorFoto.foto_perfil"
                :user-name="doctorFoto.nombre + ' ' + doctorFoto.apellido"
                :initials="getInitials(doctorFoto.nombre, doctorFoto.apellido)"
                size="lg"
                :show-initials="true"
                :border="true"
                :show-role="true"
                user-role="doctor"
              />
              <h4>{{ doctorFoto.nombre }} {{ doctorFoto.apellido }}</h4>
            </div>
            
            <div class="quick-actions">
              <label for="quick-photo-upload" class="btn btn-primary btn-block">
                <i class="fas fa-camera"></i>
                {{ doctorFoto.foto_perfil ? 'Cambiar Foto' : 'Subir Foto' }}
              </label>
              <input 
                type="file" 
                id="quick-photo-upload" 
                accept="image/*" 
                @change="handlePhotoUpload"
                style="display: none;"
              >
              
              <button 
                v-if="doctorFoto.foto_perfil" 
                @click="eliminarFotoDoctor" 
                class="btn btn-outline btn-block"
                :disabled="uploadingPhoto"
              >
                <i class="fas fa-trash"></i>
                Eliminar Foto
              </button>
            </div>
            
            <!-- Progress bar -->
            <div v-if="uploadingPhoto" class="upload-progress">
              <div class="progress-bar">
                <div class="progress-fill" :style="{width: uploadProgress + '%'}"></div>
              </div>
              <span class="progress-text">{{ uploadProgress }}%</span>
            </div>
          </div>
          
          <div class="modal-footer">
            <button type="button" @click="cerrarModalFoto" class="btn btn-outline">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ProfilePhoto from '../Shared/ProfilePhoto.vue';

export default {
  name: 'Doctores',
  components: {
    ProfilePhoto
  },
  data() {
    return {
      doctores: [],
      especialidades: [],
      subespecialidades: [],
      cargando: false,
      mostrarModal: false,
      mostrarModalDetalles: false,
      mostrarModalFoto: false,
      editando: false,
      guardando: false,
      uploadingPhoto: false,
      uploadProgress: 0,
      error: null,
      doctorSeleccionado: {},
      doctorFoto: {
        id: null,
        nombre: '',
        apellido: '',
        foto_perfil: null
      },
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
    getInitials(nombre, apellido) {
      const n = (nombre || '').charAt(0).toUpperCase();
      const a = (apellido || '').charAt(0).toUpperCase();
      return n + a || 'D';
    },

    // Gestión de fotos
    editarFotoDoctor(doctor) {
      this.doctorFoto = {
        id: doctor.id,
        nombre: doctor.nombre,
        apellido: doctor.apellido,
        foto_perfil: doctor.foto_perfil
      };
      this.mostrarModalFoto = true;
    },

    cerrarModalFoto() {
      this.mostrarModalFoto = false;
    },

    async handlePhotoUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      if (!this.validarArchivo(file)) {
        event.target.value = '';
        return;
      }
      
      await this.subirFotoDoctor(file, this.doctorFoto.id);
      event.target.value = '';
    },

    validarArchivo(file) {
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      if (!allowedTypes.includes(file.type.toLowerCase())) {
        this.error = 'Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP)';
        setTimeout(() => { this.error = null; }, 5000);
        return false;
      }
      
      const maxSize = 5 * 1024 * 1024; // 5MB
      if (file.size > maxSize) {
        this.error = 'El archivo es demasiado grande. Tamaño máximo: 5MB';
        setTimeout(() => { this.error = null; }, 5000);
        return false;
      }
      
      return true;
    },

    async subirFotoDoctor(file, doctorId) {
      this.uploadingPhoto = true;
      this.uploadProgress = 0;
      this.error = null;
      
      try {
        const formData = new FormData();
        formData.append('photo', file);
        formData.append('doctor_id', doctorId);
        
        const token = localStorage.getItem('token');
        const response = await axios.post('/api/doctores/upload_doctor_photo.php', formData, {
          headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'multipart/form-data'
          },
          onUploadProgress: (progressEvent) => {
            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        });
        
        if (response.data.success) {
          const nuevaFotoUrl = response.data.photo_url;
          this.actualizarFotoEnTodosLados(doctorId, nuevaFotoUrl);
          alert('Foto del doctor actualizada correctamente');
        } else {
          this.error = response.data.error || 'Error al subir la foto';
        }
      } catch (error) {
        console.error('Error al subir foto:', error);
        this.error = error.response?.data?.error || 'Error al subir la foto';
      } finally {
        this.uploadingPhoto = false;
        this.uploadProgress = 0;
      }
    },

    async eliminarFotoDoctor() {
      if (!confirm('¿Está seguro de que desea eliminar la foto de este doctor?')) {
        return;
      }
      
      this.uploadingPhoto = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.delete('/api/doctores/delete_doctor_photo.php', {
          headers: {
            'Authorization': 'Bearer ' + token
          },
          data: {
            doctor_id: this.doctorFoto.id
          }
        });
        
        if (response.data.success) {
          this.actualizarFotoEnTodosLados(this.doctorFoto.id, null);
          alert('Foto del doctor eliminada correctamente');
        } else {
          this.error = response.data.error || 'Error al eliminar la foto';
        }
      } catch (error) {
        console.error('Error al eliminar foto:', error);
        this.error = error.response?.data?.error || 'Error al eliminar la foto';
      } finally {
        this.uploadingPhoto = false;
      }
    },

    actualizarFotoEnTodosLados(doctorId, nuevaFotoUrl) {
      // Actualizar en la lista de doctores
      const doctorEnLista = this.doctores.find(d => d.id == doctorId);
      if (doctorEnLista) {
        doctorEnLista.foto_perfil = nuevaFotoUrl;
      }
      
      // Actualizar en doctorSeleccionado si es el mismo
      if (this.doctorSeleccionado.id == doctorId) {
        this.doctorSeleccionado.foto_perfil = nuevaFotoUrl;
      }
      
      // Actualizar en doctorFoto si es el mismo
      if (this.doctorFoto.id == doctorId) {
        this.doctorFoto.foto_perfil = nuevaFotoUrl;
      }
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

/* Información del doctor con avatar */
.doctor-info-completa {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 200px;
}

.doctor-info-completa:hover .doctor-avatar {
  transform: scale(1.05);
}

.doctor-detalles {
  flex: 1;
  min-width: 0;
  overflow: hidden;
}

.doctor-nombre {
  display: block;
  font-weight: 500;
  color: var(--dark-color);
  margin-bottom: 4px;
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.doctor-subespecialidad {
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--secondary-color);
  font-size: 12px;
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.doctor-subespecialidad i {
  font-size: 10px;
  opacity: 0.7;
}

/* Modal de detalles con avatar */
.doctor-header {
  display: flex;
  align-items: center;
  gap: 20px;
  background-color: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.detail-profile-photo {
  flex-shrink: 0;
}

.doctor-text-info {
  flex: 1;
}

.doctor-text-info h3 {
  margin: 0 0 8px 0;
  color: var(--dark-color);
  font-size: 24px;
}

.doctor-especialidad {
  color: var(--primary-color);
  font-weight: 500;
  margin: 0;
  font-size: 16px;
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

.modal-small {
  max-width: 500px;
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
  border: none;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-block {
  width: 100%;
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

/* Gestión de fotos */
.quick-photo-edit {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.user-info-quick {
  text-align: center;
}

.user-info-quick h4 {
  margin-top: 10px;
  margin-bottom: 0;
  color: var(--dark-color);
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
  max-width: 250px;
}

/* Progress bar para uploads */
.upload-progress {
  width: 100%;
  max-width: 300px;
  text-align: center;
  margin: 0 auto;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background-color: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background-color: var(--primary-color);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: var(--secondary-color);
  font-weight: 500;
}

/* CSS Variables */
:root {
  --primary-color: #007bff;
  --secondary-color: #6c757d;
  --dark-color: #343a40;
}

/* Responsive */
@media (max-width: 1200px) {
  .doctores-table th,
  .doctores-table td {
    padding: 8px 10px;
    font-size: 14px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 3px;
  }
  
  .btn-icon {
    width: 28px;
    height: 28px;
  }
}

@media (max-width: 992px) {
  .doctor-info-completa {
    min-width: 160px;
    gap: 10px;
  }

  .doctor-subespecialidad {
    font-size: 11px;
  }

  .doctor-nombre {
    font-size: 13px;
  }

  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .filter-options {
    flex-direction: column;
    align-items: stretch;
  }
  
  .doctor-header {
    flex-direction: column;
    text-align: center;
    gap: 15px;
  }
}

@media (max-width: 768px) {
  .doctor-info-completa {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    min-width: 120px;
  }

  .doctor-detalles { 
    width: 100%; 
  }

  .doctor-nombre { 
    font-size: 12px; 
  }

  .doctor-subespecialidad { 
    font-size: 10px; 
  }

  .doctores-container {
    padding: 10px;
  }
  
  .doctores-table {
    font-size: 12px;
  }
  
  .doctores-table th,
  .doctores-table td {
    padding: 6px 8px;
  }
  
  .action-buttons {
    flex-direction: row;
    gap: 2px;
  }
  
  .btn-icon {
    width: 24px;
    height: 24px;
    font-size: 11px;
  }
  
  .modal-container {
    width: 95%;
    max-width: none;
    max-height: 95vh;
  }
  
  .modal-body {
    padding: 15px;
  }
}

@media (max-width: 576px) {
  .doctor-info-completa {
    flex-direction: row;
    align-items: center;
    gap: 6px;
    min-width: 140px;
  }

  .table-responsive {
    overflow-x: scroll;
  }
  
  .doctores-table {
    min-width: 800px;
  }
  
  .search-box {
    flex-direction: column;
  }
  
  .search-box input {
    border-radius: 4px 4px 0 0;
  }
  
  .search-btn {
    border-radius: 0 0 4px 4px;
  }
  
  .header-section {
    text-align: center;
  }
  
  .quick-actions {
    max-width: 200px;
  }
}

/* Animaciones para mejorar UX */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-container {
  animation: fadeIn 0.3s ease-out;
}

.doctor-avatar {
  transition: all 0.2s ease;
}

.doctor-avatar:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Tooltips para botones */
.btn-icon[title] {
  position: relative;
}

.btn-icon[title]:hover::after {
  content: attr(title);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: #333;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  z-index: 1000; 
  margin-bottom: 5px;
}

.btn-icon[title]:hover::before {
  content: '';
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: #333;
  z-index: 1000;
}
</style>