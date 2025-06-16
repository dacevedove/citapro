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
            <div class="user-avatar">
              <img 
                v-if="userPhoto && !photoError" 
                :src="getPhotoUrl(userPhoto)" 
                :alt="'Foto de ' + userName"
                class="avatar-image"
                @error="handlePhotoError"
              >
              <div v-else class="avatar-placeholder">
                <span v-if="userInitials" class="avatar-initials">{{ userInitials }}</span>
                <i v-else class="fas fa-user-circle"></i>
              </div>
            </div>
            <div class="dropdown-indicator">
              <i :class="['fas', showDropdown ? 'fa-chevron-up' : 'fa-chevron-down']"></i>
            </div>
          </button>
          
          <div v-if="showDropdown" class="dropdown-menu">
            <div class="dropdown-header">
              <div class="header-avatar">
                <img 
                  v-if="userPhoto && !photoError" 
                  :src="getPhotoUrl(userPhoto)" 
                  :alt="'Foto de ' + userName"
                  class="header-avatar-image"
                  @error="handlePhotoError"
                >
                <div v-else class="header-avatar-placeholder">
                  <span v-if="userInitials" class="header-avatar-initials">{{ userInitials }}</span>
                  <i v-else class="fas fa-user"></i>
                </div>
              </div>
              <div class="dropdown-user-info">
                <span class="dropdown-user-name">{{ userName }}</span>
                <span class="dropdown-user-role">{{ formatRole }}</span>
                <span class="dropdown-user-email">{{ userEmail }}</span>
              </div>
            </div>
            
            <div class="dropdown-divider"></div>
            
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

export default {
  name: 'Navbar',
  data() {
    return {
      showDropdown: false,
      photoError: false
    }
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    userName() {
      // Forzar reactividad accediendo directamente al store
      const user = this.authStore.user;
      return user ? `${user.nombre} ${user.apellido}` : 'Usuario';
    },
    userRole() {
      return this.authStore.userRole;
    },
    userPhoto() {
      // Forzar reactividad y debug
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
    getPhotoUrl(photoPath) {
      if (!photoPath) return '';
      if (photoPath.startsWith('http')) return photoPath;
      return window.location.origin + photoPath;
    },
    handlePhotoError() {
      this.photoError = true;
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

/* User Avatar */
.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  flex-shrink: 0;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.avatar-initials {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.avatar-placeholder i {
  font-size: 18px;
}

.dropdown-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.dropdown-indicator i {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.7);
}

.dropdown-toggle:hover .dropdown-indicator i {
  color: white;
}

/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  min-width: 280px;
  background-color: #ffffff;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
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

/* Dropdown Header */
.dropdown-header {
  display: flex;
  align-items: center;
  padding: 20px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  gap: 16px;
}

.header-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.header-avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.header-avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.header-avatar-initials {
  font-size: 16px;
  font-weight: 600;
  text-transform: uppercase;
}

.header-avatar-placeholder i {
  font-size: 20px;
}

.dropdown-user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dropdown-user-name {
  font-weight: 600;
  color: #2c3e50;
  font-size: 16px;
  line-height: 1.2;
}

.dropdown-user-role {
  font-size: 13px;
  color: #007bff;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dropdown-user-email {
  font-size: 12px;
  color: #6c757d;
  font-weight: 400;
}

/* Dropdown Items */
.dropdown-item {
  display: flex;
  align-items: center;
  padding: 14px 20px;
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
    min-width: 260px;
    right: -8px;
  }
  
  .dropdown-header {
    padding: 16px;
  }
  
  .dropdown-item {
    padding: 12px 16px;
  }
}

@media (max-width: 480px) {
  .dropdown-menu {
    min-width: 240px;
    right: -16px;
  }
}
</style>