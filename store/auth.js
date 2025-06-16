import { defineStore } from 'pinia';
import axios from 'axios';
import router from '../router';

// Configurar los encabezados por defecto de axios
const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null,
    initialized: false
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
    userPhoto: (state) => {
      const photo = state.user?.foto_perfil;
      console.log('Store getter - userPhoto:', photo);
      return photo;
    },
    userEmail: (state) => state.user?.email || ''
  },
  
  actions: {
    async initialize() {
      if (this.initialized) return;
      
      console.log('Auth store - Initializing...');
      
      if (this.token) {
        console.log('Auth store - Token found, validating...');
        const isValid = await this.validateToken();
        if (!isValid) {
          console.log('Auth store - Token invalid, logging out');
          this.logout();
        } else {
          console.log('Auth store - Token valid, user loaded');
          console.log('Auth store - User photo after validation:', this.user?.foto_perfil);
        }
      }
      
      this.initialized = true;
    },
    
    async login(credentials) {
      this.loading = true;
      this.error = null;
      
      try {
        console.log('Auth store - Attempting login...');
        const response = await axios.post('/api/auth/login.php', credentials);
        
        if (response.data && response.data.token) {
          // Guardar token en localStorage y state
          localStorage.setItem('token', response.data.token);
          this.token = response.data.token;
          this.user = response.data.user;
          
          // Configurar axios con el nuevo token
          axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
          
          console.log('Login successful, user data:', this.user);
          console.log('Login successful, user photo:', this.user?.foto_perfil);
          
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
      if (!this.token) {
        console.log('Auth store - No token to validate');
        return false;
      }
      
      try {
        console.log('Auth store - Validating token...');
        const response = await axios.post('/api/auth/validate.php', null, {
          headers: { 'Authorization': `Bearer ${this.token}` }
        });
        
        if (response.data && response.data.valid) {
          this.user = response.data.user;
          console.log('Token validated successfully');
          console.log('User data from validation:', this.user);
          console.log('User photo from validation:', this.user?.foto_perfil);
          
          // Debug: verificar que la foto se está cargando correctamente
          if (this.user?.foto_perfil) {
            console.log('Auth store - Photo URL detected:', this.user.foto_perfil);
            // Verificar si la URL es válida
            this.verifyPhotoUrl(this.user.foto_perfil);
          } else {
            console.log('Auth store - No photo URL in user data');
          }
          
          return true;
        } else {
          console.log('Token validation failed:', response.data);
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
        console.log('Auth store - No token for refresh');
        return false;
      }

      try {
        console.log('Auth store - Refreshing user data...');
        const response = await axios.get('/api/auth/update_profile.php', {
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        });
        
        if (response.data.success) {
          // Actualizar datos del usuario manteniendo la reactividad
          const oldPhoto = this.user?.foto_perfil;
          this.user = { ...this.user, ...response.data.user };
          
          console.log('User data refreshed successfully');
          console.log('Updated user photo:', this.user?.foto_perfil);
          
          // Si la foto cambió, verificarla
          if (this.user?.foto_perfil && this.user.foto_perfil !== oldPhoto) {
            this.verifyPhotoUrl(this.user.foto_perfil);
          }
          
          return true;
        }
        return false;
      } catch (error) {
        console.error('Error refrescando datos del usuario:', error);
        return false;
      }
    },

    async updateUserPhoto(photoUrl) {
      console.log('Store - updateUserPhoto called with:', photoUrl);
      console.log('Store - Current user before update:', this.user);
      
      if (this.user) {
        // Crear un nuevo objeto completamente para forzar reactividad en Vue
        this.user = {
          ...this.user,
          foto_perfil: photoUrl
        };
        
        console.log('Store - User after photo update:', this.user);
        console.log('Store - Photo from getter after update:', this.userPhoto);
        
        // Verificar la nueva URL
        if (photoUrl) {
          this.verifyPhotoUrl(photoUrl);
        }
      }
    },

    async updateUserProfile(userData) {
      console.log('Store - updateUserProfile called with:', userData);
      
      if (this.user) {
        // Actualizar datos manteniendo la reactividad
        this.user = { ...this.user, ...userData };
        console.log('Store - Profile updated:', this.user);
      }
    },
    
    // Nueva función para verificar URLs de fotos
    verifyPhotoUrl(photoUrl) {
      if (!photoUrl) return;
      
      // Construir URL completa si es necesaria
      let fullUrl = photoUrl;
      if (!photoUrl.startsWith('http')) {
        fullUrl = photoUrl.startsWith('/') 
          ? window.location.origin + photoUrl 
          : window.location.origin + '/' + photoUrl;
      }
      
      console.log('Auth store - Verifying photo URL:', fullUrl);
      
      // Verificar que la imagen existe
      const img = new Image();
      img.onload = () => {
        console.log('Auth store - Photo URL verified successfully:', fullUrl);
      };
      img.onerror = () => {
        console.error('Auth store - Photo URL failed to load:', fullUrl);
      };
      img.src = fullUrl;
    },
    
    logout() {
      console.log('Auth store - Logging out...');
      localStorage.removeItem('token');
      this.token = null;
      this.user = null;
      this.initialized = false;
      
      // Eliminar el token de los encabezados de axios
      delete axios.defaults.headers.common['Authorization'];
      
      // Solo redirigir si no estamos ya en login
      if (router.currentRoute.value.path !== '/login') {
        router.push('/login');
      }
    },

    updateUserData(userData) {
      console.log('Auth store - Updating user data:', userData);
      this.user = userData;
      
      // Verificar foto si está presente
      if (userData?.foto_perfil) {
        this.verifyPhotoUrl(userData.foto_perfil);
      }
    },
    
    redirectBasedOnRole() {
      if (!this.user || !this.user.role) {
        console.log('Auth store - No user or role for redirect');
        return;
      }
      
      console.log("Redirigiendo según rol:", this.user.role);
      
      const dashboardRoutes = {
        'admin': '/admin/dashboard',
        'doctor': '/doctor/dashboard',
        'aseguradora': '/aseguradora/dashboard',
        'paciente': '/paciente/dashboard',
        'coordinador': '/admin/dashboard',
        'vertice': '/admin/dashboard'
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