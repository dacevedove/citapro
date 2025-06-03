<template>
  <div class="sidebar" :class="{ collapsed: isCollapsed }">
    <div class="sidebar-header">
      <span class="clinic-name">La Guerra Mendez</span>
      <button class="collapse-btn" @click="toggleCollapse">
        <i class="fas" :class="isCollapsed ? 'fa-angle-right' : 'fa-angle-left'"></i>
      </button>
    </div>
    
    <div class="sidebar-content">

      <!-- Menú para vertice -->
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
            <router-link to="/admin/citas" class="menu-item">
              <i class="fas fa-calendar-check"></i>
              <span>Gestión de Citas</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/doctores" class="menu-item">
              <i class="fas fa-user-md"></i>
              <span>Doctores</span>
            </router-link>
          </li>
          <li>
            <router-link to="/admin/especialidades" class="menu-item">
              <i class="fas fa-clipboard-list"></i>
              <span>Especialidades</span>
            </router-link>
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
      isCollapsed: false
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

.sidebar.collapsed .clinic-name,
.sidebar.collapsed .menu-title,
.sidebar.collapsed .menu-item span {
  display: none;
}

.sidebar.collapsed .menu-item {
  justify-content: center;
  padding: 15px 0;
}

.sidebar.collapsed .menu-item i {
  margin-right: 0;
  font-size: 18px;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.active {
    transform: translateX(0);
  }
}
</style>