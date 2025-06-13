<template>
  <div class="profile-photo-wrapper" :class="[`size-${size}`, { 'has-border': border, 'clickable': clickable }]" @click="handleClick">
    <img 
      v-if="photoUrl && !imageError" 
      :src="getPhotoUrl(photoUrl)" 
      :alt="`Foto de ${userName}`"
      class="profile-photo-img"
      @error="handleImageError"
      @load="handleImageLoad"
    >
    <div v-else class="profile-photo-placeholder">
      <i class="fas fa-user"></i>
      <span v-if="showInitials && initials" class="initials">{{ initials }}</span>
    </div>
    
    <!-- Loading indicator -->
    <div v-if="loading" class="photo-loading">
      <div class="spinner"></div>
    </div>
    
    <!-- Online indicator -->
    <div v-if="showOnlineStatus && isOnline" class="online-indicator"></div>
    
    <!-- Role badge -->
    <div v-if="showRole && userRole" class="role-indicator" :class="`role-${userRole}`">
      <i :class="getRoleIcon(userRole)"></i>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProfilePhoto',
  props: {
    // URL de la foto de perfil
    photoUrl: {
      type: String,
      default: null
    },
    // Nombre del usuario para el alt text
    userName: {
      type: String,
      default: 'Usuario'
    },
    // Tamaño de la foto: xs, sm, md, lg, xl
    size: {
      type: String,
      default: 'md',
      validator: value => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    // Mostrar borde
    border: {
      type: Boolean,
      default: false
    },
    // Es clickeable
    clickable: {
      type: Boolean,
      default: false
    },
    // Mostrar iniciales si no hay foto
    showInitials: {
      type: Boolean,
      default: true
    },
    // Iniciales del usuario
    initials: {
      type: String,
      default: null
    },
    // Mostrar indicador de estado online
    showOnlineStatus: {
      type: Boolean,
      default: false
    },
    // Estado online
    isOnline: {
      type: Boolean,
      default: false
    },
    // Mostrar indicador de rol
    showRole: {
      type: Boolean,
      default: false
    },
    // Rol del usuario
    userRole: {
      type: String,
      default: null
    },
    // Estado de carga
    loading: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      imageError: false
    }
  },
  methods: {
    getPhotoUrl(photoPath) {
      if (!photoPath) return '';
      // Si ya es una URL completa, devolverla tal como está
      if (photoPath.startsWith('http')) return photoPath;
      // Si es una ruta relativa, construir la URL completa
      return window.location.origin + photoPath;
    },
    
    handleImageError() {
      this.imageError = true;
      this.$emit('image-error');
    },
    
    handleImageLoad() {
      this.imageError = false;
      this.$emit('image-loaded');
    },
    
    handleClick() {
      if (this.clickable) {
        this.$emit('click');
      }
    },
    
    getRoleIcon(role) {
      const icons = {
        'admin': 'fas fa-crown',
        'doctor': 'fas fa-user-md',
        'aseguradora': 'fas fa-shield-alt',
        'paciente': 'fas fa-user',
        'coordinador': 'fas fa-users-cog',
        'vertice': 'fas fa-star'
      };
      return icons[role] || 'fas fa-user';
    }
  },
  watch: {
    photoUrl() {
      // Reset error state when photo URL changes
      this.imageError = false;
    }
  }
}
</script>

<style scoped>
.profile-photo-wrapper {
  position: relative;
  display: inline-block;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  transition: all 0.3s ease;
}

.profile-photo-wrapper.has-border {
  border: 3px solid var(--primary-color);
}

.profile-photo-wrapper.clickable {
  cursor: pointer;
}

.profile-photo-wrapper.clickable:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Tamaños */
.profile-photo-wrapper.size-xs {
  width: 24px;
  height: 24px;
}

.profile-photo-wrapper.size-sm {
  width: 32px;
  height: 32px;
}

.profile-photo-wrapper.size-md {
  width: 48px;
  height: 48px;
}

.profile-photo-wrapper.size-lg {
  width: 80px;
  height: 80px;
}

.profile-photo-wrapper.size-xl {
  width: 120px;
  height: 120px;
}

.profile-photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.profile-photo-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--secondary-color);
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

/* Iconos para diferentes tamaños */
.size-xs .profile-photo-placeholder i {
  font-size: 12px;
}

.size-sm .profile-photo-placeholder i {
  font-size: 16px;
}

.size-md .profile-photo-placeholder i {
  font-size: 20px;
}

.size-lg .profile-photo-placeholder i {
  font-size: 32px;
}

.size-xl .profile-photo-placeholder i {
  font-size: 48px;
}

/* Iniciales */
.initials {
  font-weight: 600;
  font-size: 0.7em;
  color: var(--primary-color);
  margin-top: 2px;
}

.size-xs .initials {
  font-size: 8px;
  margin-top: 0;
}

.size-sm .initials {
  font-size: 10px;
  margin-top: 1px;
}

.size-md .initials {
  font-size: 14px;
}

.size-lg .initials {
  font-size: 18px;
}

.size-xl .initials {
  font-size: 24px;
}

/* Loading spinner */
.photo-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.size-xs .spinner,
.size-sm .spinner {
  width: 12px;
  height: 12px;
  border-width: 1px;
}

.size-lg .spinner,
.size-xl .spinner {
  width: 24px;
  height: 24px;
  border-width: 3px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Indicador online */
.online-indicator {
  position: absolute;
  bottom: 10%;
  right: 10%;
  width: 25%;
  height: 25%;
  min-width: 8px;
  min-height: 8px;
  max-width: 16px;
  max-height: 16px;
  background-color: #28a745;
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Indicador de rol */
.role-indicator {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: white;
  border: 2px solid white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.size-xs .role-indicator,
.size-sm .role-indicator {
  width: 14px;
  height: 14px;
  font-size: 7px;
  top: -1px;
  right: -1px;
  border-width: 1px;
}

.size-lg .role-indicator,
.size-xl .role-indicator {
  width: 24px;
  height: 24px;
  font-size: 12px;
  top: -3px;
  right: -3px;
}

.role-admin {
  background-color: #dc3545;
}

.role-doctor {
  background-color: #28a745;
}

.role-aseguradora {
  background-color: #007bff;
}

.role-paciente {
  background-color: #6c757d;
}

.role-coordinador {
  background-color: #fd7e14;
}

.role-vertice {
  background-color: #6f42c1;
}

/* CSS Variables */
:root {
  --primary-color: #007bff;
  --secondary-color: #6c757d;
}
</style>