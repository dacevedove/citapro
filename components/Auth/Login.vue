<template>
    <div class="login-container">
      <div class="login-form">
        <div class="login-header">
          <h2>Clínica La Guerra Mendez</h2>
          <p>Sistema de gestión de citas</p>
        </div>
        
        <form @submit.prevent="login">
          <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input 
              type="email" 
              id="email" 
              v-model="credentials.email" 
              required
              placeholder="Ingrese su correo electrónico"
              autocomplete="email"
            >
          </div>
          
          <div class="form-group">
            <label for="password">Contraseña</label>
            <input 
              type="password" 
              id="password" 
              v-model="credentials.password" 
              required
              placeholder="Ingrese su contraseña"
              autocomplete="current-password"
            >
          </div>
          
          <div v-if="error" class="alert alert-danger">
            {{ error }}
          </div>
          
          <button type="submit" class="btn-login" :disabled="loading">
            {{ loading ? 'Iniciando sesión...' : 'Iniciar sesión' }}
          </button>
        </form>
      </div>
    </div>
  </template>
  
  <script>
  import { useAuthStore } from '@store/auth.js';
  
  export default {
    name: 'Login',
    data() {
      return {
        credentials: {
          email: '',
          password: ''
        },
        loading: false,
        error: null
      };
    },
    methods: {
      async login() {
        this.loading = true;
        this.error = null;
        
        try {
          const authStore = useAuthStore();
          const success = await authStore.login(this.credentials);
          
          if (!success) {
            this.error = authStore.error || 'Error al iniciar sesión. Verifique sus credenciales.';
          }
        } catch (error) {
          console.error('Error durante login:', error);
          this.error = 'Ha ocurrido un error. Intente nuevamente.';
        } finally {
          this.loading = false;
        }
      }
    }
  };
  </script>
  
  <style scoped>
  .login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f5f7fa;
  }
  
  .login-form {
    width: 100%;
    max-width: 400px;
    padding: 30px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  }
  
  .login-header {
    text-align: center;
    margin-bottom: 30px;
  }
  
  .login-header h2 {
    color: var(--primary-color);
    margin-bottom: 5px;
  }
  
  .login-header p {
    color: var(--secondary-color);
    margin: 0;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--dark-color);
  }
  
  .form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 16px;
  }
  
  .btn-login {
    width: 100%;
    padding: 12px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .btn-login:hover {
    background-color: #0069d9;
  }
  
  .btn-login:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
  }
  
  .alert {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
  }
  
  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  </style>