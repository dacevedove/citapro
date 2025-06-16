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
              <div class="usuario-info">
                <ProfilePhoto
                  :photo-url="usuario.foto_perfil"
                  :user-name="usuario.nombre + ' ' + usuario.apellido"
                  :initials="getInitials(usuario.nombre, usuario.apellido)"
                  size="sm"
                  :show-initials="true"
                  :border="false"
                  @click="editarFotoUsuario(usuario)"
                  :clickable="true"
                  class="usuario-foto"
                />
                <div class="usuario-text">
                  <strong>{{ usuario.nombre }} {{ usuario.apellido }}</strong>
                  <small v-if="usuario.telefono">{{ usuario.telefono }}</small>
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
    
    <!-- Resto de modals igual... -->
  </div>
</template>

<style scoped>
/* CSS anterior + estos estilos nuevos/modificados */

.usuario-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.usuario-foto {
  flex-shrink: 0;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.usuario-foto:hover {
  transform: scale(1.1);
}

.usuario-text {
  flex: 1;
  min-width: 0; /* Permite que el texto se trunque si es necesario */
}

.usuario-text strong {
  display: block;
  font-weight: 500;
  color: var(--dark-color);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.usuario-text small {
  display: block;
  color: var(--secondary-color);
  font-size: 12px;
  margin-top: 2px;
}

/* Ajustar el ancho de la primera columna */
.usuarios-table th:first-child,
.usuarios-table td:first-child {
  width: 200px; /* Ajustar según necesidad */
  min-width: 180px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .usuario-info {
    gap: 8px;
  }
  
  .usuario-text strong {
    font-size: 14px;
  }
  
  .usuario-text small {
    font-size: 11px;
  }
  
  .usuarios-table th:first-child,
  .usuarios-table td:first-child {
    width: 150px;
    min-width: 140px;
  }
}

@media (max-width: 576px) {
  .usuario-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    text-align: left;
  }
  
  .usuario-foto {
    align-self: center;
  }
  
  .usuarios-table th:first-child,
  .usuarios-table td:first-child {
    width: 120px;
    min-width: 110px;
  }
}
</style>