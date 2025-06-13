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
              <i v-else class="fas fa-user-circle"></i>
            </div>
          </button>
          
          <div v-show="showDropdown" class="dropdown-menu">
            <router-link to="/perfil" @click="closeDropdown" class="dropdown-item">
              <i class="fas fa-user-edit"></i> Mi Perfil
            </router-link>
            <div class="dropdown-divider"></div>
            <a href="#" @click.prevent="logout" class="dropdown-item">
              <i class="fas fa-sign-out-alt"></i> Cerrar sesión
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
      sidebarVisible: true,
      photoError: false
    }
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    userName() {
      return this.authStore.user ? `${this.authStore.user.nombre} ${this.authStore.user.apellido}` : 'Usuario';
    },
    userRole() {
      return this.authStore.userRole;
    },
    userPhoto() {
      return this.authStore.user?.foto_perfil;
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
      if (event) {
        event.stopPropagation();
      }
      this.showDropdown = !this.showDropdown;
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
    }
  },
  mounted() {
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.showDropdown = false;
      }
    });
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeDropdown);
  }
}
</script>

<style scoped>
.navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--navbar-height);
  background-color: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.navbar-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 20px;
  height: 100%;
  width: 100%;
}

.navbar-brand {
  display: flex;
  align-items: center;
  flex: 0 0 auto;
}

.brand-link {
  text-decoration: none;
  color: var(--primary-color);
  display: flex;
  align-items: center;
}

.brand-logo {
  height: 36px;
  width: auto;
  margin-right: 10px;
}

.navbar-user {
  display: flex;
  align-items: center;
  flex: 0 0 auto;
  margin-left: auto;
}

.user-info {
  display: flex;
  flex-direction: column;
  margin-right: 15px;
  text-align: right;
}

.user-name {
  font-weight: 500;
  color: var(--dark-color);
  font-size: 14px;
}

.user-role {
  font-size: 12px;
  color: var(--secondary-color);
}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-toggle {
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.dropdown-toggle:hover {
  background-color: #f8f9fa;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar i {
  font-size: 24px;
  color: white;
}

.dropdown-menu {
  position: absolute;
  top: 45px;
  right: 0;
  min-width: 200px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  margin-top: 5px;
  z-index: 9999;
  display: block;
  visibility: visible !important;
  opacity: 1 !important;
  border: 1px solid #e9ecef;
  overflow: hidden;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  text-decoration: none;
  color: #495057;
  transition: all 0.2s ease;
  font-size: 14px;
  font-weight: 500;
}

.dropdown-item i {
  margin-right: 12px;
  color: #6c757d;
  width: 16px;
  text-align: center;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  color: #495057;
}

.dropdown-divider {
  height: 1px;
  background-color: #e9ecef;
  margin: 4px 0;
}

/* Responsive */
@media (max-width: 768px) {
  .user-info {
    display: none;
  }
  
  .navbar-container {
    padding: 0 15px;
  }
}
</style>