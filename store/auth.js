import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

// Configurar los encabezados por defecto de axios
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    userRole: (state) => state.user ? state.user.role : null,
    userName: (state) => {
      if (!state.user) return '';
      return `${state.user.nombre} ${state.user.apellido}`;
    },
    userInitials: (state) => {
      if (!state.user) return 'U';
      const nombre = state.user.nombre || '';
      const apellido = state.user.apellido || '';
      return (nombre.charAt(0) + apellido.charAt(0)).toUpperCase();
    },
    userPhoto: (state) => state.user?.foto_perfil || null,
    userEmail: (state) => state.user?.email || ''
  },
  
  actions: {
    async login(credentials) {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await axios.post('/api/auth/login.php', credentials);
        
        if (response.data && response.data.token) {
          // Guardar token en localStorage y state
          localStorage.setItem('token', response.data.token);
          this.token = response.data.token;
          this.user = response.data.user;
          
          // Configurar axios con el nuevo token
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
          
          // Redirigir según rol de usuario
          this.redirectBasedOnRole();
          
          return true;
        }
      } catch (error) {
        console.error('Error de login:', error);
        this.error = error.response?.data?.error || 'Error de autenticación';
        return false;
      } finally {
        this.loading = false;
      }
    },
    
    async validateToken() {
      if (!this.token) return false;
      
      try {
        const response = await axios.post('/api/auth/validate.php', null, {
          headers: { 'Authorization': `Bearer ${this.token}` }
        });
        
        if (response.data && response.data.valid) {
          this.user = response.data.user;
          return true;
        } else {
          this.logout();
          return false;
        }
      } catch (error) {
        console.error('Error al validar token:', error);
        this.logout();
        return false;
      }
    },

    async refreshUserData() {
      if (!this.token) {
        return false;
      }

      try {
        const response = await axios.get('/api/auth/update_profile.php', {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        });
        
        if (response.data.success) {
          // Actualizar solo los datos del usuario, mantener el token
          this.user = { ...this.user, ...response.data.user };
          return true;
        }
        return false;
      } catch (error) {
        console.error('Error refrescando datos del usuario:', error);
        return false;
      }
    },

    async updateUserPhoto(photoUrl) {
      if (this.user) {
        this.user.foto_perfil = photoUrl;
      }
    },

    async updateUserProfile(userData) {
      if (this.user) {
        this.user = { ...this.user, ...userData };
      }
    },
    
    logout() {
      localStorage.removeItem('token');
      this.token = null;
      this.user = null;
      // Eliminar el token de los encabezados de axios
      delete axios.defaults.headers.common['Authorization'];
      router.push('/login');
    },

    updateUserData(userData) {
      this.user = userData;
      localStorage.setItem('user', JSON.stringify(userData));
    },
    
    redirectBasedOnRole() {
      if (!this.user || !this.user.role) return;
      
      console.log("Redirigiendo según rol:", this.user.role);
      
      const dashboardRoutes = {
        'admin': '/admin/dashboard',
        'doctor': '/doctor/dashboard',
        'aseguradora': '/aseguradora/dashboard',
        'paciente': '/paciente/dashboard'
      };
      
      const route = dashboardRoutes[this.user.role] || '/login';
      
      // Usar navegación con reemplazo para evitar historiales extraños
      router.replace(route);
    },

    clearError() {
      this.error = null;
    }
  }
});