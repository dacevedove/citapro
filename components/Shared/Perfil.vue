<template>
    <div class="perfil-container">
      <h1>Gestión de Cuenta</h1>
      
      <div class="profile-form">
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input type="text" id="nombre" v-model="userData.nombre" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="apellido">Apellido</label>
          <input type="text" id="apellido" v-model="userData.apellido" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input type="text" id="telefono" v-model="userData.telefono" class="form-control">
        </div>
        
        <h2>Cambiar Contraseña</h2>
        
        <div class="form-group">
          <label for="currentPassword">Contraseña Actual</label>
          <input type="password" id="currentPassword" v-model="passwordData.currentPassword" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="newPassword">Nueva Contraseña</label>
          <input type="password" id="newPassword" v-model="passwordData.newPassword" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="confirmPassword">Confirmar Contraseña</label>
          <input type="password" id="confirmPassword" v-model="passwordData.confirmPassword" class="form-control">
        </div>
        
        <div class="buttons">
          <button @click="updateUserData" class="btn btn-primary">Actualizar Datos</button>
          <button @click="updatePassword" class="btn btn-secondary">Cambiar Contraseña</button>
        </div>
        
        <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-danger']">
          {{ message }}
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import { useAuthStore } from '../../store/auth';
  import axios from 'axios';
  
  export default {
    name: 'Perfil',
    data() {
      return {
        userData: {
          nombre: '',
          apellido: '',
          telefono: ''
        },
        passwordData: {
          currentPassword: '',
          newPassword: '',
          confirmPassword: ''
        },
        message: '',
        messageType: 'success'
      }
    },
    computed: {
      authStore() {
        return useAuthStore();
      }
    },
    methods: {
      loadUserData() {
        // Cargar datos del usuario desde el store
        if (this.authStore.user) {
          this.userData.nombre = this.authStore.user.nombre;
          this.userData.apellido = this.authStore.user.apellido;
          this.userData.telefono = this.authStore.user.telefono || '';
        }
      },
      async updateUserData() {
        try {
          const response = await axios.post('/lgm/api/auth/update_profile.php', {
            id: this.authStore.user.id,
            nombre: this.userData.nombre,
            apellido: this.userData.apellido,
            telefono: this.userData.telefono
          }, {
            headers: {
              'Authorization': `Bearer ${this.authStore.token}`
            }
          });
          
          if (response.data.success) {
            // Actualizar datos en el store
            this.authStore.updateUserData({
              ...this.authStore.user,
              nombre: this.userData.nombre,
              apellido: this.userData.apellido,
              telefono: this.userData.telefono
            });
            
            this.message = 'Datos actualizados correctamente';
            this.messageType = 'success';
          } else {
            this.message = response.data.message || 'Error al actualizar datos';
            this.messageType = 'error';
          }
        } catch (error) {
          console.error('Error al actualizar perfil:', error);
          this.message = 'Error al actualizar datos';
          this.messageType = 'error';
        }
      },
      async updatePassword() {
        if (this.passwordData.newPassword !== this.passwordData.confirmPassword) {
          this.message = 'Las contraseñas no coinciden';
          this.messageType = 'error';
          return;
        }
        
        try {
          const response = await axios.post('/lgm/api/auth/change_password.php', {
            id: this.authStore.user.id,
            current_password: this.passwordData.currentPassword,
            new_password: this.passwordData.newPassword
          }, {
            headers: {
              'Authorization': `Bearer ${this.authStore.token}`
            }
          });
          
          if (response.data.success) {
            this.message = 'Contraseña actualizada correctamente';
            this.messageType = 'success';
            this.passwordData = {
              currentPassword: '',
              newPassword: '',
              confirmPassword: ''
            };
          } else {
            this.message = response.data.message || 'Error al actualizar contraseña';
            this.messageType = 'error';
          }
        } catch (error) {
          console.error('Error al cambiar contraseña:', error);
          this.message = 'Error al actualizar contraseña';
          this.messageType = 'error';
        }
      }
    },
    mounted() {
      this.loadUserData();
    }
  }
  </script>
  
  <style scoped>
  .perfil-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }
  
  h1 {
    color: var(--primary-color);
    margin-bottom: 20px;
  }
  
  h2 {
    color: var(--secondary-color);
    margin: 25px 0 15px;
    font-size: 1.2rem;
  }
  
  .profile-form {
    margin-top: 20px;
  }
  
  .form-group {
    margin-bottom: 15px;
  }
  
  label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--dark-color);
  }
  
  .form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
  }
  
  .buttons {
    display: flex;
    gap: 10px;
    margin: 20px 0;
  }
  
  .btn {
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
  }
  
  .btn-primary {
    background-color: var(--primary-color);
    color: white;
  }
  
  .btn-secondary {
    background-color: var(--secondary-color);
    color: white;
  }
  
  .alert {
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 20px;
  }
  
  .alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }
  
  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }
  </style>