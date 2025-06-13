<template>
  <div class="perfil-container">
    <h1>Gestión de Cuenta</h1>
    
    <div class="cards-container">
      <!-- Card de información personal -->
      <div class="card">
        <div class="card-header">
          <h2>Información Personal</h2>
          <div class="role-badge" :class="'role-' + userData.role">
            {{ formatRole(userData.role) }}
          </div>
        </div>
        
        <div class="card-body">
          <form @submit.prevent="updateUserData">
            <div class="form-row">
              <div class="form-group">
                <label for="nombre">Nombre</label>
                <input 
                  type="text" 
                  id="nombre" 
                  v-model="userData.nombre" 
                  class="form-control"
                  placeholder="Ingresa tu nombre"
                >
              </div>
              
              <div class="form-group">
                <label for="apellido">Apellido</label>
                <input 
                  type="text" 
                  id="apellido" 
                  v-model="userData.apellido" 
                  class="form-control"
                  placeholder="Ingresa tu apellido"
                >
              </div>
            </div>
            
            <div class="form-group">
              <label for="telefono">Teléfono</label>
              <input 
                type="text" 
                id="telefono" 
                v-model="userData.telefono" 
                class="form-control"
                placeholder="Ej: +58 414 123 4567"
              >
            </div>
            
            <div class="form-group">
              <label for="email">Email</label>
              <input 
                type="email" 
                id="email" 
                :value="userData.email" 
                disabled 
                class="form-control disabled"
              >
              <small class="form-hint">Para cambiar el email, contacta al administrador</small>
            </div>
            
            <div class="form-group">
              <label for="cedula">Cédula</label>
              <input 
                type="text" 
                id="cedula" 
                :value="userData.cedula" 
                disabled 
                class="form-control disabled"
              >
            </div>
            
            <div class="form-actions">
              <button 
                type="submit" 
                class="btn btn-primary"
                :disabled="updatingProfile"
              >
                <i class="fas fa-save"></i>
                {{ updatingProfile ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Card de foto de perfil -->
      <div class="card">
        <div class="card-header">
          <h2>Foto de Perfil</h2>
        </div>
        
        <div class="card-body photo-section">
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
              </div>
            </div>
            
            <div class="photo-actions">
              <label for="photo-upload" class="btn btn-primary">
                <i class="fas fa-camera"></i>
                {{ userData.foto_perfil ? 'Cambiar' : 'Subir' }}
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
                class="btn btn-outline"
                :disabled="uploadingPhoto"
              >
                <i class="fas fa-trash"></i>
                Eliminar
              </button>
            </div>
            
            <!-- Progress bar para upload -->
            <div v-if="uploadingPhoto" class="upload-progress">
              <div class="progress-bar">
                <div class="progress-fill" :style="{width: uploadProgress + '%'}"></div>
              </div>
              <span class="progress-text">Subiendo... {{ uploadProgress }}%</span>
            </div>
            
            <div class="photo-info">
              <small><i class="fas fa-info-circle"></i> JPG, PNG, WebP • Máx. 5MB</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Card de seguridad -->
      <div class="card">
        <div class="card-header">
          <h2>Cambiar Contraseña</h2>
          <i class="fas fa-shield-alt"></i>
        </div>
        
        <div class="card-body">
          <form @submit.prevent="updatePassword">
            <div class="form-group">
              <label for="currentPassword">Contraseña Actual</label>
              <div class="password-input">
                <input 
                  :type="showCurrentPassword ? 'text' : 'password'" 
                  id="currentPassword" 
                  v-model="passwordData.currentPassword" 
                  class="form-control"
                  placeholder="Ingresa tu contraseña actual"
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
                  placeholder="Mínimo 8 caracteres"
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
              <div v-if="passwordData.newPassword" class="password-strength">
                <div class="strength-bar">
                  <div 
                    class="strength-fill" 
                    :class="getPasswordStrengthClass(passwordData.newPassword)"
                    :style="{width: getPasswordStrength(passwordData.newPassword) + '%'}"
                  ></div>
                </div>
                <small :class="getPasswordStrengthClass(passwordData.newPassword)">
                  {{ getPasswordStrengthText(passwordData.newPassword) }}
                </small>
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
                  placeholder="Repite la nueva contraseña"
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
                class="error-text"
              >
                <i class="fas fa-times-circle"></i> Las contraseñas no coinciden
              </small>
            </div>
            
            <div class="form-actions">
              <button 
                type="submit" 
                class="btn btn-primary"
                :disabled="!canUpdatePassword || updatingPassword"
              >
                <i class="fas fa-key"></i>
                {{ updatingPassword ? 'Cambiando...' : 'Cambiar Contraseña' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Card de información de cuenta -->
      <div class="card">
        <div class="card-header">
          <h2>Información de Cuenta</h2>
          <i class="fas fa-info-circle"></i>
        </div>
        
        <div class="card-body">
          <div class="account-stats">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-calendar-plus"></i>
              </div>
              <div class="stat-content">
                <span class="stat-label">Miembro desde</span>
                <span class="stat-value">{{ formatDate(userData.creado_en) }}</span>
              </div>
            </div>
            
            <div class="stat-item" v-if="userData.ultimo_acceso">
              <div class="stat-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div class="stat-content">
                <span class="stat-label">Último acceso</span>
                <span class="stat-value">{{ formatDate(userData.ultimo_acceso) }}</span>
              </div>
            </div>
            
            <div class="stat-item">
              <div class="stat-icon">
                <i :class="userData.email_verificado ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
              </div>
              <div class="stat-content">
                <span class="stat-label">Estado del email</span>
                <span :class="['stat-value', userData.email_verificado ? 'text-success' : 'text-warning']">
                  {{ userData.email_verificado ? 'Verificado' : 'Pendiente' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Alert para mensajes -->
    <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-danger']">
      <div class="alert-content">
        <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
        <span>{{ message }}</span>
      </div>
      <button @click="message = ''" class="alert-close">
        <i class="fas fa-times"></i>
      </button>
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
    
    formatDate(dateString) {
      if (!dateString) return '';
      try {
        return new Date(dateString).toLocaleDateString('es-ES', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
      } catch {
        return dateString;
      }
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
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  margin-bottom: 20px;
  color: var(--dark-color);
}

.cards-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.card {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e9ecef;
}

.card-header h2 {
  margin: 0;
  font-size: 20px;
  color: var(--dark-color);
}

.card-header i {
  color: var(--secondary-color);
  font-size: 18px;
}

.card-body {
  padding: 20px;
  flex: 1;
}

/* Role Badge */
.role-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
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
  background-color: var(--primary-color);
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

/* Forms */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: var(--dark-color);
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
}

.form-control.disabled {
  background-color: #f8f9fa;
  color: #6c757d;
  cursor: not-allowed;
}

.form-control::placeholder {
  color: #6c757d;
}

.form-hint {
  font-size: 12px;
  color: #6c757d;
  margin-top: 5px;
}

.form-actions {
  padding-top: 15px;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
}

/* Photo Section */
.photo-section {
  text-align: center;
}

.photo-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 15px;
}

.current-photo {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 3px solid #e9ecef;
}

.profile-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-photo {
  width: 100%;
  height: 100%;
  background-color: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 3rem;
}

.photo-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.upload-progress {
  width: 100%;
  max-width: 200px;
  text-align: center;
}

.progress-bar {
  width: 100%;
  height: 4px;
  background-color: #e9ecef;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 5px;
}

.progress-fill {
  height: 100%;
  background-color: var(--primary-color);
  border-radius: 2px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: var(--secondary-color);
  font-weight: 500;
}

.photo-info {
  text-align: center;
}

.photo-info small {
  color: #6c757d;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

/* Password Input */
.password-input {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  padding: 5px;
}

.password-toggle:hover {
  color: var(--primary-color);
}

/* Password Strength */
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

.strength-weak small {
  color: #dc3545;
}

.strength-medium {
  background-color: #ffc107;
}

.strength-medium small {
  color: #856404;
}

.strength-good {
  background-color: #17a2b8;
}

.strength-good small {
  color: #0c5460;
}

.strength-strong {
  background-color: #28a745;
}

.strength-strong small {
  color: #155724;
}

.password-strength small {
  font-size: 12px;
  font-weight: 500;
}

.error-text {
  color: #dc3545;
  font-size: 12px;
  margin-top: 5px;
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Account Stats */
.account-stats {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 4px;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-label {
  font-size: 12px;
  color: #6c757d;
  font-weight: 500;
}

.stat-value {
  font-weight: 600;
  color: var(--dark-color);
  font-size: 14px;
}

.text-success {
  color: #28a745 !important;
}

.text-warning {
  color: #ffc107 !important;
}

/* Buttons */
.btn {
  padding: 10px 15px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  font-size: 14px;
  border: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-outline:hover:not(:disabled) {
  background-color: #f8f9fa;
}

/* Alert */
.alert {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1000;
  min-width: 300px;
  padding: 15px 20px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

.alert-content {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 500;
}

.alert-close {
  background: none;
  border: none;
  color: currentColor;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  opacity: 0.8;
}

.alert-close:hover {
  opacity: 1;
  background-color: rgba(0, 0, 0, 0.1);
}

/* Responsive */
@media (max-width: 992px) {
  .cards-container {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .perfil-container {
    padding: 15px;
  }
  
  .card-body {
    padding: 15px;
  }
  
  .current-photo {
    width: 100px;
    height: 100px;
  }
  
  .photo-actions {
    flex-direction: column;
    width: 100%;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
  
  .alert {
    left: 15px;
    right: 15px;
    min-width: auto;
  }
}
</style>