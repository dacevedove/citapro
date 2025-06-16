<template>
  <nav class="navbar">
    <div class="navbar-container">
      <div class="navbar-brand">
        <router-link to="/" class="brand-link">
          <img src="/assets/img/logo_lgm.svg" alt="LGM Logo" class="brand-logo">
        </router-link>
      </div>
      
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name">{{ userName }}</span>
          <span class="user-role">{{ formatRole }}</span>
        </div>
        
        <div class="dropdown">
          <button class="dropdown-toggle" @click.stop="toggleDropdown">
            <Avatar
              :photo-url="userPhoto"
              :name="userName"
              :initials="userInitials"
              size="sm"
            />
            <i class="dropdown-chevron fas fa-chevron-down" :class="{ 'rotated': showDropdown }"></i>
          </button>
          
          <div v-if="showDropdown" class="dropdown-menu">
            <router-link to="/perfil" @click="closeDropdown" class="dropdown-item">
              <i class="fas fa-user-edit"></i> 
              <span>Mi Perfil</span>
            </router-link>
            
            <router-link to="/configuracion" @click="closeDropdown" class="dropdown-item">
              <i class="fas fa-cog"></i>
              <span>Configuración</span>
            </router-link>
            
            <div class="dropdown-divider"></div>
            
            <a href="#" @click.prevent="logout" class="dropdown-item logout-item">
              <i class="fas fa-sign-out-alt"></i>
              <span>Cerrar sesión</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { useAuthStore } from '../../store/auth';
import Avatar from './Avatar.vue';

export default {
  name: 'Navbar',
  components: {
    Avatar
  },
  data() {
    return {
      showDropdown: false
    }
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    userName() {
      const user = this.authStore.user;
      return user ? `${user.nombre} ${user.apellido}` : 'Usuario';
    },
    userRole() {
      return this.authStore.userRole;
    },
    userPhoto() {
      const photo = this.authStore.user?.foto_perfil;
      console.log('Navbar - User photo computed:', photo);
      return photo;
    },
    userEmail() {
      return this.authStore.user?.email || '';
    },
    userInitials() {
      const user = this.authStore.user;
      if (!user) return 'U';
      const nombre = user.nombre || '';
      const apellido = user.apellido || '';
      return (nombre.charAt(0) + apellido.charAt(0)).toUpperCase();
    },
    formatRole() {
      switch (this.userRole) {
        case 'admin': return 'Dirección Médica';
        case 'doctor': return 'Doctor';
        case 'aseguradora': return 'Aseguradora';
        case 'paciente': return 'Paciente';
        case 'coordinador': return 'Coordinador';
        case 'vertice': return 'Vértice';
        default: return 'Usuario';
      }
    }
  },
  watch: {
    // Observar cambios en la foto del usuario
    'authStore.user.foto_perfil': {
      handler(newPhoto, oldPhoto) {
        console.log('Navbar - Foto cambió:', { old: oldPhoto, new: newPhoto });
        this.$forceUpdate(); // Forzar re-render del componente
      },
      immediate: true
    }
  },
  methods: {
    toggleDropdown(event) {
      console.log('Toggle dropdown clicked');
      if (event) {
        event.stopPropagation();
      }
      this.showDropdown = !this.showDropdown;
      console.log('Dropdown state:', this.showDropdown);
    },
    closeDropdown() {
      this.showDropdown = false;
    },
    logout() {
      this.authStore.logout();
      this.showDropdown = false;
    },
    handleClickOutside(event) {
      if (!this.$el.contains(event.target)) {
        this.showDropdown = false;
      }
    }
  },
  mounted() {
    console.log('Navbar mounted');
    console.log('Initial user data:', this.authStore.user);
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  }
}
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  background-color: #2c3e50;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.navbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 24px;
  height: 100%;
  width: 100%;
}

/* Brand */
.navbar-brand {
  display: flex;
  align-items: center;
  flex: 0 0 auto;
}

.brand-link {
  text-decoration: none;
  color: white;
  display: flex;
  align-items: center;
  transition: opacity 0.2s ease;
}

.brand-link:hover {
  opacity: 0.8;
}

.brand-logo {
  height: 36px;
  width: auto;
}

/* User Section */
.navbar-user {
  display: flex;
  align-items: center;
  flex: 0 0 auto;
  margin-left: auto;
}

.user-info {
  display: flex;
  flex-direction: column;
  margin-right: 16px;
  text-align: right;
}

.user-name {
  font-weight: 600;
  color: white;
  font-size: 14px;
  line-height: 1.2;
}

.user-role {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 2px;
  font-weight: 500;
}

/* Dropdown */
.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.dropdown-toggle:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.dropdown-toggle:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
}

.dropdown-chevron {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
}

.dropdown-chevron.rotated {
  transform: rotate(180deg);
}

.dropdown-toggle:hover .dropdown-chevron {
  color: white;
}

/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 200px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(0, 0, 0, 0.1);
  z-index: 99999;
  overflow: hidden;
  animation: dropdownFadeIn 0.2s ease-out;
  display: block;
}

@keyframes dropdownFadeIn {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Dropdown Items */
.dropdown-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  text-decoration: none;
  color: #495057;
  transition: all 0.2s ease;
  font-size: 14px;
  font-weight: 500;
  gap: 12px;
}

.dropdown-item i {
  color: #6c757d;
  width: 16px;
  text-align: center;
  font-size: 14px;
  transition: color 0.2s ease;
}

.dropdown-item span {
  flex: 1;
}

.dropdown-item:hover {
  background-color: rgba(0, 123, 255, 0.05);
  color: #007bff;
}

.dropdown-item:hover i {
  color: #007bff;
}

.logout-item:hover {
  background-color: rgba(220, 53, 69, 0.05);
  color: #dc3545;
}

.logout-item:hover i {
  color: #dc3545;
}

.dropdown-divider {
  height: 1px;
  background-color: rgba(0, 0, 0, 0.06);
  margin: 8px 0;
}

/* Responsive */
@media (max-width: 768px) {
  .navbar-container {
    padding: 0 16px;
  }
  
  .user-info {
    display: none;
  }
  
  .dropdown-menu {
    min-width: 180px;
    right: -8px;
  }
  
  .dropdown-item {
    padding: 10px 14px;
  }
}

@media (max-width: 480px) {
  .dropdown-menu {
    min-width: 160px;
    right: -16px;
  }
}
</style>