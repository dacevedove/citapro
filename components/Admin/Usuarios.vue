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
            <th>Información Adicional</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="usuario in usuarios" :key="usuario.id">
            <td>
              <div class="usuario-info">
                <strong>{{ usuario.nombre }} {{ usuario.apellido }}</strong>
                <small>{{ usuario.telefono }}</small>
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
            <td>{{ usuario.ultimo_acceso }}</td>
            <td>{{ usuario.info_adicional || '-' }}</td>
            <td>
              <div class="action-buttons">
                <button @click="editarUsuario(usuario)" class="btn-icon" title="Editar usuario">
                  <i class="fas fa-user-cog"></i>
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
            <h3>{{ usuarioEdit.nombre }} {{ usuarioEdit.apellido }}</h3>
            <p>{{ usuarioEdit.email }} - {{ usuarioEdit.cedula }}</p>
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
              <h4>Historial de cambios</h4>
              
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
                    <span class="log-fecha">{{ log.fecha_accion }}</span>
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
  </div>
</template>

<script>
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

export default {
  name: 'Usuarios',
  data() {
    return {
      usuarios: [],
      cargando: false,
      mostrarModalNuevo: false,
      mostrarModalEditar: false,
      guardando: false,
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
        esta_activo: true
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
        esta_activo: usuario.esta_activo
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
      
      try {
        const token = localStorage.getItem('token');
        const url = `/api/usuarios/logs_auditoria.php?usuario_id=${this.usuarioEdit.id}&limite=20`;
        
        console.log('Cargando logs para usuario:', this.usuarioEdit.id);
        console.log('URL:', url);
        
        const response = await axios.get(url, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        console.log('Respuesta de logs:', response.data);
        this.logsUsuario = response.data;
        
        if (this.logsUsuario.length === 0) {
          console.log('No se encontraron logs para este usuario');
        }
      } catch (error) {
        console.error('Error al cargar logs:', error);
        console.error('Response data:', error.response?.data);
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
        'UPDATE': 'Actualización general',
        // Mantener compatibilidad con nombres anteriores
        'UPDATE_ACTUALIZAR_DATOS': 'Actualización de datos',
        'UPDATE_CAMBIAR_EMAIL': 'Cambio de email',
        'UPDATE_CAMBIAR_PASSWORD': 'Cambio de contraseña',
        'UPDATE_RESETEAR_PASSWORD': 'Reseteo de contraseña'
      };
      return acciones[accion] || 'Modificación';
    },
    
    getRolClass(role) {
      const classes = {
        'admin': 'rol-admin',
        'doctor': 'rol-doctor',
        'aseguradora': 'rol-aseguradora',
        'paciente': 'rol-paciente',
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

.table-responsive {
  overflow-x: auto;
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

.rol-vertice {
  background-color: #fd7e14;
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

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.checkbox-group input[type="checkbox"] {
  width: auto;
}

.usuario-info-edit {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.usuario-info-edit h3 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.usuario-info-edit p {
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
}

.edit-tabs .tab-btn.active {
  color: var(--primary-color);
  border-bottom-color: var(--primary-color);
}

.edit-tabs .tab-btn:hover {
  color: var(--primary-color);
}

/* Acciones de contraseña */
.password-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

/* Contenedor de logs */
.logs-container {
  max-height: 400px;
  overflow-y: auto;
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

.btn-warning {
  background-color: #ffc107;
  border: 1px solid #ffc107;
  color: #212529;
}

.btn-warning:hover {
  background-color: #e0a800;
  border-color: #d39e00;
}

.btn-danger {
  background-color: #dc3545;
  border: 1px solid #dc3545;
  color: white;
}

.btn-danger:hover {
  background-color: #c82333;
  border-color: #bd2130;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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
  
  .edit-tabs {
    flex-wrap: wrap;
  }
  
  .edit-tabs .tab-btn {
    flex: 1;
    min-width: 120px;
  }
  
  .password-actions {
    flex-direction: column;
  }
}
</style>