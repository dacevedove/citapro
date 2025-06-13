<template>
  <div class="perfil-container">
    <!-- Header con gradient -->
    <div class="profile-header">
      <div class="header-content">
        <h1 class="page-title">Mi Perfil</h1>
        <p class="page-subtitle">Gestiona tu información personal y configuración de cuenta</p>
      </div>
    </div>
    
    <div class="profile-content">
      <!-- Sección principal con foto y datos básicos -->
      <div class="profile-main">
        <!-- Card de foto de perfil -->
        <div class="photo-card">
          <div class="card-header">
            <h3>Foto de Perfil</h3>
          </div>
          
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
                <div class="avatar-placeholder">
                  <i class="fas fa-user"></i>
                </div>
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
                class="btn btn-outline-danger"
                :disabled="uploadingPhoto"
              >
                <i class="fas fa-trash"></i>
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

        <!-- Card de información personal -->
        <div class="info-card">
          <div class="card-header">
            <h3>Información Personal</h3>
            <div class="role-badge" :class="'role-' + userData.role">
              {{ formatRole(userData.role) }}
            </div>
          </div>
          
          <div class="form-grid">
            <div class="form-group">
              <label for="nombre">Nombre</label>
              <input 
                type="text" 
                id="nombre" 
                v-model="userData.nombre" 
                class="form-input"
                placeholder="Ingresa tu nombre"
              >
            </div>
            
            <div class="form-group">
              <label for="apellido">Apellido</label>
              <input 
                type="text" 
                id="apellido" 
                v-model="userData.apellido" 
                class="form-input"
                placeholder="Ingresa tu apellido"
              >
            </div>
            
            <div class="form-group">
              <label for="telefono">Teléfono</label>
              <input 
                type="text" 
                id="telefono" 
                v-model="userData.telefono" 
                class="form-input"
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
                class="form-input disabled"
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
                class="form-input disabled"
              >
            </div>
          </div>
          
          <div class="card-actions">
            <button 
              @click="updateUserData" 
              class="btn btn-primary"
              :disabled="updatingProfile"
            >
              <i class="fas fa-save"></i>
              {{ updatingProfile ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Sección secundaria -->
      <div class="profile-secondary">
        <!-- Card de seguridad -->
        <div class="security-card">
          <div class="card-header">
            <h3>Seguridad</h3>
            <i class="fas fa-shield-alt"></i>
          </div>
          
          <div class="form-group">
            <label for="currentPassword">Contraseña Actual</label>
            <div class="password-input">
              <input 
                :type="showCurrentPassword ? 'text' : 'password'" 
                id="currentPassword" 
                v-model="passwordData.currentPassword" 
                class="form-input"
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
                class="form-input"
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
                class="form-input"
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
          
          <div class="card-actions">
            <button 
              @click="updatePassword" 
              class="btn btn-gradient"
              :disabled="!canUpdatePassword || updatingPassword"
            >
              <i class="fas fa-key"></i>
              {{ updatingPassword ? 'Cambiando...' : 'Cambiar Contraseña' }}
            </button>
          </div>
        </div>

        <!-- Card de información de cuenta -->
        <div class="account-card">
          <div class="card-header">
            <h3>Información de Cuenta</h3>
            <i class="fas fa-info-circle"></i>
          </div>
          
          <div class="account-stats">
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-calendar-plus"></i>
              </div>
              <div class="stat-content">
                <label>Miembro desde</label>
                <span>{{ formatDate(userData.creado_en) }}</span>
              </div>
            </div>
            
            <div class="stat-item" v-if="userData.ultimo_acceso">
              <div class="stat-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div class="stat-content">
                <label>Último acceso</label>
                <span>{{ formatDate(userData.ultimo_acceso) }}</span>
              </div>
            </div>
            
            <div class="stat-item">
              <div class="stat-icon">
                <i :class="userData.email_verificado ? 'fas fa-check-circle text-success' : 'fas fa-exclamation-circle text-warning'"></i>
              </div>
              <div class="stat-content">
                <label>Estado del email</label>
                <span :class="userData.email_verificado ? 'text-success' : 'text-warning'">
                  {{ userData.email_verificado ? 'Verificado' : 'Pendiente' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Toast/Alert para mensajes -->
    <Transition name="alert">
      <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-error']">
        <div class="alert-content">
          <i :class="messageType === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'"></i>
          <span>{{ message }}</span>
        </div>
        <button @click="message = ''" class="alert-close">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </Transition>
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
/* Variables CSS */
:root {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
  --warning-gradient: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
  --surface-white: #ffffff;
  --surface-light: #f8fafc;
  --surface-border: #e2e8f0;
  --text-primary: #1a202c;
  --text-secondary: #4a5568;
  --text-muted: #718096;
  --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  --border-radius: 12px;
  --border-radius-lg: 16px;
  --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.perfil-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 0;
}

/* Header */
.profile-header {
  background: var(--primary-gradient);
  color: white;
  padding: 3rem 2rem 2rem;
  position: relative;
  overflow: hidden;
}

.profile-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
  pointer-events: none;
}

.header-content {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-subtitle {
  font-size: 1.125rem;
  opacity: 0.9;
  margin: 0;
  font-weight: 400;
}

/* Content Layout */
.profile-content {
  max-width: 1200px;
  margin: -2rem auto 0;
  padding: 0 2rem 2rem;
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
  position: relative;
  z-index: 2;
}

.profile-main {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.profile-secondary {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Cards */
.photo-card,
.info-card,
.security-card,
.account-card {
  background: var(--surface-white);
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  transition: var(--transition);
}

.photo-card:hover,
.info-card:hover,
.security-card:hover,
.account-card:hover {
  box-shadow: var(--shadow-xl);
  transform: translateY(-2px);
}

.card-header {
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-bottom: 1px solid var(--surface-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
}

.card-header i {
  color: var(--text-muted);
  font-size: 1.25rem;
}

.card-actions {
  padding: 1.5rem 2rem;
  background: var(--surface-light);
  border-top: 1px solid var(--surface-border);
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

/* Photo Section */
.photo-container {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
}

.current-photo {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
  transition: var(--transition);
}

.current-photo:hover {
  transform: scale(1.05);
  box-shadow: var(--shadow-xl);
}

.profile-photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.no-photo {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-placeholder {
  width: 80px;
  height: 80px;
  background: var(--primary-gradient);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 2rem;
}

.photo-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.upload-progress {
  width: 100%;
  max-width: 200px;
  text-align: center;
}

.progress-bar {
  width: 100%;
  height: 6px;
  background-color: var(--surface-border);
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: var(--success-gradient);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.photo-info {
  text-align: center;
}

.photo-info small {
  color: var(--text-muted);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.photo-info i {
  color: var(--text-secondary);
}

/* Role Badge */
.role-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: var(--primary-gradient);
  color: white;
  box-shadow: var(--shadow-sm);
}

.role-admin {
  background: var(--danger-gradient);
}

.role-doctor {
  background: var(--success-gradient);
}

.role-aseguradora {
  background: var(--primary-gradient);
}

.role-paciente {
  background: linear-gradient(135deg, #6c757d, #495057);
}

.role-coordinador {
  background: var(--warning-gradient);
}

.role-vertice {
  background: linear-gradient(135deg, #6f42c1, #e83e8c);
}

/* Forms */
.form-grid {
  padding: 2rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  padding: 0.875rem 1rem;
  border: 2px solid var(--surface-border);
  border-radius: var(--border-radius);
  font-size: 1rem;
  transition: var(--transition);
  background-color: var(--surface-white);
  color: var(--text-primary);
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input.disabled {
  background-color: var(--surface-light);
  color: var(--text-muted);
  cursor: not-allowed;
}

.form-input::placeholder {
  color: var(--text-muted);
}

.form-hint {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 0.25rem;
}

/* Password Input */
.password-input {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: var(--transition);
}

.password-toggle:hover {
  color: var(--text-secondary);
  background-color: var(--surface-light);
}

/* Password Strength */
.password-strength {
  margin-top: 0.75rem;
}

.strength-bar {
  width: 100%;
  height: 4px;
  background-color: var(--surface-border);
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.strength-weak {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.strength-weak small {
  color: #dc2626;
}

.strength-medium {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.strength-medium small {
  color: #d97706;
}

.strength-good {
  background: linear-gradient(90deg, #3b82f6, #2563eb);
}

.strength-good small {
  color: #2563eb;
}

.strength-strong {
  background: linear-gradient(90deg, #10b981, #059669);
}

.strength-strong small {
  color: #059669;
}

.password-strength small {
  font-size: 0.8rem;
  font-weight: 500;
}

.error-text {
  color: #dc2626;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Buttons */
.btn {
  padding: 0.875rem 1.5rem;
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  text-decoration: none;
  min-height: 44px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

.btn:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn:not(:disabled):active {
  transform: translateY(0);
}

.btn-primary {
  background: var(--primary-gradient);
  color: white;
  box-shadow: var(--shadow-sm);
}

.btn-gradient {
  background: var(--secondary-gradient);
  color: white;
  box-shadow: var(--shadow-sm);
}

.btn-outline-danger {
  background: transparent;
  color: #dc2626;
  border: 2px solid #dc2626;
}

.btn-outline-danger:hover:not(:disabled) {
  background: #dc2626;
  color: white;
}

/* Account Stats */
.account-stats {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: var(--surface-light);
  border-radius: var(--border-radius);
  transition: var(--transition);
}

.stat-item:hover {
  background: #f1f5f9;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--primary-gradient);
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
  gap: 0.25rem;
}

.stat-content label {
  font-size: 0.875rem;
  color: var(--text-muted);
  font-weight: 500;
  margin: 0;
  text-transform: none;
  letter-spacing: normal;
}

.stat-content span {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
}

.text-success {
  color: #059669 !important;
}

.text-warning {
  color: #d97706 !important;
}

/* Security Card specific styling */
.security-card .form-group {
  margin-bottom: 1.5rem;
}

.security-card .card-header {
  background: linear-gradient(135deg, #fef7ff 0%, #f3e8ff 100%);
}

/* Alert/Toast */
.alert {
  position: fixed;
  top: 2rem;
  right: 2rem;
  z-index: 1000;
  min-width: 320px;
  padding: 1rem 1.5rem;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-xl);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  backdrop-filter: blur(10px);
}

.alert-success {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.95) 0%, rgba(5, 150, 105, 0.95) 100%);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.alert-error {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.95) 0%, rgba(220, 38, 38, 0.95) 100%);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.alert-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}

.alert-close {
  background: none;
  border: none;
  color: currentColor;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: var(--transition);
  opacity: 0.8;
}

.alert-close:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.1);
}

/* Animations */
.alert-enter-active,
.alert-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.alert-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.alert-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

/* Responsive Design */
@media (max-width: 1024px) {
  .profile-content {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .profile-secondary {
    order: -1;
  }
}

@media (max-width: 768px) {
  .profile-header {
    padding: 2rem 1rem 1rem;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .page-subtitle {
    font-size: 1rem;
  }
  
  .profile-content {
    margin-top: -1rem;
    padding: 0 1rem 1rem;
    gap: 1rem;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
    padding: 1.5rem;
  }
  
  .card-header {
    padding: 1rem 1.5rem;
  }
  
  .card-actions {
    padding: 1rem 1.5rem;
    flex-direction: column;
  }
  
  .photo-container {
    padding: 1.5rem;
  }
  
  .account-stats {
    padding: 1.5rem;
  }
  
  .alert {
    left: 1rem;
    right: 1rem;
    top: 1rem;
    min-width: auto;
  }
}
</style> 