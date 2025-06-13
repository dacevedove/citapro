<template>
  <div class="perfil-container">
    <h1>Gestión de Cuenta</h1>
    
    <div class="profile-form">
      <!-- Sección de foto de perfil -->
      <div class="photo-section">
        <h2>Foto de Perfil</h2>
        
        <div class="photo-container">
          <div class="current-photo">
            <img 
              v-if="userData.foto_perfil && !imageError" 
              :src="getPhotoUrl(userData.foto_perfil)" 
              :alt="'Foto de ' + userData.nombre"
              class="profile-photo"
              @error="handleImageError"
            >
            <div v-else class="no-photo">
              <i class="fas fa-user"></i>
              <span>Sin foto</span>
            </div>
          </div>
          
          <div class="photo-actions">
            <label for="photo-upload" class="btn btn-primary">
              <i class="fas fa-camera"></i>
              {{ userData.foto_perfil ? 'Cambiar foto' : 'Subir foto' }}
            </label>
            <input 
              type="file" 
              id="photo-upload" 
              accept="image/*" 
              @change="handlePhotoUpload"
              style="display: none;"
            >
            
            <button 
              v-if="userData.foto_perfil" 
              @click="deletePhoto" 
              class="btn btn-danger"
              :disabled="uploadingPhoto"
            >
              <i class="fas fa-trash"></i>
              Eliminar
            </button>
          </div>
          
          <div v-if="uploadingPhoto" class="upload-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{width: uploadProgress + '%'}"></div>
            </div>
            <span class="progress-text">{{ uploadProgress }}%</span>
          </div>
          
          <div class="photo-requirements">
            <small>
              <i class="fas fa-info-circle"></i>
              Formatos permitidos: JPEG, PNG, GIF, WebP. Tamaño máximo: 5MB
            </small>
          </div>
        </div>
      </div>

      <!-- Información personal -->
      <div class="personal-info-section">
        <h2>Información Personal</h2>
        
        <div class="form-row">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" v-model="userData.nombre" class="form-control">
          </div>
          
          <div class="form-group">
            <label for="apellido">Apellido</label>
            <input type="text" id="apellido" v-model="userData.apellido" class="form-control">
          </div>
        </div>
        
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input type="text" id="telefono" v-model="userData.telefono" class="form-control">
        </div>
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" :value="userData.email" disabled class="form-control disabled">
          <small class="form-text">Para cambiar el email, contacte al administrador</small>
        </div>
        
        <div class="form-group">
          <label for="cedula">Cédula</label>
          <input type="text" id="cedula" :value="userData.cedula" disabled class="form-control disabled">
        </div>
      </div>

      <!-- Cambiar contraseña -->
      <div class="password-section">
        <h2>Cambiar Contraseña</h2>
        
        <div class="form-group">
          <label for="currentPassword">Contraseña Actual</label>
          <div class="password-input">
            <input 
              :type="showCurrentPassword ? 'text' : 'password'" 
              id="currentPassword" 
              v-model="passwordData.currentPassword" 
              class="form-control"
            >
            <button 
              type="button" 
              @click="toggleCurrentPassword"
              class="password-toggle"
            >
              <i :class="showCurrentPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
        </div>
        
        <div class="form-group">
          <label for="newPassword">Nueva Contraseña</label>
          <div class="password-input">
            <input 
              :type="showNewPassword ? 'text' : 'password'" 
              id="newPassword" 
              v-model="passwordData.newPassword" 
              class="form-control"
              minlength="8"
            >
            <button 
              type="button" 
              @click="toggleNewPassword"
              class="password-toggle"
            >
              <i :class="showNewPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
          <div class="password-strength">
            <div class="strength-bar">
              <div 
                class="strength-fill" 
                :class="getPasswordStrengthClass(passwordData.newPassword)"
                :style="{width: getPasswordStrength(passwordData.newPassword) + '%'}"
              ></div>
            </div>
            <small>{{ getPasswordStrengthText(passwordData.newPassword) }}</small>
          </div>
        </div>
        
        <div class="form-group">
          <label for="confirmPassword">Confirmar Contraseña</label>
          <div class="password-input">
            <input 
              :type="showConfirmPassword ? 'text' : 'password'" 
              id="confirmPassword" 
              v-model="passwordData.confirmPassword" 
              class="form-control"
            >
            <button 
              type="button" 
              @click="toggleConfirmPassword"
              class="password-toggle"
            >
              <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
          <small 
            v-if="passwordData.confirmPassword && passwordData.newPassword !== passwordData.confirmPassword"
            class="text-danger"
          >
            Las contraseñas no coinciden
          </small>
        </div>
      </div>

      <!-- Botones de acción -->
      <div class="buttons">
        <button 
          @click="updateUserData" 
          class="btn btn-primary"
          :disabled="updatingProfile"
        >
          <i class="fas fa-save"></i>
          {{ updatingProfile ? 'Actualizando...' : 'Actualizar Datos' }}
        </button>
        
        <button 
          @click="updatePassword" 
          class="btn btn-secondary"
          :disabled="!canUpdatePassword || updatingPassword"
        >
          <i class="fas fa-key"></i>
          {{ updatingPassword ? 'Cambiando...' : 'Cambiar Contraseña' }}
        </button>
      </div>
      
      <!-- Mensajes -->
      <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-danger']">
        <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
        {{ message }}
      </div>
      
      <!-- Información adicional -->
      <div class="account-info">
        <h3>Información de la Cuenta</h3>
        <div class="info-grid">
          <div class="info-item">
            <label>Rol:</label>
            <span class="role-badge" :class="'role-' + userData.role">{{ formatRole(userData.role) }}</span>
          </div>
          <div class="info-item">
            <label>Miembro desde:</label>
            <span>{{ userData.creado_en }}</span>
          </div>
          <div class="info-item" v-if="userData.ultimo_acceso">
            <label>Último acceso:</label>
            <span>{{ userData.ultimo_acceso }}</span>
          </div>
          <div class="info-item">
            <label>Email verificado:</label>
            <span :class="userData.email_verificado ? 'text-success' : 'text-warning'">
              <i :class="userData.email_verificado ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
              {{ userData.email_verificado ? 'Verificado' : 'Pendiente' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Perfil',
  data() {
    return {
      userData: {
        id: null,
        nombre: '',
        apellido: '',
        email: '',
        cedula: '',
        telefono: '',
        foto_perfil: null,
        role: '',
        email_verificado: false,
        ultimo_acceso: '',
        creado_en: ''
      },
      passwordData: {
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
      },
      message: '',
      messageType: 'success',
      updatingProfile: false,
      updatingPassword: false,
      uploadingPhoto: false,
      uploadProgress: 0,
      showCurrentPassword: false,
      showNewPassword: false,
      showConfirmPassword: false,
      imageError: false
    }
  },
  computed: {
    canUpdatePassword() {
      return this.passwordData.currentPassword && 
             this.passwordData.newPassword && 
             this.passwordData.confirmPassword &&
             this.passwordData.newPassword === this.passwordData.confirmPassword &&
             this.passwordData.newPassword.length >= 8;
    }
  },
  methods: {
    async loadUserData() {
      try {
        const token = localStorage.getItem('token');
        if (!token) {
          this.showMessage('No hay sesión activa', 'error');
          return;
        }

        const response = await axios.get('/api/auth/update_profile.php', {
          headers: {
            'Authorization': 'Bearer ' + token
          }
        });
        
        if (response.data.success) {
          this.userData = Object.assign({}, response.data.user);
        } else {
          this.showMessage('Error al cargar datos del perfil', 'error');
        }
      } catch (error) {
        console.error('Error al cargar datos del usuario:', error);
        this.showMessage('Error al cargar datos del perfil', 'error');
      }
    },
    
    getPhotoUrl(photoPath) {
      if (!photoPath) return '';
      if (photoPath.startsWith('http')) return photoPath;
      return window.location.origin + photoPath;
    },
    
    handleImageError() {
      this.imageError = true;
    },
    
    async handlePhotoUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
      if (!allowedTypes.includes(file.type.toLowerCase())) {
        this.showMessage('Tipo de archivo no permitido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP)', 'error');
        return;
      }
      
      const maxSize = 5 * 1024 * 1024;
      if (file.size > maxSize) {
        this.showMessage('El archivo es demasiado grande. Tamaño máximo: 5MB', 'error');
        return;
      }
      
      this.uploadingPhoto = true;
      this.uploadProgress = 0;
      
      try {
        const formData = new FormData();
        formData.append('photo', file);
        
        const token = localStorage.getItem('token');
        const response = await axios.post('/api/auth/upload_profile_photo.php', formData, {
          headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'multipart/form-data'
          },
          onUploadProgress: (progressEvent) => {
            this.uploadProgress = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          }
        });
        
        if (response.data.success) {
          this.userData.foto_perfil = response.data.photo_url;
          this.imageError = false;
          this.showMessage('Foto de perfil actualizada correctamente', 'success');
        } else {
          this.showMessage(response.data.error || 'Error al subir la foto', 'error');
        }
      } catch (error) {
        console.error('Error al subir foto:', error);
        this.showMessage(error.response?.data?.error || 'Error al subir la foto', 'error');
      } finally {
        this.uploadingPhoto = false;
        this.uploadProgress = 0;
        event.target.value = '';
      }
    },
    
    async deletePhoto() {
      if (!confirm('¿Está seguro de que desea eliminar su foto de perfil?')) {
        return;
      }
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.delete('/api/auth/delete_profile_photo.php', {
          headers: {
            'Authorization': 'Bearer ' + token
          }
        });
        
        if (response.data.success) {
          this.userData.foto_perfil = null;
          this.showMessage('Foto de perfil eliminada correctamente', 'success');
        } else {
          this.showMessage(response.data.error || 'Error al eliminar la foto', 'error');
        }
      } catch (error) {
        console.error('Error al eliminar foto:', error);
        this.showMessage(error.response?.data?.error || 'Error al eliminar la foto', 'error');
      }
    },
    
    async updateUserData() {
      this.updatingProfile = true;
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.post('/api/auth/update_profile.php', {
          id: this.userData.id,
          nombre: this.userData.nombre,
          apellido: this.userData.apellido,
          telefono: this.userData.telefono
        }, {
          headers: {
            'Authorization': 'Bearer ' + token
          }
        });
        
        if (response.data.success) {
          this.showMessage('Datos actualizados correctamente', 'success');
        } else {
          this.showMessage(response.data.error || 'Error al actualizar datos', 'error');
        }
      } catch (error) {
        console.error('Error al actualizar perfil:', error);
        this.showMessage(error.response?.data?.error || 'Error al actualizar datos', 'error');
      } finally {
        this.updatingProfile = false;
      }
    },
    
    async updatePassword() {
      if (!this.canUpdatePassword) {
        this.showMessage('Verifique que todos los campos de contraseña estén completos y coincidan', 'error');
        return;
      }
      
      this.updatingPassword = true;
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.post('/api/auth/change_password.php', {
          id: this.userData.id,
          current_password: this.passwordData.currentPassword,
          new_password: this.passwordData.newPassword
        }, {
          headers: {
            'Authorization': 'Bearer ' + token
          }
        });
        
        if (response.data.success) {
          this.showMessage('Contraseña actualizada correctamente', 'success');
          this.passwordData = {
            currentPassword: '',
            newPassword: '',
            confirmPassword: ''
          };
        } else {
          this.showMessage(response.data.error || 'Error al actualizar contraseña', 'error');
        }
      } catch (error) {
        console.error('Error al cambiar contraseña:', error);
        this.showMessage(error.response?.data?.error || 'Error al actualizar contraseña', 'error');
      } finally {
        this.updatingPassword = false;
      }
    },
    
    getPasswordStrength(password) {
      if (!password) return 0;
      
      let strength = 0;
      
      if (password.length >= 8) strength += 20;
      if (password.length >= 12) strength += 10;
      if (/[a-z]/.test(password)) strength += 20;
      if (/[A-Z]/.test(password)) strength += 20;
      if (/[0-9]/.test(password)) strength += 20;
      if (/[^A-Za-z0-9]/.test(password)) strength += 10;
      
      return Math.min(strength, 100);
    },
    
    getPasswordStrengthClass(password) {
      const strength = this.getPasswordStrength(password);
      if (strength < 30) return 'strength-weak';
      if (strength < 60) return 'strength-medium';
      if (strength < 80) return 'strength-good';
      return 'strength-strong';
    },
    
    getPasswordStrengthText(password) {
      const strength = this.getPasswordStrength(password);
      if (!password) return '';
      if (strength < 30) return 'Contraseña débil';
      if (strength < 60) return 'Contraseña media';
      if (strength < 80) return 'Contraseña buena';
      return 'Contraseña fuerte';
    },
    
    formatRole(role) {
      const roles = {
        'admin': 'Administrador',
        'doctor': 'Doctor',
        'aseguradora': 'Aseguradora',
        'paciente': 'Paciente',
        'coordinador': 'Coordinador',
        'vertice': 'Vértice'
      };
      return roles[role] || role;
    },
    
    showMessage(text, type) {
      this.message = text;
      this.messageType = type || 'success';
      
      setTimeout(() => {
        this.message = '';
      }, 5000);
    },
    
    toggleCurrentPassword() {
      this.showCurrentPassword = !this.showCurrentPassword;
    },
    
    toggleNewPassword() {
      this.showNewPassword = !this.showNewPassword;
    },
    
    toggleConfirmPassword() {
      this.showConfirmPassword = !this.showConfirmPassword;
    }
  },
  
  mounted() {
    this.loadUserData();
  }
}
</script>

<style scoped>
.perfil-container {
  max-width: 900px;
  margin: 20px auto;
  padding: 20px;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

h1 {
  color: #007bff;
  margin-bottom: 30px;
  font-size: 2rem;
  text-align: center;
}

h2 {
  color: #6c757d;
  margin: 30px 0 20px;
  font-size: 1.4rem;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 10px;
}

h3 {
  color: #343a40;
  margin: 20px 0 15px;
  font-size: 1.2rem;
}

.profile-form {
  margin-top: 20px;
}

/* Sección de foto de perfil */
.photo-section {
  text-align: center;
  margin-bottom: 40px;
}

.photo-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.current-photo {
  position: relative;
  width: 150px;
  height: 150px;
  border-radius: 50%;
  overflow: hidden;
  border: 4px solid #007bff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.profile-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.profile-photo:hover {
  transform: scale(1.05);
}

.no-photo {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6c757d;
}

.no-photo i {
  font-size: 3rem;
  margin-bottom: 8px;
}

.no-photo span {
  font-size: 0.9rem;
  font-weight: 500;
}

.photo-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.upload-progress {
  width: 200px;
  text-align: center;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 5px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #007bff, #28a745);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.8rem;
  color: #6c757d;
  font-weight: 500;
}

.photo-requirements {
  max-width: 300px;
  text-align: center;
}

.photo-requirements small {
  color: #6c757d;
  font-size: 0.8rem;
  line-height: 1.4;
}

.photo-requirements i {
  color: #007bff;
  margin-right: 5px;
}

/* Formularios */
.personal-info-section,
.password-section {
  margin-bottom: 40px;
}

.form-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  flex: 1;
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #343a40;
  font-size: 0.95rem;
}

.form-control {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
  background-color: #fff;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-control.disabled {
  background-color: #f8f9fa;
  color: #6c757d;
  cursor: not-allowed;
}

.form-text {
  margin-top: 5px;
  font-size: 0.8rem;
  color: #6c757d;
}

/* Inputs de contraseña */
.password-input {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: color 0.2s ease;
}

.password-toggle:hover {
  color: #007bff;
}

/* Medidor de fuerza de contraseña */
.password-strength {
  margin-top: 8px;
}

.strength-bar {
  width: 100%;
  height: 4px;
  background-color: #e9ecef;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 5px;
}

.strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.strength-weak {
  background-color: #dc3545;
}

.strength-medium {
  background-color: #ffc107;
}

.strength-good {
  background-color: #fd7e14;
}

.strength-strong {
  background-color: #28a745;
}

.password-strength small {
  font-size: 0.8rem;
  font-weight: 500;
}

.text-danger {
  color: #dc3545;
  font-size: 0.8rem;
  margin-top: 5px;
  display: block;
}

.text-success {
  color: #28a745;
}

.text-warning {
  color: #ffc107;
}

/* Botones */
.buttons {
  display: flex;
  gap: 15px;
  margin: 30px 0;
  justify-content: center;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  min-width: 150px;
  justify-content: center;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
}

.btn-secondary {
  background: linear-gradient(135deg, #6c757d, #545b62);
  color: white;
}

.btn-danger {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
}

/* Alertas */
.alert {
  padding: 15px 20px;
  border-radius: 8px;
  margin: 20px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 500;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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

/* Información de la cuenta */
.account-info {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  padding: 25px;
  border-radius: 12px;
  margin-top: 30px;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 15px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.info-item label {
  font-size: 0.85rem;
  color: #6c757d;
  font-weight: 500;
  margin-bottom: 0;
}

.info-item span {
  font-weight: 600;
  color: #343a40;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Badges de rol */
.role-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-admin {
  background-color: #dc3545;
  color: white;
}

.role-doctor {
  background-color: #28a745;
  color: white;
}

.role-aseguradora {
  background-color: #007bff;
  color: white;
}

.role-paciente {
  background-color: #6c757d;
  color: white;
}

.role-coordinador {
  background-color: #fd7e14;
  color: white;
}

.role-vertice {
  background-color: #6f42c1;
  color: white;
}

/* Responsive */
@media (max-width: 768px) {
  .perfil-container {
    margin: 10px;
    padding: 15px;
  }
  
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .buttons {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn {
    min-width: auto;
  }
  }
</style>