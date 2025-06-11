<template>
  <div class="tipos-bloque-container">
    <div class="header-section">
      <h1>Tipos de Bloque Horario</h1>
      <button @click="abrirModal()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo Tipo
      </button>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando tipos de bloque...</p>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div class="tipos-grid">
      <div v-for="tipo in tipos" :key="tipo.id" class="tipo-card">
        <div class="tipo-header">
          <div 
            class="color-indicator" 
            :style="{ backgroundColor: tipo.color }"
          ></div>
          <h3>{{ tipo.nombre }}</h3>
          <div class="tipo-actions">
            <button 
              @click="abrirModal(tipo)"
              class="btn-icon"
              title="Editar"
            >
              <i class="fas fa-edit"></i>
            </button>
            <button 
              @click="cambiarEstado(tipo.id, !tipo.activo)"
              :class="`btn-icon ${tipo.activo ? 'btn-danger' : 'btn-success'}`"
              :title="tipo.activo ? 'Desactivar' : 'Activar'"
            >
              <i :class="`fas ${tipo.activo ? 'fa-eye-slash' : 'fa-eye'}`"></i>
            </button>
          </div>
        </div>
        <div class="tipo-body">
          <p class="tipo-descripcion">{{ tipo.descripcion || 'Sin descripción' }}</p>
          <div class="tipo-estado">
            <span :class="`estado-badge ${tipo.activo ? 'activo' : 'inactivo'}`">
              {{ tipo.activo ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>{{ editando ? 'Editar Tipo de Bloque' : 'Nuevo Tipo de Bloque' }}</h2>
          <button @click="cerrarModal" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Nombre *</label>
            <input
              type="text"
              v-model="formData.nombre"
              class="form-control"
              placeholder="Ej: APS, Consulta Privada"
              required
            />
          </div>

          <div class="form-group">
            <label>Descripción</label>
            <textarea
              v-model="formData.descripcion"
              class="form-control"
              rows="3"
              placeholder="Descripción del tipo de bloque horario"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Color</label>
            <div class="color-picker-container">
              <input
                type="color"
                v-model="formData.color"
                class="color-picker"
              />
              <input
                type="text"
                v-model="formData.color"
                class="color-text"
                placeholder="#007bff"
              />
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-container">
              <input
                type="checkbox"
                v-model="formData.activo"
              />
              <span class="checkmark"></span>
              Activo
            </label>
          </div>

          <div v-if="errorModal" class="alert alert-danger">
            {{ errorModal }}
          </div>

          <div class="modal-footer">
            <button type="button" @click="cerrarModal" class="btn btn-outline">
              Cancelar
            </button>
            <button type="button" @click="guardarTipo" class="btn btn-primary" :disabled="guardando">
              {{ guardando ? 'Guardando...' : (editando ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'TiposBloqueHorario',
  data() {
    return {
      tipos: [],
      loading: false,
      showModal: false,
      editando: false,
      guardando: false,
      error: '',
      errorModal: '',
      formData: {
        id: '',
        nombre: '',
        descripcion: '',
        color: '#007bff',
        activo: true
      }
    };
  },
  mounted() {
    this.cargarTipos();
  },
  methods: {
    async cargarTipos() {
      this.loading = true;
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/horarios/tipos_bloque.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.tipos = response.data;
      } catch (error) {
        console.error('Error al cargar tipos:', error);
        this.error = 'Error al cargar los tipos de bloque';
      } finally {
        this.loading = false;
      }
    },

    abrirModal(tipo = null) {
      if (tipo) {
        this.formData = {
          id: tipo.id,
          nombre: tipo.nombre,
          descripcion: tipo.descripcion || '',
          color: tipo.color,
          activo: tipo.activo
        };
        this.editando = true;
      } else {
        this.formData = {
          id: '',
          nombre: '',
          descripcion: '',
          color: '#007bff',
          activo: true
        };
        this.editando = false;
      }
      this.errorModal = '';
      this.showModal = true;
    },

    cerrarModal() {
      this.showModal = false;
      this.formData = {
        id: '',
        nombre: '',
        descripcion: '',
        color: '#007bff',
        activo: true
      };
      this.errorModal = '';
    },

    async guardarTipo() {
      if (!this.formData.nombre.trim()) {
        this.errorModal = 'El nombre es requerido';
        return;
      }

      this.guardando = true;
      try {
        const token = localStorage.getItem('token');
        const url = '/api/horarios/tipos_bloque.php';
        const method = this.editando ? 'put' : 'post';
        
        const response = await axios[method](url, this.formData, {
          headers: { 'Authorization': `Bearer ${token}` }
        });

        if (response.data.success) {
          await this.cargarTipos();
          this.cerrarModal();
        } else {
          this.errorModal = response.data.error || 'Error al guardar';
        }
      } catch (error) {
        console.error('Error al guardar:', error);
        this.errorModal = error.response?.data?.error || 'Error al guardar el tipo de bloque';
      } finally {
        this.guardando = false;
      }
    },

    async cambiarEstado(id, nuevoEstado) {
      try {
        const token = localStorage.getItem('token');
        await axios.put('/api/horarios/tipos_bloque.php', {
          id,
          activo: nuevoEstado
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        await this.cargarTipos();
      } catch (error) {
        console.error('Error al cambiar estado:', error);
        this.error = 'Error al cambiar el estado';
      }
    }
  }
};
</script>

<style scoped>
.tipos-bloque-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

h1 {
  margin: 0;
  color: var(--dark-color);
}

.tipos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.tipo-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s;
}

.tipo-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.tipo-header {
  display: flex;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
}

.color-indicator {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  margin-right: 12px;
  border: 2px solid #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.tipo-header h3 {
  flex: 1;
  margin: 0;
  color: var(--dark-color);
  font-size: 16px;
}

.tipo-actions {
  display: flex;
  gap: 5px;
}

.btn-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid #ced4da;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover {
  background-color: #e9ecef;
}

.btn-icon.btn-danger {
  color: var(--danger-color);
}

.btn-icon.btn-success {
  color: var(--success-color);
}

.tipo-body {
  padding: 15px;
}

.tipo-descripcion {
  color: var(--secondary-color);
  margin-bottom: 10px;
  font-size: 14px;
}

.estado-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.estado-badge.activo {
  background-color: #d4edda;
  color: #155724;
}

.estado-badge.inactivo {
  background-color: #f8d7da;
  color: #721c24;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 50px 0;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.alert {
  padding: 12px 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.alert-danger {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-container {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--secondary-color);
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #e9ecef;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 16px;
}

.color-picker-container {
  display: flex;
  gap: 10px;
  align-items: center;
}

.color-picker {
  width: 50px;
  height: 40px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.color-text {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
}

.checkbox-container {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.checkbox-container input {
  margin-right: 8px;
}

.btn {
  padding: 10px 15px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: #0069d9;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-outline:hover {
  background-color: #e9ecef;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>