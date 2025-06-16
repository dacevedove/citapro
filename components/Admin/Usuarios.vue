<template>
  <div class="usuarios-container">
    <h1>Gestión de Usuarios</h1>
    
    <div class="header-section">
      <button @click="abrirModalNuevo" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Usuario
      </button>
    </div>
    
    <div class="filters-section">
      <div class="search-box">
        <input 
          type="text" 
          v-model="filtros.busqueda"
          placeholder="Buscar por nombre, email o cédula" 
          class="form-control"
          @input="aplicarFiltros"
        >
        <button class="search-btn">
          <i class="fas fa-search"></i>
        </button>
      </div>
      
      <div class="filter-options">
        <div class="filter-item">
          <label>Rol:</label>
          <select v-model="filtros.role" class="form-control" @change="aplicarFiltros">
            <option value="">Todos</option>
            <option value="admin">Administrador</option>
            <option value="doctor">Doctor</option>
            <option value="aseguradora">Aseguradora</option>
            <option value="paciente">Paciente</option>
            <option value="coordinador">Coordinador</option>
            <option value="vertice">Vértice</option>
          </select>
        </div>
        
        <div class="filter-item">
          <label>Estado:</label>
          <select v-model="filtros.activo" class="form-control" @change="aplicarFiltros">
            <option value="">Todos</option>
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        
        <button @click="resetearFiltros" class="btn btn-outline">
          <i class="fas fa-sync"></i> Resetear
        </button>
      </div>
    </div>
    
    <div v-if="cargando" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando usuarios...</p>
    </div>
    
    <div v-else-if="usuarios.length === 0" class="empty-state">
      <i class="fas fa-users"></i>
      <p>No hay usuarios registrados</p>
      <button @click="abrirModalNuevo" class="btn btn-primary">
        Agregar Nuevo Usuario
      </button>
    </div>
    
    <div v-else class="table-responsive">
      <table class="usuarios-table">
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Email</th>
            <th>Cédula</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Último Acceso</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="usuario in usuarios" :key="usuario.id">
            <td>
              <div class="usuario-info-completa">
                <ProfilePhoto
                  :photo-url="usuario.foto_perfil"
                  :user-name="usuario.nombre + ' ' + usuario.apellido"
                  :initials="getInitials(usuario.nombre, usuario.apellido)"
                  size="sm"
                  :show-initials="true"
                  :border="true"
                  @click="editarFotoUsuario(usuario)"
                  :clickable="true"
                  class="usuario-avatar"
                />
                <div class="usuario-detalles">
                  <strong class="usuario-nombre">{{ usuario.nombre }} {{ usuario.apellido }}</strong>
                  <small class="usuario-telefono">
                    <i class="fas fa-phone"></i> {{ usuario.telefono || 'Sin teléfono' }}
                  </small>
                </div>
              </div>
            </td>
            <td>{{ usuario.email }}</td>
            <td>{{ usuario.cedula }}</td>
            <td>
              <span :class="getRolClass(usuario.role)">
                {{ formatRole(usuario.role) }}
              </span>
            </td>
            <td>
              <span :class="getEstadoClass(usuario.esta_activo)">
                {{ usuario.esta_activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td>{{ formatDate(usuario.ultimo_acceso) }}</td>
            <td>
              <div class="action-buttons">
                <button @click="editarUsuario(usuario)" class="btn-icon" title="Editar usuario">
                  <i class="fas fa-user-cog"></i>
                </button>
                <button @click="editarFotoUsuario(usuario)" class="btn-icon" title="Cambiar foto">
                  <i class="fas fa-camera"></i>
                </button>
                <button 
                  @click="toggleEstadoUsuario(usuario)" 
                  class="btn-icon" 
                  :title="usuario.esta_activo ? 'Desactivar' : 'Activar'"
                >
                  <i :class="usuario.esta_activo ? 'fas fa-user-slash' : 'fas fa-user-check'"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Modal para crear usuario -->
    <div v-if="mostrarModalNuevo" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>Nuevo Usuario</h2>
          <button @click="cerrarModalNuevo" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="crearUsuario">
            <div class="form-row">
              <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input 
                  type="text" 
                  id="nombre" 
                  v-model="nuevoUsuario.nombre" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="apellido">Apellido:</label>
                <input 
                  type="text" 
                  id="apellido" 
                  v-model="nuevoUsuario.apellido" 
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
                v-model="nuevoUsuario.cedula" 
                required
                class="form-control"
              >
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="email">Email:</label>
                <input 
                  type="email" 
                  id="email" 
                  v-model="nuevoUsuario.email" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="telefono">Teléfono:</label>
                <input 
                  type="tel" 
                  id="telefono" 
                  v-model="nuevoUsuario.telefono" 
                  class="form-control"
                >
              </div>
            </div>
            
            <div class="form-group">
              <label for="role">Rol:</label>
              <select 
                id="role" 
                v-model="nuevoUsuario.role" 
                required
                class="form-control"
              >
                <option value="" disabled>Seleccione un rol</option>
                <option value="admin">Administrador</option>
                <option value="doctor">Doctor</option>
                <option value="aseguradora">Aseguradora</option>
                <option value="paciente">Paciente</option>
                <option value="coordinador">Coordinador</option>
                <option value="vertice">Vértice</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="password">Contraseña:</label>
              <input 
                type="password" 
                id="password" 
                v-model="nuevoUsuario.password" 
                required
                class="form-control"
                placeholder="Mínimo 8 caracteres"
                minlength="8"
              >
            </div>
            
            <div class="form-group">
              <label for="confirmPassword">Confirmar Contraseña:</label>
              <input 
                type="password" 
                id="confirmPassword" 
                v-model="nuevoUsuario.confirmPassword" 
                required
                class="form-control"
                placeholder="Repita la contraseña"
                minlength="8"
              >
            </div>
            
            <div class="form-group">
              <div class="checkbox-group">
                <input type="checkbox" id="esta_activo" v-model="nuevoUsuario.esta_activo">
                <label for="esta_activo">Usuario activo</label>
              </div>
            </div>
            
            <div v-if="error" class="alert alert-danger">
              {{ error }}
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="cerrarModalNuevo" class="btn btn-outline">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="guardando">
                {{ guardando ? 'Creando...' : 'Crear Usuario' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Modal para editar usuario -->
    <div v-if="mostrarModalEditar" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>Editar Usuario</h2>
          <button @click="cerrarModalEditar" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="usuario-info-edit">
            <ProfilePhoto
              :photo-url="usuarioEdit.foto_perfil"
              :user-name="usuarioEdit.nombre + ' ' + usuarioEdit.apellido"
              :initials="getInitials(usuarioEdit.nombre, usuarioEdit.apellido)"
              size="lg"
              :show-initials="true"
              :border="true"
              class="edit-profile-photo"
            />
            <div class="usuario-text-info">
              <h3>{{ usuarioEdit.nombre }} {{ usuarioEdit.apellido }}</h3>
              <p>{{ usuarioEdit.email }} - {{ usuarioEdit.cedula }}</p>
            </div>
          </div>
          
          <!-- Tabs para diferentes tipos de edición -->
          <div class="edit-tabs">
            <button 
              :class="['tab-btn', tipoEdicion === 'datos' ? 'active' : '']" 
              @click="tipoEdicion = 'datos'"
            >
              Datos Básicos
            </button>
            <button 
              :class="['tab-btn', tipoEdicion === 'foto' ? 'active' : '']" 
              @click="tipoEdicion = 'foto'"
            >
              Foto de Perfil
            </button>
            <button 
              :class="['tab-btn', tipoEdicion === 'email' ? 'active' : '']" 
              @click="tipoEdicion = 'email'"
            >
              Cambiar Email
            </button>
            <button 
              :class="['tab-btn', tipoEdicion === 'password' ? 'active' : '']" 
              @click="tipoEdicion = 'password'"
            >
              Cambiar Contraseña
            </button>
            <button 
              :class="['tab-btn', tipoEdicion === 'logs' ? 'active' : '']" 
              @click="tipoEdicion = 'logs'; cargarLogsUsuario()"
            >
              Historial
            </button>
          </div>
          
          <!-- Datos básicos -->
          <div v-if="tipoEdicion === 'datos'">
            <form @submit.prevent="actualizarDatos">
              <div class="form-group">
                <label for="edit-nombre">Nombre:</label>
                <input 
                  type="text" 
                  id="edit-nombre" 
                  v-model="usuarioEdit.nombre" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="edit-apellido">Apellido:</label>
                <input 
                  type="text" 
                  id="edit-apellido" 
                  v-model="usuarioEdit.apellido" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="edit-telefono">Teléfono:</label>
                <input 
                  type="text" 
                  id="edit-telefono" 
                  v-model="usuarioEdit.telefono" 
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="edit-role">Rol:</label>
                <select 
                  id="edit-role" 
                  v-model="usuarioEdit.role" 
                  required
                  class="form-control"
                  :disabled="usuarioEdit.id === currentUserId"
                >
                  <option value="admin">Administrador</option>
                  <option value="doctor">Doctor</option>
                  <option value="aseguradora">Aseguradora</option>
                  <option value="paciente">Paciente</option>
                  <option value="coordinador">Coordinador</option>
                  <option value="vertice">Vértice</option>
                </select>
                <small v-if="usuarioEdit.id === currentUserId" class="text-muted">
                  No puede cambiar su propio rol
                </small>
              </div>
              
              <div class="form-group">
                <div class="checkbox-group">
                  <input 
                    type="checkbox" 
                    id="edit_esta_activo" 
                    v-model="usuarioEdit.esta_activo"
                    :disabled="usuarioEdit.id === currentUserId"
                  >
                  <label for="edit_esta_activo">Usuario activo</label>
                </div>
                <small v-if="usuarioEdit.id === currentUserId" class="text-muted">
                  No puede desactivarse a sí mismo
                </small>
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalEditar" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="guardando">
                  {{ guardando ? 'Actualizando...' : 'Actualizar Datos' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Gestión de Foto de Perfil -->
          <div v-if="tipoEdicion === 'foto'" class="photo-edit-section">
            <div class="current-photo-display">
              <h4>Foto Actual</h4>
              <ProfilePhoto
                :photo-url="usuarioEdit.foto_perfil"
                :user-name="usuarioEdit.nombre + ' ' + usuarioEdit.apellido"
                :initials="getInitials(usuarioEdit.nombre, usuarioEdit.apellido)"
                size="xl"
                :show-initials="true"
                :border="true"
                class="large-profile-photo"
              />
            </div>
            
            <div class="photo-actions-admin">
              <div class="upload-section">
                <label for="admin-photo-upload" class="btn btn-primary">
                  <i class="fas fa-camera"></i>
                  {{ usuarioEdit.foto_perfil ? 'Cambiar Foto' : 'Subir Foto' }}
                </label>
                <input 
                  type="file" 
                  id="admin-photo-upload" 
                  accept="image/*" 
                  @change="handleAdminPhotoUpload"
                  style="display: none;"
                >
              </div>
              
              <div v-if="usuarioEdit.foto_perfil" class="delete-section">
                <button 
                  @click="eliminarFotoUsuario" 
                  class="btn btn-danger"
                  :disabled="uploadingPhoto"
                >
                  <i class="fas fa-trash"></i>
                  Eliminar Foto
                </button>
              </div>
            </div>
            
            <!-- Progress bar para upload -->
            <div v-if="uploadingPhoto" class="upload-progress">
              <div class="progress-bar">
                <div class="progress-fill" :style="{width: uploadProgress + '%'}"></div>
              </div>
              <span class="progress-text">Subiendo... {{ uploadProgress }}%</span>
            </div>
            
            <div class="photo-info">
              <small><i class="fas fa-info-circle"></i> JPG, PNG, WebP • Máx. 5MB</small>
            </div>

            <div class="modal-footer">
              <button type="button" @click="cerrarModalEditar" class="btn btn-outline">Cerrar</button>
            </div>
          </div>
          
          <!-- Cambiar Email -->
          <div v-if="tipoEdicion === 'email'">
            <form @submit.prevent="cambiarEmail">
              <div class="form-group">
                <label for="email-actual">Email actual:</label>
                <input 
                  type="email" 
                  id="email-actual" 
                  :value="usuarioEdit.email" 
                  disabled
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="nuevo-email">Nuevo email:</label>
                <input 
                  type="email" 
                  id="nuevo-email" 
                  v-model="nuevoEmail" 
                  required
                  class="form-control"
                  :disabled="usuarioEdit.id === currentUserId"
                >
                <small v-if="usuarioEdit.id === currentUserId" class="text-muted">
                  No puede cambiar su propio email desde aquí
                </small>
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalEditar" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-warning" :disabled="guardando || usuarioEdit.id === currentUserId">
                  {{ guardando ? 'Cambiando...' : 'Cambiar Email' }}
                </button>
              </div>
            </form>
          </div>
          
          <!-- Cambiar Contraseña -->
          <div v-if="tipoEdicion === 'password'">
            <form @submit.prevent="cambiarPassword">
              <div class="form-group">
                <label for="nueva-password">Nueva contraseña:</label>
                <input 
                  type="password" 
                  id="nueva-password" 
                  v-model="nuevaPassword" 
                  required
                  class="form-control"
                  placeholder="Mínimo 8 caracteres"
                  minlength="8"
                >
              </div>
              
              <div class="form-group">
                <label for="confirmar-password">Confirmar contraseña:</label>
                <input 
                  type="password" 
                  id="confirmar-password" 
                  v-model="confirmarPassword" 
                  required
                  class="form-control"
                  placeholder="Repita la nueva contraseña"
                  minlength="8"
                >
              </div>
              
              <div class="password-actions">
                <button type="submit" class="btn btn-warning" :disabled="guardando">
                  {{ guardando ? 'Cambiando...' : 'Cambiar Contraseña' }}
                </button>
                <button type="button" @click="resetearPassword" class="btn btn-danger" :disabled="guardando">
                  {{ guardando ? 'Reseteando...' : 'Resetear Contraseña' }}
                </button>
              </div>
              
              <div v-if="passwordTemporal" class="alert alert-info">
                <strong>Contraseña temporal generada:</strong> {{ passwordTemporal }}
                <br><small>Guarde esta contraseña y compártala de forma segura con el usuario.</small>
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalEditar" class="btn btn-outline">Cancelar</button>
              </div>
            </form>
          </div>
          
          <!-- Historial de cambios -->
          <div v-if="tipoEdicion === 'logs'">
            <div class="logs-container">
              <div class="logs-header">
                <h4>Historial de cambios</h4>
                <button @click="cargarLogsUsuario" class="btn btn-sm btn-outline" :disabled="cargandoLogs">
                  <i class="fas fa-sync"></i> Actualizar
                </button>
              </div>
              
              <div v-if="cargandoLogs" class="loading-small">
                <div class="spinner-small"></div>
                <p>Cargando historial...</p>
              </div>
              
              <div v-else-if="logsUsuario.length === 0" class="empty-logs">
                <p>No hay cambios registrados para este usuario.</p>
              </div>
              
              <div v-else class="logs-list">
                <div v-for="log in logsUsuario" :key="log.id" class="log-item">
                  <div class="log-header">
                    <span class="log-fecha">{{ formatDate(log.fecha_accion) }}</span>
                    <span class="log-accion">{{ formatAccion(log.accion) }}</span>
                  </div>
                  <div class="log-body">
                    <p class="log-admin"><strong>Administrador:</strong> {{ log.admin_completo }}</p>
                    <p class="log-cambios"><strong>Detalles:</strong> {{ log.resumen_cambios }}</p>
                    <p v-if="log.direccion_ip" class="log-ip"><strong>Desde IP:</strong> {{ log.direccion_ip }}</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="cerrarModalEditar" class="btn btn-outline">Cerrar</button>
            </div>
          </div>
          
          <div v-if="error" class="alert alert-danger">
            {{ error }}
          </div>
        </div>
      </div>
    </div>

    <!-- Modal rápido para cambiar foto -->
    <div v-if="mostrarModalFoto" class="modal-overlay">
      <div class="modal-container modal-small">
        <div class="modal-header">
          <h2>Cambiar Foto de Perfil</h2>
          <button @click="cerrarModalFoto" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="quick-photo-edit">
            <div class="user-info-quick">
              <ProfilePhoto
                :photo-url="usuarioFoto.foto_perfil"
                :user-name="usuarioFoto.nombre + ' ' + usuarioFoto.apellido"
                :initials="getInitials(usuarioFoto.nombre, usuarioFoto.apellido)"
                size="lg"
                :show-initials="true"
                :border="true"
              />
              <h4>{{ usuarioFoto.nombre }} {{ usuarioFoto.apellido }}</h4>
            </div>
            
            <div class="quick-actions">
              <label for="quick-photo-upload" class="btn btn-primary btn-block">
                <i class="fas fa-camera"></i>
                {{ usuarioFoto.foto_perfil ? 'Cambiar Foto' : 'Subir Foto' }}
              </label>
              <input 
                type="file" 
                id="quick-photo-upload" 
                accept="image/*" 
                @change="handleQuickPhotoUpload"
                style="display: none;"
              >
              
              <button 
                v-if="usuarioFoto.foto_perfil" 
                @click="eliminarFotoRapido" 
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
import { useAuthStore } from '../../store/auth';
import ProfilePhoto from '../Shared/ProfilePhoto.vue';

export default {
  name: 'Usuarios',
  components: {
    ProfilePhoto
  },
  data() {
    return {
      usuarios: [],
      cargando: false,
      mostrarModalNuevo: false,
      mostrarModalEditar: false,
      mostrarModalFoto: false,
      guardando: false,
      uploadingPhoto: false,
      uploadProgress: 0,
      error: null,
      filtros: {
        busqueda: '',
        role: '',
        activo: ''
      },
      nuevoUsuario: {
        nombre: '',
        apellido: '',
        cedula: '',
        email: '',
        telefono: '',
        role: '',
        password: '',
        confirmPassword: '',
        esta_activo: true
      },
      usuarioEdit: {
        id: null,
        nombre: '',
        apellido: '',
        email: '',
        cedula: '',
        telefono: '',
        role: '',
        esta_activo: true,
        foto_perfil: null
      },
      usuarioFoto: {
        id: null,
        nombre: '',
        apellido: '',
        foto_perfil: null
      },
      tipoEdicion: 'datos',
      nuevoEmail: '',
      nuevaPassword: '',
      confirmarPassword: '',
      passwordTemporal: '',
      logsUsuario: [],
      cargandoLogs: false
    }
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    currentUserId() {
      return this.authStore.user?.id;
    }
  },
  mounted() {
    this.cargarUsuarios();
  },
  methods: {
    getInitials(nombre, apellido) {
      const n = (nombre || '').charAt(0).toUpperCase();
      const a = (apellido || '').charAt(0).toUpperCase();
      return n + a || 'U';
    },

    formatDate(dateString) {
      if (!dateString) return '-';
      try {
        return new Date(dateString).toLocaleDateString('es-ES', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch {
        return dateString;
      }
    },

    // Modal de foto rápido
    editarFotoUsuario(usuario) {
      this.usuarioFoto = {
        id: usuario.id,
        nombre: usuario.nombre,
        apellido: usuario.apellido,
        foto_perfil: usuario.foto_perfil
      };
      this.mostrarModalFoto = true;
    },

    cerrarModalFoto() {
      this.mostrarModalFoto = false;
    },

    async handleQuickPhotoUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      if (!this.validarArchivo(file)) {
        event.target.value = '';
        return;
      }
      
      await this.subirFotoUsuario(file, this.usuarioFoto.id);
      event.target.value = '';
    },

    async handleAdminPhotoUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      if (!this.validarArchivo(file)) {
        event.target.value = '';
        return;
      }
      
      await this.subirFotoUsuario(file, this.usuarioEdit.id);
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

    async subirFotoUsuario(file, userId) {
      this.uploadingPhoto = true;
      this.uploadProgress = 0;
      this.error = null;
      
      try {
        const formData = new FormData();
        formData.append('photo', file);
        formData.append('user_id', userId);
        
        const token = localStorage.getItem('token');
        const response = await axios.post('/api/usuarios/upload_user_photo.php', formData, {
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
          
          // Actualizar en todos los lugares necesarios
          this.actualizarFotoEnTodosLados(userId, nuevaFotoUrl);
          
          alert('Foto de perfil actualizada correctamente');
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

    async eliminarFotoRapido() {
      if (!confirm('¿Está seguro de que desea eliminar la foto de perfil de este usuario?')) {
        return;
      }
      
      await this.eliminarFotoUsuarioInterno(this.usuarioFoto.id);
    },

    async eliminarFotoUsuario() {
      if (!confirm('¿Está seguro de que desea eliminar la foto de perfil de este usuario?')) {
        return;
      }
      
      await this.eliminarFotoUsuarioInterno(this.usuarioEdit.id);
    },

    async eliminarFotoUsuarioInterno(userId) {
      this.uploadingPhoto = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.delete('/api/usuarios/delete_user_photo.php', {
          headers: {
            'Authorization': 'Bearer ' + token
          },
          data: {
            user_id: userId
          }
        });
        
        if (response.data.success) {
          // Actualizar en todos los lugares necesarios
          this.actualizarFotoEnTodosLados(userId, null);
          
          alert('Foto de perfil eliminada correctamente');
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

    actualizarFotoEnTodosLados(userId, nuevaFotoUrl) {
      // Actualizar en la lista de usuarios
      const usuarioEnLista = this.usuarios.find(u => u.id == userId);
      if (usuarioEnLista) {
        usuarioEnLista.foto_perfil = nuevaFotoUrl;
      }
      
      // Actualizar en usuarioEdit si es el mismo
      if (this.usuarioEdit.id == userId) {
        this.usuarioEdit.foto_perfil = nuevaFotoUrl;
      }
      
      // Actualizar en usuarioFoto si es el mismo
      if (this.usuarioFoto.id == userId) {
        this.usuarioFoto.foto_perfil = nuevaFotoUrl;
      }
      
      // Si es el usuario actual logueado, actualizar el store también
      if (this.currentUserId == userId) {
        this.authStore.updateUserPhoto(nuevaFotoUrl);
      }
    },

    async cargarUsuarios() {
      this.cargando = true;
      
      try {
        const token = localStorage.getItem('token');
        let url = '/api/usuarios/listar.php';
        
        const params = new URLSearchParams();
        if (this.filtros.busqueda) {
          params.append('busqueda', this.filtros.busqueda);
        }
        if (this.filtros.role) {
          params.append('role', this.filtros.role);
        }
        if (this.filtros.activo !== '') {
          params.append('activo', this.filtros.activo);
        }
        
        if (params.toString()) {
          url += `?${params.toString()}`;
        }
        
        const response = await axios.get(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.usuarios = response.data;
      } catch (error) {
        console.error('Error al cargar usuarios:', error);
        this.error = 'Error al cargar usuarios';
      } finally {
        this.cargando = false;
      }
    },
    
    aplicarFiltros() {
      this.cargarUsuarios();
    },
    
    resetearFiltros() {
      this.filtros = {
        busqueda: '',
        role: '',
        activo: ''
      };
      this.cargarUsuarios();
    },
    
    abrirModalNuevo() {
      this.nuevoUsuario = {
        nombre: '',
        apellido: '',
        cedula: '',
        email: '',
        telefono: '',
        role: '',
        password: '',
        confirmPassword: '',
        esta_activo: true
      };
      this.error = null;
      this.mostrarModalNuevo = true;
    },
    
    cerrarModalNuevo() {
      this.mostrarModalNuevo = false;
    },
    
    editarUsuario(usuario) {
      this.usuarioEdit = {
        id: usuario.id,
        nombre: usuario.nombre,
        apellido: usuario.apellido,
        email: usuario.email,
        cedula: usuario.cedula,
        telefono: usuario.telefono || '',
        role: usuario.role,
        esta_activo: usuario.esta_activo,
        foto_perfil: usuario.foto_perfil
      };
      this.tipoEdicion = 'datos';
      this.nuevoEmail = '';
      this.nuevaPassword = '';
      this.confirmarPassword = '';
      this.passwordTemporal = '';
      this.error = null;
      this.mostrarModalEditar = true;
    },
    
    cerrarModalEditar() {
      this.mostrarModalEditar = false;
      this.logsUsuario = [];
    },
    
    async crearUsuario() {
      if (!this.nuevoUsuario.nombre || !this.nuevoUsuario.apellido || !this.nuevoUsuario.cedula || 
          !this.nuevoUsuario.email || !this.nuevoUsuario.role || !this.nuevoUsuario.password) {
        this.error = "Por favor, complete todos los campos obligatorios.";
        return;
      }
      
      if (this.nuevoUsuario.password.length < 8) {
        this.error = "La contraseña debe tener al menos 8 caracteres.";
        return;
      }
      
      if (this.nuevoUsuario.password !== this.nuevoUsuario.confirmPassword) {
        this.error = "Las contraseñas no coinciden.";
        return;
      }
      
      this.guardando = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post('/api/usuarios/crear.php', this.nuevoUsuario, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.success) {
          this.cerrarModalNuevo();
          this.cargarUsuarios();
          alert('Usuario creado correctamente');
        }
      } catch (error) {
        console.error('Error al crear usuario:', error);
        this.error = error.response?.data?.error || "Error al crear usuario. Por favor, intente nuevamente.";
      } finally {
        this.guardando = false;
      }
    },
    
    async actualizarDatos() {
      this.guardando = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post('/api/usuarios/editar_usuario.php', {
          user_id: this.usuarioEdit.id,
          accion: 'actualizar_datos',
          nombre: this.usuarioEdit.nombre,
          apellido: this.usuarioEdit.apellido,
          telefono: this.usuarioEdit.telefono,
          role: this.usuarioEdit.role,
          esta_activo: this.usuarioEdit.esta_activo
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.success) {
          this.cargarUsuarios();
          alert('Datos actualizados correctamente');
        }
      } catch (error) {
        console.error('Error al actualizar datos:', error);
        this.error = error.response?.data?.error || "Error al actualizar datos.";
      } finally {
        this.guardando = false;
      }
    },
    
    async cambiarEmail() {
      if (!this.nuevoEmail) {
        this.error = "Ingrese el nuevo email";
        return;
      }
      
      if (this.nuevoEmail === this.usuarioEdit.email) {
        this.error = "El nuevo email debe ser diferente al actual";
        return;
      }
      
      this.guardando = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post('/api/usuarios/editar_usuario.php', {
          user_id: this.usuarioEdit.id,
          accion: 'cambiar_email',
          nuevo_email: this.nuevoEmail
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.success) {
          this.usuarioEdit.email = this.nuevoEmail;
          this.nuevoEmail = '';
          this.cargarUsuarios();
          alert('Email cambiado correctamente');
        } else {
          this.error = response.data.error || 'Error desconocido';
        }
      } catch (error) {
        console.error('Error al cambiar email:', error);
        this.error = error.response?.data?.error || "Error al cambiar email.";
      } finally {
        this.guardando = false;
      }
    },
    
    async cambiarPassword() {
      if (!this.nuevaPassword || !this.confirmarPassword) {
        this.error = "Complete ambos campos de contraseña";
        return;
      }
      
      if (this.nuevaPassword.length < 8) {
        this.error = "La contraseña debe tener al menos 8 caracteres";
        return;
      }
      
      if (this.nuevaPassword !== this.confirmarPassword) {
        this.error = "Las contraseñas no coinciden";
        return;
      }
      
      this.guardando = true;
      this.error = null;
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post('/api/usuarios/editar_usuario.php', {
          user_id: this.usuarioEdit.id,
          accion: 'cambiar_password',
          nueva_password: this.nuevaPassword
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.success) {
          this.nuevaPassword = '';
          this.confirmarPassword = '';
          alert('Contraseña cambiada correctamente');
        }
      } catch (error) {
        console.error('Error al cambiar contraseña:', error);
        this.error = error.response?.data?.error || "Error al cambiar contraseña.";
      } finally {
        this.guardando = false;
      }
    },
    
    async resetearPassword() {
      if (!confirm('¿Está seguro de que desea resetear la contraseña? Se generará una contraseña temporal.')) {
        return;
      }
      
      this.guardando = true;
      this.error = null;
      this.passwordTemporal = '';
      
      try {
        const token = localStorage.getItem('token');
        
        const response = await axios.post('/api/usuarios/editar_usuario.php', {
          user_id: this.usuarioEdit.id,
          accion: 'resetear_password'
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.success) {
          this.passwordTemporal = response.data.password_temporal;
          alert('Contraseña reseteada correctamente');
        }
      } catch (error) {
        console.error('Error al resetear contraseña:', error);
        this.error = error.response?.data?.error || "Error al resetear contraseña.";
      } finally {
        this.guardando = false;
      }
    },
    
    async cargarLogsUsuario() {
      this.cargandoLogs = true;
      this.logsUsuario = [];
      
      try {
        const token = localStorage.getItem('token');
        const url = `/api/usuarios/logs_auditoria.php?usuario_id=${this.usuarioEdit.id}&limite=50`;
        
        const response = await axios.get(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (Array.isArray(response.data)) {
          this.logsUsuario = response.data;
        } else {
          this.logsUsuario = [];
        }
      } catch (error) {
        console.error('Error al cargar logs:', error);
        this.logsUsuario = [];
      } finally {
        this.cargandoLogs = false;
      }
    },
    
    async toggleEstadoUsuario(usuario) {
      if (usuario.id === this.currentUserId) {
        alert('No puede desactivarse a sí mismo');
        return;
      }
      
      const accion = usuario.esta_activo ? 'desactivar' : 'activar';
      if (!confirm(`¿Está seguro de que desea ${accion} este usuario?`)) {
        return;
      }
      
      try {
        const token = localStorage.getItem('token');
        
        await axios.post('/api/usuarios/editar_usuario.php', {
          user_id: usuario.id,
          accion: 'actualizar_datos',
          role: usuario.role,
          esta_activo: !usuario.esta_activo
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.cargarUsuarios();
      } catch (error) {
        console.error('Error al cambiar estado del usuario:', error);
        alert('Error al cambiar el estado del usuario');
      }
    },
    
    formatRole(role) {
      const roles = {
        'admin': 'Administrador',
        'doctor': 'Doctor',
        'aseguradora': 'Aseguradora',
        'paciente': 'Paciente',
        'coordinador': 'Coordinador',
        'vertice': 'Vértice'
      };
      return roles[role] || role;
    },
    
    formatAccion(accion) {
      const acciones = {
        'INSERT': 'Creación de usuario',
        'UPDATE_DATOS': 'Actualización de datos',
        'UPDATE_EMAIL': 'Cambio de email',
        'UPDATE_PASS': 'Cambio de contraseña',
        'RESET_PASS': 'Reseteo de contraseña',
        'UPDATE_PHOTO': 'Cambio de foto',
        'DELETE_PHOTO': 'Eliminación de foto',
        'UPDATE': 'Actualización general'
      };
      
      return acciones[accion] || accion;
    },
    
    getRolClass(role) {
      const classes = {
        'admin': 'rol-admin',
        'doctor': 'rol-doctor',
        'aseguradora': 'rol-aseguradora',
        'paciente': 'rol-paciente',
        'coordinador': 'rol-coordinador',
        'vertice': 'rol-vertice'
      };
      return classes[role] || '';
    },
    
    getEstadoClass(activo) {
      return activo ? 'estado-activo' : 'estado-inactivo';
    }
  }
}
</script>

<style scoped>
.usuarios-container {
  max-width: 1400px;
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

.usuarios-table {
  width: 100%;
  border-collapse: collapse;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.usuarios-table th,
.usuarios-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.usuarios-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: var(--dark-color);
}

.usuarios-table th:first-child,
.usuarios-table td:first-child {
  width: 60px;
  text-align: center;
}

.table-responsive {
  overflow-x: auto;
}

.table-photo {
  cursor: pointer;
  transition: transform 0.2s ease;
}

.table-photo:hover {
  transform: scale(1.1);
}

.usuario-info strong {
  display: block;
  font-weight: 500;
}

.usuario-info small {
  color: var(--secondary-color);
  font-size: 12px;
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

/* Estilos para roles */
.rol-admin {
  background-color: #dc3545;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.rol-doctor {
  background-color: #28a745;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.rol-aseguradora {
  background-color: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.rol-paciente {
  background-color: #6c757d;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.rol-coordinador {
  background-color: #fd7e14;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.rol-vertice {
  background-color: #6f42c1;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

/* Estilos para estados */
.estado-activo {
  color: #28a745;
  font-weight: 500;
}

.estado-inactivo {
  color: #dc3545;
  font-weight: 500;
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
  max-width: 700px;
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
  margin-top: 20px;
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

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.checkbox-group input[type="checkbox"] {
  width: auto;
}

/* Usuario info en modal de edición */
.usuario-info-edit {
  display: flex;
  align-items: center;
  gap: 20px;
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.edit-profile-photo {
  flex-shrink: 0;
}

.usuario-text-info {
  flex: 1;
}

.usuario-text-info h3 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.usuario-text-info p {
  margin: 0;
  color: var(--secondary-color);
}

.text-muted {
  color: #6c757d;
  font-size: 12px;
}

/* Tabs de edición */
.edit-tabs {
  display: flex;
  margin-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
  flex-wrap: wrap;
}

.edit-tabs .tab-btn {
  padding: 10px 15px;
  border: none;
  background: none;
  cursor: pointer;
  font-weight: 500;
  color: #6c757d;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}

.edit-tabs .tab-btn.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

.edit-tabs .tab-btn:hover {
  color: var(--primary-color);
}

/* Sección de edición de foto */
.photo-edit-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.current-photo-display {
  text-align: center;
}

.current-photo-display h4 {
  margin-bottom: 15px;
  color: var(--dark-color);
}

.large-profile-photo {
  margin: 0 auto;
}

.photo-actions-admin {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
}

.upload-section,
.delete-section {
  display: flex;
  justify-content: center;
}

/* Modal rápido de foto */
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

.photo-info {
  text-align: center;
  margin-top: 10px;
}

.photo-info small {
  color: #6c757d;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

/* Acciones de contraseña */
.password-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

/* Contenedor de logs */
.logs-container {
  max-height: 500px;
  overflow-y: auto;
}

.logs-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.logs-header h4 {
  margin: 0;
}

.logs-list {
  space-y: 10px;
}

.log-item {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 12px;
  margin-bottom: 10px;
}

.log-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.log-fecha {
  font-size: 12px;
  color: #6c757d;
}

.log-accion {
  background-color: var(--primary-color);
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.log-body {
  font-size: 13px;
}

.log-body p {
  margin: 4px 0;
}

.log-admin {
  color: #495057;
  font-size: 12px;
}

.log-cambios {
  color: #212529;
  font-weight: 500;
  background-color: #e9ecef;
  padding: 6px 8px;
  border-radius: 4px;
  margin: 8px 0;
}

.log-ip {
  color: #6c757d;
  font-size: 11px;
}

.loading-small {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20px;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
}

.empty-logs {
  text-align: center;
  padding: 20px;
  color: #6c757d;
}

/* Alerts */
.alert {
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.alert-info {
  background-color: #d1ecf1;
  border: 1px solid #bee5eb;
  color: #0c5460;
}

.alert-danger {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

/* Botones */
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

.btn-sm {
  padding: 5px 10px;
  font-size: 12px;
}

.btn-block {
  width: 100%;
}

.btn-primary {
  background-color: var(--primary-color);
  border: 1px solid var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
  border-color: #004085;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-outline:hover:not(:disabled) {
  background-color: #e9ecef;
}

.btn-warning {
  background-color: #ffc107;
  border: 1px solid #ffc107;
  color: #212529;
}

.btn-warning:hover:not(:disabled) {
  background-color: #e0a800;
  border-color: #d39e00;
}

.btn-danger {
  background-color: #dc3545;
  border: 1px solid #dc3545;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background-color: #c82333;
  border-color: #bd2130;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* CSS Variables */
:root {
  --primary-color: #007bff;
  --secondary-color: #6c757d;
  --dark-color: #343a40;
}

/* Responsive */
@media (max-width: 1200px) {
  .usuarios-table th,
  .usuarios-table td {
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
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .filter-options {
    flex-direction: column;
    align-items: stretch;
  }
  
  .edit-tabs {
    flex-wrap: wrap;
  }
  
  .edit-tabs .tab-btn {
    flex: 1;
    min-width: 100px;
    font-size: 12px;
    padding: 8px 10px;
  }
  
  .usuario-info-edit {
    flex-direction: column;
    text-align: center;
    gap: 15px;
  }
  
  .photo-actions-admin {
    flex-direction: column;
    align-items: center;
  }
  
  .password-actions {
    flex-direction: column;
  }
}

@media (max-width: 768px) {
  .usuarios-container {
    padding: 10px;
  }
  
  .usuarios-table {
    font-size: 12px;
  }
  
  .usuarios-table th,
  .usuarios-table td {
    padding: 6px 8px;
  }
  
  .usuario-info strong {
    font-size: 13px;
  }
  
  .usuario-info small {
    font-size: 10px;
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
  
  .logs-container {
    max-height: 300px;
  }
  
  .edit-tabs .tab-btn {
    padding: 6px 8px;
    font-size: 11px;
  }
  
  .rol-admin,
  .rol-doctor,
  .rol-aseguradora,
  .rol-paciente,
  .rol-coordinador,
  .rol-vertice {
    font-size: 10px;
    padding: 2px 6px;
  }
}

@media (max-width: 576px) {
  .table-responsive {
    overflow-x: scroll;
  }
  
  .usuarios-table {
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

.log-item {
  transition: all 0.2s ease;
}

.log-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.table-photo {
  transition: all 0.2s ease;
}

.table-photo:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Estados de carga mejorados */
.btn:disabled {
  position: relative;
}

.btn:disabled::after {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  margin: auto;
  border: 2px solid transparent;
  border-top-color: currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

/* Indicadores visuales mejorados */
.upload-progress {
  background: rgba(255, 255, 255, 0.9);
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.progress-bar {
  background: #e9ecef;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.progress-fill {
  background: linear-gradient(90deg, var(--primary-color), #0056b3);
  box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
}

/* Mejoras de accesibilidad */
.btn:focus,
.form-control:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.tab-btn:focus {
  outline: none;
  background-color: rgba(0, 123, 255, 0.1);
}

.btn-icon:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

/* Tooltips mejorados */
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