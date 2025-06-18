<template>
  <div class="aseguradoras-container">
    <h1>Gestión de Aseguradoras</h1>
    
    <!-- ==================== INICIO SECCIÓN DE PRUEBA ==================== -->
    <!-- ELIMINAR ESTA SECCIÓN COMPLETA DESPUÉS DE PROBAR -->
    <div class="test-section">
      <h2>🧪 Prueba del Selector de Diagnósticos CIE-10</h2>
      <p class="test-description">
        Esta sección es solo para probar el componente DiagnosticSelector. 
        <strong>Eliminar después de las pruebas.</strong>
      </p>
      
      <div class="diagnostic-test-container">
        <DiagnosticSelector
          v-model="diagnosticosPrueba"
          label="Diagnósticos de Prueba CIE-10"
          placeholder="Buscar diagnósticos por código o descripción (ej: diabetes, A00, etc.)"
          :max-results="8"
          :max-selections="5"
          @diagnostic-selected="onDiagnosticSelected"
          @diagnostic-removed="onDiagnosticRemoved"
          @search="onSearch"
        />
        
        <!-- Debug info -->
        <div v-if="diagnosticosPrueba.length > 0" class="debug-info">
          <h4>🔍 Debug - Diagnósticos seleccionados:</h4>
          <pre>{{ JSON.stringify(diagnosticosPrueba, null, 2) }}</pre>
        </div>
        
        <div class="test-actions">
          <button @click="clearDiagnostics" class="btn btn-outline">
            <i class="fas fa-trash"></i> Limpiar Diagnósticos
          </button>
          <button @click="addSampleDiagnostics" class="btn btn-info">
            <i class="fas fa-plus"></i> Agregar Ejemplos
          </button>
        </div>
      </div>
    </div>
    <!-- ==================== FIN SECCIÓN DE PRUEBA ==================== -->
    
    <div class="header-section">
      <button class="btn btn-primary" @click="abrirModal">
        <i class="fas fa-plus"></i> Nueva Aseguradora
      </button>
    </div>
    
    <div class="filters-section">
      <div class="search-box">
        <input 
          type="text" 
          v-model="busqueda"
          placeholder="Buscar por nombre o RIF" 
          class="form-control"
        >
        <button class="search-btn" @click="filtrarAseguradoras">
          <i class="fas fa-search"></i>
        </button>
      </div>
      
      <div class="filter-options">
        <div class="filter-item">
          <label>Estado:</label>
          <select class="form-control" v-model="filtroEstado" @change="filtrarAseguradoras">
            <option value="">Todos</option>
            <option value="activo">Activa</option>
            <option value="inactivo">Inactiva</option>
          </select>
        </div>
        
        <button class="btn btn-outline" @click="resetearFiltros">
          <i class="fas fa-sync"></i> Resetear
        </button>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="aseguradoras-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Correo electrónico</th>
            <th>Teléfono</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="aseguradoras.length === 0">
            <td colspan="5" class="text-center">No hay aseguradoras registradas</td>
          </tr>
          <tr v-for="aseguradora in aseguradorasFiltradas" :key="aseguradora.id">
            <td>{{ aseguradora.nombre_comercial }}</td>
            <td>{{ aseguradora.email }}</td>
            <td>{{ aseguradora.telefono }}</td>
            <td>{{ aseguradora.estado === 'activo' ? 'Activa' : 'Inactiva' }}</td>
            <td>
              <button class="btn btn-sm btn-info" title="Editar" @click="editarAseguradora(aseguradora)">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-danger" title="Eliminar" @click="eliminarAseguradora(aseguradora)">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal para crear aseguradora -->
    <div class="modal" v-if="modalVisible">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ modoEdicion ? 'Editar' : 'Nueva' }} Aseguradora</h2>
          <span class="close" @click="cerrarModal">&times;</span>
        </div>
        <div class="modal-body">
          <form @submit.prevent="guardarAseguradora">
            <div class="form-group">
              <label>Nombre Comercial:</label>
              <input type="text" v-model="aseguradoraActual.nombre_comercial" required class="form-control">
            </div>
            
            <template v-if="!modoEdicion">
              <div class="form-group">
                <label>Nombre:</label>
                <input type="text" v-model="aseguradoraActual.nombre" required class="form-control">
              </div>
              <div class="form-group">
                <label>Apellido:</label>
                <input type="text" v-model="aseguradoraActual.apellido" required class="form-control">
              </div>
              <div class="form-group">
                <label>Cédula/RIF:</label>
                <input type="text" v-model="aseguradoraActual.cedula" required class="form-control">
              </div>
            </template>
            
            <div class="form-group">
              <label>Correo Electrónico:</label>
              <input type="email" v-model="aseguradoraActual.email" required class="form-control">
            </div>
            <div class="form-group">
              <label>Teléfono:</label>
              <input type="tel" v-model="aseguradoraActual.telefono" required class="form-control">
            </div>
            
            <template v-if="!modoEdicion">
              <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" v-model="aseguradoraActual.password" required class="form-control">
              </div>
            </template>
            
            <template v-if="modoEdicion">
              <div class="form-group">
                <label>Estado:</label>
                <select class="form-control" v-model="aseguradoraActual.estado">
                  <option value="activo">Activa</option>
                  <option value="inactivo">Inactiva</option>
                </select>
              </div>
            </template>
            
            <div class="form-actions">
              <button type="button" class="btn btn-outline" @click="cerrarModal">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="enviando">
                {{ enviando ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal" v-if="modalEliminarVisible">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h2>Confirmar eliminación</h2>
          <span class="close" @click="cerrarModalEliminar">&times;</span>
        </div>
        <div class="modal-body">
          <p>¿Está seguro de eliminar la aseguradora "{{ aseguradoraEliminar?.nombre_comercial }}"?</p>
          <div class="form-actions">
            <button type="button" class="btn btn-outline" @click="cerrarModalEliminar">Cancelar</button>
            <button type="button" class="btn btn-danger" :disabled="enviando" @click="confirmarEliminar">
              {{ enviando ? 'Eliminando...' : 'Eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
// IMPORTACIÓN DE PRUEBA - ELIMINAR DESPUÉS
import DiagnosticSelector from '../Shared/DiagnosticSelector.vue';

export default {
  name: 'Aseguradoras',
  
  // COMPONENTE DE PRUEBA - ELIMINAR DESPUÉS
  components: {
    DiagnosticSelector
  },
  
  data() {
    return {
      aseguradoras: [],
      aseguradorasFiltradas: [],
      busqueda: '',
      filtroEstado: '',
      modalVisible: false,
      modalEliminarVisible: false,
      enviando: false,
      modoEdicion: false,
      aseguradoraActual: {
        id: null,
        nombre_comercial: '',
        nombre: '',
        apellido: '',
        cedula: '',
        email: '',
        telefono: '',
        password: '',
        estado: 'activo'
      },
      aseguradoraEliminar: null,
      
      // DATOS DE PRUEBA - ELIMINAR DESPUÉS
      diagnosticosPrueba: []
    }
  },
  mounted() {
    this.cargarAseguradoras();
  },
  methods: {
    // ==================== MÉTODOS DE PRUEBA - ELIMINAR DESPUÉS ====================
    onDiagnosticSelected(diagnostic) {
      console.log('🩺 Diagnóstico seleccionado:', diagnostic);
      alert(`Diagnóstico seleccionado: ${diagnostic.code} - ${diagnostic.description}`);
    },
    
    onDiagnosticRemoved(diagnostic) {
      console.log('🗑️ Diagnóstico eliminado:', diagnostic);
      alert(`Diagnóstico eliminado: ${diagnostic.code}`);
    },
    
    onSearch(searchData) {
      console.log('🔍 Búsqueda realizada:', searchData);
    },
    
    clearDiagnostics() {
      this.diagnosticosPrueba = [];
      console.log('🧹 Diagnósticos limpiados');
    },
    
    addSampleDiagnostics() {
      // Agregar algunos diagnósticos de ejemplo
      const ejemplos = [
        {
          level: 1,
          code: "E10",
          description: "Diabetes mellitus insulinodependiente"
        },
        {
          level: 0,
          code: "I10-I16", 
          description: "Enfermedades hipertensivas"
        }
      ];
      
      this.diagnosticosPrueba = [...this.diagnosticosPrueba, ...ejemplos];
      console.log('📝 Diagnósticos de ejemplo agregados');
    },
    // ==================== FIN MÉTODOS DE PRUEBA ====================
    
    async cargarAseguradoras() {
      try {
        const token = localStorage.getItem('token');
        if (!token) {
          this.$router.push('/login');
          return;
        }

        const response = await axios.get('/api/aseguradoras/listar.php', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        this.aseguradoras = response.data;
        this.aseguradorasFiltradas = [...this.aseguradoras];
      } catch (error) {
        console.error('Error al cargar aseguradoras:', error);
        if (error.response && error.response.status === 401) {
          this.$router.push('/login');
        }
        alert('Error al cargar datos: ' + (error.response?.data?.error || error.message));
      }
    },
    filtrarAseguradoras() {
      this.aseguradorasFiltradas = this.aseguradoras.filter(a => {
        const coincideBusqueda = !this.busqueda || 
          a.nombre_comercial.toLowerCase().includes(this.busqueda.toLowerCase()) || 
          a.email.toLowerCase().includes(this.busqueda.toLowerCase());
        
        const coincideEstado = !this.filtroEstado || a.estado === this.filtroEstado;
        
        return coincideBusqueda && coincideEstado;
      });
    },
    resetearFiltros() {
      this.busqueda = '';
      this.filtroEstado = '';
      this.aseguradorasFiltradas = [...this.aseguradoras];
    },
    abrirModal() {
      this.modoEdicion = false;
      this.aseguradoraActual = {
        id: null,
        nombre_comercial: '',
        nombre: '',
        apellido: '',
        cedula: '',
        email: '',
        telefono: '',
        password: '',
        estado: 'activo'
      };
      this.modalVisible = true;
    },
    editarAseguradora(aseguradora) {
      this.modoEdicion = true;
      this.aseguradoraActual = { ...aseguradora };
      this.modalVisible = true;
    },
    cerrarModal() {
      this.modalVisible = false;
      
      if (this.modoEdicion) {
        setTimeout(() => {
          this.cargarAseguradoras();
        }, 500);
      }
    },
    eliminarAseguradora(aseguradora) {
      this.aseguradoraEliminar = aseguradora;
      this.modalEliminarVisible = true;
    },
    cerrarModalEliminar() {
      this.modalEliminarVisible = false;
      this.aseguradoraEliminar = null;
    },
    async guardarAseguradora() {
      this.enviando = true;
      try {
        const token = localStorage.getItem('token');
        
        if (this.modoEdicion) {
          // Actualizar aseguradora
          await axios.put('/api/aseguradoras/actualizar.php', 
            this.aseguradoraActual,
            {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
              }
            }
          );
          alert('Aseguradora actualizada exitosamente');
        } else {
          // Crear nueva aseguradora
          await axios.post('/api/aseguradoras/crear.php', 
            this.aseguradoraActual,
            {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
              }
            }
          );
          alert('Aseguradora creada exitosamente');
        }
        
        this.cerrarModal();
        this.cargarAseguradoras();
      } catch (error) {
        console.error('Error al guardar aseguradora:', error);
        let mensaje = 'Ocurrió un error al guardar la aseguradora';
        
        if (error.response && error.response.data && error.response.data.error) {
          mensaje = error.response.data.error;
        }
        
        alert(mensaje);
      } finally {
        this.enviando = false;
      }
    },
    async confirmarEliminar() {
      this.enviando = true;
      try {
        const token = localStorage.getItem('token');
        
        await axios.delete('/api/aseguradoras/eliminar.php', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          },
          data: { id: this.aseguradoraEliminar.id }
        });

        alert('Aseguradora eliminada exitosamente');
        this.cerrarModalEliminar();
        this.cargarAseguradoras();
      } catch (error) {
        console.error('Error al eliminar aseguradora:', error);
        let mensaje = 'Ocurrió un error al eliminar la aseguradora';
        
        if (error.response && error.response.data && error.response.data.error) {
          mensaje = error.response.data.error;
        }
        
        alert(mensaje);
      } finally {
        this.enviando = false;
      }
    }
  }
}
</script>

<style scoped>
.aseguradoras-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

h1 {
  margin-bottom: 20px;
  color: var(--dark-color);
}

/* ==================== ESTILOS DE PRUEBA - ELIMINAR DESPUÉS ==================== */
.test-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 30px;
  border: 3px dashed #ffd700;
}

.test-section h2 {
  margin: 0 0 10px 0;
  font-size: 24px;
  font-weight: 600;
}

.test-description {
  margin: 0 0 20px 0;
  opacity: 0.9;
  font-size: 14px;
}

.diagnostic-test-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
  color: #333;
}

.debug-info {
  margin-top: 20px;
  padding: 15px;
  background: #f8f9fa;
  border-left: 4px solid #007bff;
  border-radius: 4px;
}

.debug-info h4 {
  margin: 0 0 10px 0;
  color: #007bff;
  font-size: 14px;
}

.debug-info pre {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 10px;
  border-radius: 4px;
  font-size: 12px;
  margin: 0;
  overflow-x: auto;
  max-height: 200px;
}

.test-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
  justify-content: center;
}
/* ==================== FIN ESTILOS DE PRUEBA ==================== */

.header-section {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 20px;
}

.filters-section {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  margin-bottom: 20px;
}

.search-box {
  display: flex;
  margin-bottom: 15px;
}

.search-box input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px 0 0 4px;
  font-size: 16px;
}

.search-btn {
  padding: 10px 15px;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
}

.filter-options {
  display: flex;
  align-items: center;
  gap: 15px;
}

.filter-item {
  flex: 1;
}

.filter-item label {
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

.aseguradoras-table {
  width: 100%;
  border-collapse: collapse;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.aseguradoras-table th,
.aseguradoras-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.aseguradoras-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: var(--dark-color);
}

.table-responsive {
  overflow-x: auto;
}

.text-center {
  text-align: center;
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
  border: 1px solid var(--primary-color);
  color: white;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-sm {
  padding: 5px 10px;
  font-size: 14px;
  margin-right: 5px;
}

.btn-info {
  background-color: var(--info-color, #17a2b8);
  color: white;
  border: none;
}

.btn-danger {
  background-color: var(--danger-color, #dc3545);
  color: white;
  border: none;
}

/* Estilos para el modal */
.modal {
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 600px;
  max-width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-sm {
  width: 400px;
}

.modal-header {
  padding: 15px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e9ecef;
}

.modal-body {
  padding: 20px;
}

.close {
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

/* Responsivo para dispositivos móviles */
@media (max-width: 768px) {
  .filter-options {
    flex-direction: column;
    gap: 10px;
  }
  
  /* RESPONSIVE DE PRUEBA - ELIMINAR DESPUÉS */
  .test-section {
    padding: 15px;
  }
  
  .test-actions {
    flex-direction: column;
    align-items: center;
  }
}

@media (max-width: 576px) {
  .aseguradoras-container {
    padding: 15px;
  }
  
  .modal-content {
    width: 95%;
    margin: 10px;
  }
  
  .modal-body {
    padding: 15px;
  }
  
  .btn-sm {
    padding: 8px 12px;
    font-size: 12px;
  }
  
  .search-box {
    flex-direction: column;
  }
  
  .search-box input {
    border-radius: 4px 4px 0 0;
  }
  
  .search-btn {
    border-radius: 0 0 4px 4px;
  }
  
  /* RESPONSIVE DE PRUEBA - ELIMINAR DESPUÉS */
  .test-section h2 {
    font-size: 20px;
  }
  
  .debug-info pre {
    font-size: 10px;
  }
}
</style>s