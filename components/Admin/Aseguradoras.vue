<template>
  <div class="aseguradoras-container">
    <h1>Gestión de Aseguradoras</h1>
    
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
    
    <!-- Sección de prueba para CIE-10 -->
    <div class="test-section">
      <div class="test-card">
        <h3>🧪 Prueba del Componente CIE-10</h3>
        <p class="test-description">Este es un componente de prueba para validar la funcionalidad de búsqueda CIE-10. Se eliminará después de las pruebas.</p>
        
        <div class="test-form">
          <div class="form-group">
            <label for="cie10-test">Diagnóstico de Prueba:</label>
            <div class="input-container">
              <CIE10SearchDropdown
                v-model="diagnosticoPrueba"
                placeholder="Buscar código CIE-10 para prueba..."
                :required="false"
                @select="handleDiagnosticoSelect"
                @clear="handleDiagnosticoClear"
              />
            </div>
          </div>
          
          <!-- Mostrar diagnóstico seleccionado -->
          <div v-if="diagnosticoPrueba" class="selected-diagnostic">
            <h4>✅ Diagnóstico Seleccionado:</h4>
            <div class="diagnostic-info">
              <span class="diagnostic-code">{{ diagnosticoPrueba.code }}</span>
              <span class="diagnostic-description">{{ diagnosticoPrueba.description }}</span>
              <span class="diagnostic-level">Nivel {{ diagnosticoPrueba.level }}</span>
            </div>
            
            <!-- Información adicional de debug -->
            <div class="debug-info">
              <small>
                <strong>Datos del objeto:</strong>
                <pre>{{ JSON.stringify(diagnosticoPrueba, null, 2) }}</pre>
              </small>
            </div>
          </div>
          
          <!-- Botones de prueba -->
          <div class="test-actions">
            <button 
              class="btn btn-outline btn-sm" 
              @click="limpiarPrueba"
              v-if="diagnosticoPrueba"
            >
              🗑️ Limpiar Selección
            </button>
            <button 
              class="btn btn-info btn-sm" 
              @click="probarDiagnosticoAleatorio"
            >
              🎲 Seleccionar Diagnóstico Aleatorio
            </button>
            <button 
              class="btn btn-success btn-sm" 
              @click="probarBusquedaEspecifica"
            >
              🔍 Buscar "diabetes"
            </button>
            <button 
              class="btn btn-warning btn-sm" 
              @click="probarValidacion"
            >
              ⚠️ Probar Validación
            </button>
          </div>
          
          <!-- Estado de validación -->
          <div v-if="mostrarValidacion" class="validation-test">
            <h5>🧪 Prueba de Validación:</h5>
            <CIE10SearchDropdown
              v-model="diagnosticoValidacion"
              placeholder="Campo requerido para prueba..."
              :required="true"
              error-message="Este campo es obligatorio"
              @select="handleValidacionSelect"
              @clear="handleValidacionClear"
            />
          </div>
        </div>
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
import CIE10SearchDropdown from '../Shared/BusquedaCIE10.vue';

export default {
  name: 'Aseguradoras',
  components: {
    CIE10SearchDropdown
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
      // Datos para prueba de CIE-10
      diagnosticoPrueba: null,
      diagnosticoValidacion: null,
      mostrarValidacion: false
    }
  },
  mounted() {
    this.cargarAseguradoras();
  },
  methods: {
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
    },
    
    // Métodos para prueba de CIE-10
    handleDiagnosticoSelect(diagnostico) {
      console.log('🎯 Diagnóstico seleccionado:', diagnostico);
      console.log('Tipo de objeto:', typeof diagnostico);
      console.log('Propiedades:', Object.keys(diagnostico));
    },
    
    handleDiagnosticoClear() {
      console.log('🗑️ Diagnóstico limpiado');
    },
    
    handleValidacionSelect(diagnostico) {
      console.log('✅ Validación - Diagnóstico seleccionado:', diagnostico);
    },
    
    handleValidacionClear() {
      console.log('❌ Validación - Diagnóstico limpiado');
    },
    
    limpiarPrueba() {
      this.diagnosticoPrueba = null;
      console.log('🧹 Prueba limpiada');
    },
    
    probarDiagnosticoAleatorio() {
      // Usar datos más realistas que coincidan con el componente
      const diagnosticosPrueba = [
        { code: 'A00', description: 'Cólera', level: 1 },
        { code: 'A00.0', description: 'Cólera debida a Vibrio cholerae 01, biotipo cholerae', level: 2 },
        { code: 'B00', description: 'Infecciones herpéticas', level: 1 },
        { code: 'E10', description: 'Diabetes mellitus tipo 1', level: 1 },
        { code: 'E11', description: 'Diabetes mellitus tipo 2', level: 1 },
        { code: 'J00', description: 'Rinofaringitis aguda [resfriado común]', level: 1 },
        { code: 'I00', description: 'Fiebre reumática sin mención de complicación cardíaca', level: 1 }
      ];
      
      const diagnosticoAleatorio = diagnosticosPrueba[Math.floor(Math.random() * diagnosticosPrueba.length)];
      this.diagnosticoPrueba = diagnosticoAleatorio;
      console.log('🎲 Diagnóstico aleatorio seleccionado:', diagnosticoAleatorio);
    },
    
    probarBusquedaEspecifica() {
      // Simular selección directa de diabetes
      const diabetes = { code: 'E11', description: 'Diabetes mellitus tipo 2', level: 1 };
      this.diagnosticoPrueba = diabetes;
      console.log('🔍 Búsqueda específica - Diabetes seleccionada:', diabetes);
    },
    
    probarValidacion() {
      this.mostrarValidacion = !this.mostrarValidacion;
      this.diagnosticoValidacion = null;
      console.log('⚠️ Prueba de validación:', this.mostrarValidacion ? 'activada' : 'desactivada');
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

/* Estilos para la sección de prueba CIE-10 */
.test-section {
  margin-bottom: 30px;
  clear: both;
}

.test-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 25px;
  color: white;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: relative;
  z-index: 1;
}

.test-card h3 {
  margin: 0 0 10px 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: white;
}

.test-description {
  margin: 0 0 20px 0;
  opacity: 0.9;
  font-size: 0.95rem;
  color: white;
}

.test-form {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  padding: 20px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.test-form .form-group {
  margin-bottom: 20px;
}

.test-form label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: white;
  font-size: 14px;
}

/* Asegurar que el dropdown del CIE10 se vea correctamente */
.test-form .input-container {
  position: relative;
  z-index: 100;
}

.test-form :deep(.cie10-search-dropdown) {
  position: relative;
  z-index: 100;
}

.test-form :deep(.dropdown-menu) {
  z-index: 1001 !important;
  position: absolute !important;
}

/* Asegurar que el input del componente tenga el estilo correcto */
.test-form :deep(.cie10-search-dropdown .search-input) {
  background-color: white !important;
  color: #333 !important;
  border: 1px solid #ddd !important;
  border-radius: 4px !important;
  padding: 10px 40px 10px 12px !important;
  font-size: 14px !important;
  width: 100% !important;
  box-sizing: border-box;
}

.test-form :deep(.cie10-search-dropdown .search-input:focus) {
  outline: none !important;
  border-color: #007bff !important;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25) !important;
}

/* Estilo para el componente seleccionado */
.test-form :deep(.selected-item) {
  background-color: rgba(255, 255, 255, 0.95) !important;
  border: 1px solid #007bff !important;
  color: #333 !important;
}

.test-form :deep(.selected-code) {
  color: #007bff !important;
}

.test-form :deep(.selected-description) {
  color: #333 !important;
}

.selected-diagnostic {
  margin-top: 20px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  border-left: 4px solid #4ade80;
}

.selected-diagnostic h4 {
  margin: 0 0 10px 0;
  color: #4ade80;
  font-size: 1rem;
}

.diagnostic-info {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.diagnostic-code {
  font-family: 'Courier New', monospace;
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.9rem;
}

.diagnostic-description {
  flex: 1;
  font-weight: 500;
}

.diagnostic-level {
  background: rgba(168, 85, 247, 0.2);
  color: #c084fc;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Estilos adicionales para las nuevas funciones de prueba */
.debug-info {
  margin-top: 15px;
  padding: 10px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  border-left: 3px solid #fbbf24;
}

.debug-info pre {
  background: rgba(0, 0, 0, 0.2);
  color: #fbbf24;
  padding: 8px;
  border-radius: 4px;
  font-size: 11px;
  margin: 5px 0 0 0;
  overflow-x: auto;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.validation-test {
  margin-top: 20px;
  padding: 15px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  border-left: 4px solid #f59e0b;
}

.validation-test h5 {
  margin: 0 0 15px 0;
  color: #fbbf24;
  font-size: 1rem;
}

.test-actions {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
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

.btn-success {
  background-color: #28a745;
  color: white;
  border: none;
}

.btn-warning {
  background-color: #ffc107;
  color: #212529;
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
  .test-actions {
    flex-direction: column;
  }
  
  .debug-info pre {
    font-size: 10px;
  }
  
  .test-form {
    padding: 15px;
  }
  
  .diagnostic-info {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .filter-options {
    flex-direction: column;
    gap: 10px;
  }
}

@media (max-width: 576px) {
  .aseguradoras-container {
    padding: 15px;
  }
  
  .test-card {
    padding: 20px;
  }
  
  .test-form {
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
}
</style>