<template>
  <div id="app">
    <!-- Pantalla de carga mientras se valida la sesión -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
    
    <!-- Barra de navegación para usuarios autenticados -->
    <Navbar v-if="isAuthenticated" />
    
    <!-- Contenedor principal -->
    <div class="main-container" :class="{ 'with-sidebar': isAuthenticated && showSidebar }">
      <!-- Sidebar para usuarios autenticados excepto pacientes -->
      <Sidebar v-if="isAuthenticated && showSidebar" />
      
      <!-- Contenido principal -->
      <main class="content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../../store/auth.js';
import Navbar from '../../components/Shared/Navbar.vue';
import Sidebar from '../../components/Shared/Sidebar.vue';

export default {
  name: 'App',
  components: {
    Navbar,
    Sidebar
  },
  data() {
    return {
      loading: true
    }
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    isAuthenticated() {
      return this.authStore.isAuthenticated;
    },
    userRole() {
      return this.authStore.userRole;
    },
    showSidebar() {
      // Mostrar sidebar para todos los roles excepto pacientes
      return this.userRole && this.userRole !== 'paciente';
    }
  },
  async created() {
    // Validar token al cargar la aplicación
    if (this.authStore.token) {
      try {
        await this.authStore.validateToken();
      } catch (error) {
        console.error('Error validando token:', error);
      }
    }
    // Desactivar pantalla de carga
    this.loading = false;
  }
}
</script>

<style>
:root {
  --primary-color: #CC5215;
  --secondary-color: #6c757d;
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
  --info-color: #17a2b8;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --sidebar-width: 250px;
  --navbar-height: 60px;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  font-size: 16px;
  line-height: 1.5;
  color: #212529;
  background-color: #f5f7fa;
  overflow-x: hidden;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-container {
  display: flex;
  flex: 1;
  padding-top: var(--navbar-height);
}

.main-container.with-sidebar .content {
  margin-left: var(--sidebar-width);
}

.content {
  flex: 1;
  padding: 20px;
  transition: margin-left 0.3s ease;
}

/* Estilos para la pantalla de carga */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid #f3f3f3;
  border-top: 5px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .main-container.with-sidebar .content {
    margin-left: 0;
  }
}
</style>