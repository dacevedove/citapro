<template>
  <nav class="debug-navbar">
    <div class="debug-container">
      <h3>Debug Navbar</h3>
      <div class="debug-info">
        <p>Usuario: {{ userName }}</p>
        <p>Rol: {{ userRole }}</p>
        <p>Foto: {{ userPhoto }}</p>
        <p>Dropdown: {{ showDropdown ? 'Abierto' : 'Cerrado' }}</p>
      </div>
      
      <div class="simple-dropdown">
        <button @click="toggleDropdown" class="simple-btn">
          Abrir Dropdown
        </button>
        <div v-if="showDropdown" class="simple-menu">
          <div>Opción 1</div>
          <div>Opción 2</div>
          <div>Opción 3</div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { useAuthStore } from '../../store/auth';

export default {
  name: 'DebugNavbar',
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
      return this.authStore.user ? `${this.authStore.user.nombre} ${this.authStore.user.apellido}` : 'No usuario';
    },
    userRole() {
      return this.authStore.userRole || 'No rol';
    },
    userPhoto() {
      return this.authStore.user?.foto_perfil || 'No foto';
    }
  },
  methods: {
    toggleDropdown() {
      console.log('Dropdown toggle clicked');
      this.showDropdown = !this.showDropdown;
      console.log('New state:', this.showDropdown);
    }
  }
}
</script>

<style scoped>
.debug-navbar {
  background-color: #f8f9fa;
  padding: 20px;
  border: 2px solid #007bff;
  margin: 20px;
  border-radius: 8px;
}

.debug-container {
  max-width: 800px;
  margin: 0 auto;
}

.debug-info {
  background-color: white;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.simple-dropdown {
  position: relative;
  display: inline-block;
}

.simple-btn {
  background-color: #007bff;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
}

.simple-menu {
  position: absolute;
  top: 100%;
  left: 0;
  background-color: white;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  min-width: 150px;
  z-index: 1000;
}

.simple-menu div {
  padding: 10px 15px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
}

.simple-menu div:hover {
  background-color: #f8f9fa;
}

.simple-menu div:last-child {
  border-bottom: none;
}
</style>