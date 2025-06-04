<template>
  <nav class="navbar">
    <div class="navbar-container">
      <div class="navbar-brand">
        <router-link to="/" class="brand-link">
          <img src="https://localhost/assets/img/logo_lgm.svg" alt="LGM Logo" class="brand-logo">
        </router-link>
      </div>
      
      <div class="navbar-user">
        <div class="user-info">
          <span class="user-name">{{ userName }}</span>
          <span class="user-role">{{ formatRole }}</span>
        </div>
        
        <div class="dropdown">
          <button class="dropdown-toggle" @click.stop="toggleDropdown">
            <i class="fas fa-user-circle"></i>
          </button>
          
          <div v-show="showDropdown" class="dropdown-menu">
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
      showDropdown: false, // Inicialmente cerrado
      sidebarVisible: true
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
    formatRole() {
      switch (this.userRole) {
        case 'admin': return 'Dirección Médica';
        case 'doctor': return 'Doctor';
        case 'aseguradora': return 'Aseguradora';
        case 'paciente': return 'Paciente';
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
      console.log('Dropdown state:', this.showDropdown); // Para debugging
    },
    toggleSidebar() {
      this.sidebarVisible = !this.sidebarVisible;
      document.body.classList.toggle('sidebar-collapsed', !this.sidebarVisible);
    },
    logout() {
      this.authStore.logout();
      this.showDropdown = false;
    }
  },
  mounted() {
    // Cerrar el dropdown al hacer clic fuera de él
    document.addEventListener('click', (e) => {
      if (!this.$el.contains(e.target)) {
        this.showDropdown = false;
      }
    });
  },
  beforeUnmount() {
    // Limpiar event listener al desmontar el componente
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
  justify-content: space-between; /* Mantener space-between para separar brand y user */
  align-items: center;
  padding: 0 20px;
  height: 100%;
  width: 100%; /* Asegurar que ocupe todo el ancho */
}

.navbar-brand {
  display: flex;
  align-items: center;
  flex: 0 0 auto; /* No crecer ni encoger */
}

.menu-toggle {
  font-size: 20px;
  margin-right: 15px;
  cursor: pointer;
  color: var(--dark-color);
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

.brand-name {
  font-size: 18px;
  font-weight: bold;
}

.navbar-user {
  display: flex;
  align-items: center;
  flex: 0 0 auto; /* No crecer ni encoger */
  margin-left: auto; /* Empuja el elemento completamente a la derecha */
}

.user-info {
  display: flex;
  flex-direction: column;
  margin-right: 15px;
  text-align: right; /* Volver a alinear a la derecha */
}

.user-name {
  font-weight: 500;
  color: var(--dark-color);
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
  font-size: 24px;
  color: var(--primary-color);
  padding: 5px;
}

.dropdown-menu {
  position: absolute;
  top: 40px; /* Ajustado para que aparezca debajo del botón */
  right: 0;
  min-width: 180px;
  background-color: #ffffff;
  border-radius: 4px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  margin-top: 5px;
  z-index: 9999; /* Valor muy alto para asegurar que esté encima de todo */
  display: block; /* Asegurar que siempre se muestre cuando v-show es true */
  visibility: visible !important; /* Forzar visibilidad */
  opacity: 1 !important; /* Forzar opacidad */
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 12px 15px;
  text-decoration: none;
  color: var(--dark-color);
  transition: background-color 0.3s;
}

.dropdown-item i {
  margin-right: 10px;
  color: var(--secondary-color);
}

.dropdown-item:hover {
  background-color: #f5f5f5;
}
</style>