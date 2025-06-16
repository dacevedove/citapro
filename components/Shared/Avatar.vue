<template>
  <div 
    class="avatar" 
    :class="[
      `avatar-${size}`,
      { 'avatar-clickable': clickable, 'avatar-bordered': bordered }
    ]"
    @click="handleClick"
  >
    <img 
      v-if="photoUrl && !imageError" 
      :src="getPhotoUrl(photoUrl)" 
      :alt="`Avatar de ${name}`"
      class="avatar-image"
      @error="handleImageError"
    >
    <div v-else class="avatar-placeholder">
      <span v-if="initials" class="avatar-initials">{{ initials }}</span>
      <i v-else class="fas fa-user"></i>
    </div>
    
    <!-- Status indicator -->
    <div v-if="status" class="avatar-status" :class="`status-${status}`"></div>
  </div>
</template>

<script>
export default {
  name: 'Avatar',
  props: {
    photoUrl: {
      type: String,
      default: null
    },
    name: {
      type: String,
      default: 'Usuario'
    },
    initials: {
      type: String,
      default: null
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    clickable: {
      type: Boolean,
      default: false
    },
    bordered: {
      type: Boolean,
      default: false
    },
    status: {
      type: String,
      default: null,
      validator: value => !value || ['online', 'offline', 'busy', 'away'].includes(value)
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
      if (photoPath.startsWith('http')) return photoPath;
      return window.location.origin + photoPath;
    },
    
    handleImageError() {
      this.imageError = true;
      this.$emit('error');
    },
    
    handleClick() {
      if (this.clickable) {
        this.$emit('click');
      }
    }
  },
  watch: {
    photoUrl() {
      this.imageError = false;
    }
  }
}
</script>

<style scoped>
.avatar {
  position: relative;
  display: inline-block;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  flex-shrink: 0;
}

.avatar-clickable {
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.avatar-clickable:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.avatar-bordered {
  border: 2px solid #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Sizes */
.avatar-xs {
  width: 24px;
  height: 24px;
}

.avatar-sm {
  width: 32px;
  height: 32px;
}

.avatar-md {
  width: 48px;
  height: 48px;
}

.avatar-lg {
  width: 64px;
  height: 64px;
}

.avatar-xl {
  width: 96px;
  height: 96px;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.avatar-initials {
  font-size: 0.6em;
  font-weight: 600;
  text-transform: uppercase;
}

/* Icon sizes */
.avatar-xs .fas {
  font-size: 12px;
}

.avatar-sm .fas {
  font-size: 14px;
}

.avatar-md .fas {
  font-size: 18px;
}

.avatar-lg .fas {
  font-size: 24px;
}

.avatar-xl .fas {
  font-size: 36px;
}

/* Initials sizes */
.avatar-xs .avatar-initials {
  font-size: 10px;
}

.avatar-sm .avatar-initials {
  font-size: 12px;
}

.avatar-md .avatar-initials {
  font-size: 16px;
}

.avatar-lg .avatar-initials {
  font-size: 20px;
}

.avatar-xl .avatar-initials {
  font-size: 32px;
}

/* Status indicator */
.avatar-status {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 25%;
  height: 25%;
  border-radius: 50%;
  border: 2px solid white;
  min-width: 8px;
  min-height: 8px;
}

.status-online {
  background-color: #28a745;
}

.status-offline {
  background-color: #6c757d;
}

.status-busy {
  background-color: #dc3545;
}

.status-away {
  background-color: #ffc107;
}
</style>