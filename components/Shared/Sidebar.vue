// Archivo: components/Shared/Sidebar.vue (Actualizado)
<template>
  <div class="sidebar" :class="{ collapsed: isCollapsed }">
    <div class="sidebar-header">
      <span class="clinic-name">La Guerra Mendez</span>
      <button class="collapse-btn" @click="toggleCollapse">
        <i class="fas" :class="isCollapsed ? 'fa-angle-right' : 'fa-angle-left'"></i>
      </button>
    </div>
    
    <div class="sidebar-content">

      <!-- Menú para coordinador -->
      <div v-if="userRole === 'coordinador'" class="sidebar-menu">
        <div class="menu-title">Panel de Coordinación</div>
        <ul class="menu-items">
          <li>
            <router-link to="/crear-cita" class="menu-item menu-item-highlight">
              <i class="fas fa-plus-circle"></i>
              <span>Nueva Cita</span>
            </router-link>
          </li>
          <li>
            <router-link to="/coordinador/dashboard" class="menu-item">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </router-link>
          </li>
          <li>
            <router-link to="/coordinador/citas" class="menu-item">
              <i class="fas fa-calendar-check"></i>
              <span>Gestión de Citas</span>
            </router-link>
          </li>
          <li class="menu-group">
            <div class="menu-item-group" @click="toggleSubmenu('doctores')">
              <i class="fas fa-user-md"></i>
              <span>Doctores</span>
              <i class="fas fa-chevron-down submenu-arrow" :class="{ 'rotated': openSubmenus.doctores }"></i>
            </div>
            <ul class="submenu" v-show="openSubmenus.doctores">
              <li>
                <router-link to="/coordinador/doctores" class="submenu-item">
                  <i class="fas fa-users"></i>
                  <span>Lista de Doctores</span>
                </router-link>
              </li>
              <li>
                <router-link to="/coordinador/horarios" class="submenu-item">
                  <i class="fas fa-calendar"></i>
                  <span>Horarios</span>
                </router-link>
              </li>
              <li>
                <router-link to="/coordinador/especialidades" class="submenu-item">
                  <i class="fas fa-stethoscope"></i>
                  <span>Especialidades</span>
                </router-link>
              </li>
              <li>
                <router-link to="/coordinador/tipos-bloque" class="submenu-item">
                  <i class="fas fa-clock"></i>
                  <span>Tipos de Bloque</span>
                </router-link>
              </li>
            </ul>
          </li>
        </ul>
      </div>

      <!-- Menú para vértice -->
      <div v-if="userRole === 'vertice'" class="sidebar-menu">
        <div class="menu-title">Panel de Vértice</div>
        <ul class="menu-items">
          <li>
            <router-link to="/vertice/solicitudes" class="menu-item">
              <i class="fas fa-clipboard-list"></i>
              <span>Solicitudes</span>
            </router-link>
          </li>
          <li>
            <router-link to="/vertice/horarios" class="menu-item">
              <i class="fas fa-clock"></i>
              <span>Horarios</span>
            </router-link>
          </li>
          <li>
            <router-link to="/vertice/doctores" class="menu-item">
              <i class="fas fa-user-md"></i>
              <span>Doctores</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Menú para administrador (Dirección Médica) -->
      <div v-if="userRole === 'admin'" class="sidebar-menu">
        <div class="menu-title">Dirección Médica</div>
        <ul class="menu-items">
          <li>
            <router-link to="/admin/dashboard" class="menu-item">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </router-link>
          </li>
          <li>
            <router-link to="/crear-cita" class="menu-item menu-item-highlight">
              <i class="fas fa-plus-circle"></i>
              <span>Nueva Cita</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/citas" class="menu-item">
              <i class="fas fa-calendar-check"></i>
              <span>Gestión de Citas</span>
            </router-link>
          </li>
          <li class="menu-group">
            <div class="menu-item-group" @click="toggleSubmenu('doctores')">
              <i class="fas fa-user-md"></i>
              <span>Doctores</span>
              <i class="fas fa-chevron-down submenu-arrow" :class="{ 'rotated': openSubmenus.doctores }"></i>
            </div>
            <ul class="submenu" v-show="openSubmenus.doctores">
              <li>
                <router-link to="/admin/doctores" class="submenu-item">
                  <i class="fas fa-users"></i>
                  <span>Lista de Doctores</span>
                </router-link>
              </li>
              <li>
                <router-link to="/admin/horarios" class="submenu-item">
                  <i class="fas fa-calendar"></i>
                  <span>Horarios</span>
                </router-link>
              </li>
              <li>
                <router-link to="/admin/especialidades" class="submenu-item">
                  <i class="fas fa-stethoscope"></i>
                  <span>Especialidades</span>
                </router-link>
              </li>
              <li>
                <router-link to="/admin/tipos-bloque" class="submenu-item">
                  <i class="fas fa-clock"></i>
                  <span>Tipos de Bloque</span>
                </router-link>
              </li>
            </ul>
          </li>
          <li>
            <router-link to="/admin/aseguradoras" class="menu-item">
              <i class="fas fa-building"></i>
              <span>Aseguradoras</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/pacientes" class="menu-item">
              <i class="fas fa-users"></i>
              <span>Pacientes</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/usuarios" class="menu-item">
              <i class="fas fa-user-cog"></i>
              <span>Usuarios</span>
            </router-link>
          </li>
        </ul>
      </div>
      
      <!-- Menú para doctores -->
      <div v-if="userRole === 'doctor'" class="sidebar-menu">
        <div class="menu-title">Panel del Doctor</div>
        <ul class="menu-items">
          <li>
            <router-link to="/doctor/dashboard" class="menu-item">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </router-link>
          </li>
          <li>
            <router-link to="/crear-cita" class="menu-item menu-item-highlight">
              <i class="fas fa-plus-circle"></i>
              <span>Nueva Cita</span>
            </router-link>
          </li>
          <li>
            <router-link to="/doctor/horarios" class="menu-item">
              <i class="fas fa-calendar"></i>
              <span>Mis Horarios</span>
            </router-link>
          </li>
          <li>
            <router-link to="/doctor/aceptar-citas" class="menu-item">
              <i class="fas fa-clipboard-check"></i>
              <span>Tomar Citas</span>
            </router-link>
          </li>
          <li>
            <router-link to="/doctor/disponibles" class="menu-item">
              <i class="fas fa-calendar-plus"></i>
              <span>Citas Disponibles</span>
            </router-link>
          </li>
          <li>
            <router-link to="/doctor/historial" class="menu-item">
              <i class="fas fa-history"></i>
              <span>Historial</span>
            </router-link>
          </li>
        </ul>
      </div>
      
      <!-- Menú para aseguradoras -->
      <div v-if="userRole === 'aseguradora'" class="sidebar-menu">
        <div class="menu-title">Panel de Aseguradora</div>
        <ul class="menu-items">
          <li>
            <router-link to="/aseguradora/dashboard" class="menu-item">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </router-link>
          </li>
          <li>
            <router-link to="/crear-cita" class="menu-item menu-item-highlight">
              <i class="fas fa-plus-circle"></i>
              <span>Nueva Cita</span>
            </router-link>
          </li>
          <li>
            <router-link to="/aseguradora/solicitar-cita" class="menu-item">
              <i class="fas fa-plus-circle"></i>
              <span>Solicitar Cita</span>
            </router-link>
          </li>
          <li>
            <router-link to="/aseguradora/titulares" class="menu-item">
              <i class="fas fa-user-friends"></i>
              <span>Titulares</span>
            </router-link>
          </li>
          <li>
            <router-link to="/aseguradora/pacientes" class="menu-item">
              <i class="fas fa-users"></i>
              <span>Pacientes</span>
            </router-link>
          </li>
          <li>
            <router-link to="/aseguradora/historial" class="menu-item">
              <i class="fas fa-history"></i>
              <span>Historial de Citas</span>
            </router-link>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../../store/auth.js';

export default {
  name: 'Sidebar',
  data() {
    return {
      isCollapsed: false,
      openSubmenus: {
        doctores: false
      }
    }
  },
  computed: {
    userRole() {
      return useAuthStore().userRole;
    }
  },
  methods: {
    toggleCollapse() {
      this.isCollapsed = !this.isCollapsed;
      document.body.classList.toggle('sidebar-collapsed', this.isCollapsed);
      
      // Cerrar submenús cuando se colapsa
      if (this.isCollapsed) {
        this.openSubmenus = { doctores: false };
      }
    },
    
    toggleSubmenu(submenuName) {
      if (this.isCollapsed) return;
      
      this.openSubmenus[submenuName] = !this.openSubmenus[submenuName];
    }
  }
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: var(--navbar-height);
  left: 0;
  height: calc(100vh - var(--navbar-height));
  width: var(--sidebar-width);
  background-color: #433B37;
  color: #ecf0f1;
  transition: all 0.3s ease;
  z-index: 90;
  box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
  overflow-y: auto;
}

.sidebar.collapsed {
  width: 60px;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #2E2825;
}

.clinic-name {
  font-weight: bold;
  font-size: 16px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.collapse-btn {
  background: none;
  border: none;
  color: #ecf0f1;
  cursor: pointer;
  font-size: 16px;
}

.sidebar-content {
  padding: 15px 0;
}

.sidebar-menu {
  margin-bottom: 20px;
}

.menu-title {
  padding: 0 15px 10px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #7f8c8d;
}

.menu-items {
  list-style-type: none;
  padding: 0;
  margin: 0;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  color: #ecf0f1;
  text-decoration: none;
  transition: background-color 0.2s;
}

.menu-item:hover {
  background-color: #2E2825;
}

.menu-item i {
  margin-right: 10px;
  width: 20px;
  text-align: center;
}

.router-link-active {
  background-color: #897A72;
}

.router-link-active:hover {
  background-color: #2E2825;
}

/* Estilo especial para Nueva Cita */
.menu-item-highlight {
  background: #28a745;
  margin: 5px 10px;
  border-radius: 8px;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.menu-item-highlight:hover {
  background: #25973f;
  transform: translateY(-1px);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
}

.menu-item-highlight.router-link-active {
  background: #28a745;
}

/* Estilos para submenús */
.menu-group {
  position: relative;
}

.menu-item-group {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  color: #ecf0f1;
  cursor: pointer;
  transition: background-color 0.2s;
}

.menu-item-group:hover {
  background-color: #2E2825;
}

.menu-item-group i:first-child {
  margin-right: 10px;
  width: 20px;
  text-align: center;
}

.submenu-arrow {
  transition: transform 0.3s ease;
  font-size: 12px;
  padding: 0.5em;
}

.submenu-arrow.rotated {
  transform: rotate(180deg);
}

.submenu {
  list-style-type: none;
  padding: 0;
  margin: 0;
  background-color: #2E2825;
  border-top: 1px solid #1a1a1a;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

.menu-group .submenu {
  max-height: 200px;
}

.submenu-item {
  display: flex;
  align-items: center;
  padding: 8px 15px 8px 45px;
  color: #bdc3c7;
  text-decoration: none;
  transition: all 0.2s;
  font-size: 14px;
}

.submenu-item:hover {
  background-color: #1a1a1a;
  color: #ecf0f1;
}

.submenu-item i {
  margin-right: 8px;
  width: 16px;
  text-align: center;
  font-size: 12px;
}

.submenu-item.router-link-active {
  background-color: #897A72;
  color: #ecf0f1;
}

/* Estados colapsado */
.sidebar.collapsed .clinic-name,
.sidebar.collapsed .menu-title,
.sidebar.collapsed .menu-item span,
.sidebar.collapsed .submenu-arrow {
  display: none;
}

.sidebar.collapsed .menu-item {
  justify-content: center;
  padding: 15px 0;
  margin: 5px 0;
  border-radius: 0;
}

.sidebar.collapsed .menu-item-highlight {
  margin: 5px 8px;
  border-radius: 8px;
}

.sidebar.collapsed .menu-item i {
  margin-right: 0;
  font-size: 18px;
}

.sidebar.collapsed .menu-item-group {
  justify-content: center;
  padding: 15px 0;
}

.sidebar.collapsed .menu-item-group i:first-child {
  margin-right: 0;
  font-size: 18px;
}

.sidebar.collapsed .submenu {
  display: none;
}

/* Animaciones */
@keyframes slideDown {
  from {
    max-height: 0;
    opacity: 0;
  }
  to {
    max-height: 200px;
    opacity: 1;
  }
}

.submenu {
  animation: slideDown 0.3s ease;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.active {
    transform: translateX(0);
  }
}
</style>