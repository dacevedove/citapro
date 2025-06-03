<template>
    <div class="especialidades-container">
      <h1>Gestión de Especialidades</h1>
      
      <div class="cards-container">
        <!-- Especialidades Card -->
        <div class="card">
          <div class="card-header">
            <h2>Especialidades</h2>
            <button @click="abrirModalEspecialidad()" class="btn btn-primary">
              <i class="fas fa-plus"></i> Nueva Especialidad
            </button>
          </div>
          
          <div class="search-box">
            <input 
              type="text" 
              v-model="filtroEspecialidad"
              placeholder="Buscar especialidad" 
              class="form-control"
            >
            <button class="search-btn">
              <i class="fas fa-search"></i>
            </button>
          </div>
          
          <div v-if="cargandoEspecialidades" class="loading-container">
            <div class="spinner"></div>
            <p>Cargando especialidades...</p>
          </div>
          
          <div v-else-if="especialidades.length === 0" class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <p>No hay especialidades registradas</p>
            <button @click="abrirModalEspecialidad()" class="btn btn-primary">
              Agregar Especialidad
            </button>
          </div>
          
          <div v-else class="list-container">
            <div 
              v-for="especialidad in especialidadesFiltradas" 
              :key="especialidad.id"
              class="list-item"
              :class="{ 'active': especialidadSeleccionada && especialidadSeleccionada.id === especialidad.id }"
              @click="seleccionarEspecialidad(especialidad)"
            >
              <div class="item-info">
                <h3>{{ especialidad.nombre }}</h3>
                <p v-if="especialidad.descripcion">{{ especialidad.descripcion }}</p>
              </div>
              <div class="item-actions">
                <button @click.stop="editarEspecialidad(especialidad)" class="btn-icon" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click.stop="confirmarEliminarEspecialidad(especialidad)" class="btn-icon" title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Subespecialidades Card -->
        <div class="card">
          <div class="card-header">
            <h2>Subespecialidades</h2>
            <button 
              @click="abrirModalSubespecialidad()" 
              class="btn btn-primary"
              :disabled="!especialidadSeleccionada"
            >
              <i class="fas fa-plus"></i> Nueva Subespecialidad
            </button>
          </div>
          
          <div class="search-box">
            <input 
              type="text" 
              v-model="filtroSubespecialidad"
              placeholder="Buscar subespecialidad" 
              class="form-control"
              :disabled="!especialidadSeleccionada"
            >
            <button class="search-btn">
              <i class="fas fa-search"></i>
            </button>
          </div>
          
          <div v-if="!especialidadSeleccionada" class="empty-state">
            <i class="fas fa-hand-point-left"></i>
            <p>Seleccione una especialidad para ver sus subespecialidades</p>
          </div>
          
          <div v-else-if="cargandoSubespecialidades" class="loading-container">
            <div class="spinner"></div>
            <p>Cargando subespecialidades...</p>
          </div>
          
          <div v-else-if="subespecialidades.length === 0" class="empty-state">
            <i class="fas fa-list"></i>
            <p>No hay subespecialidades registradas para esta especialidad</p>
            <button @click="abrirModalSubespecialidad()" class="btn btn-primary">
              Agregar Subespecialidad
            </button>
          </div>
          
          <div v-else class="list-container">
            <div 
              v-for="subespecialidad in subespecialidadesFiltradas" 
              :key="subespecialidad.id"
              class="list-item"
            >
              <div class="item-info">
                <h3>{{ subespecialidad.nombre }}</h3>
                <p v-if="subespecialidad.descripcion">{{ subespecialidad.descripcion }}</p>
              </div>
              <div class="item-actions">
                <button @click="editarSubespecialidad(subespecialidad)" class="btn-icon" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="confirmarEliminarSubespecialidad(subespecialidad)" class="btn-icon" title="Eliminar">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal para crear/editar especialidad -->
      <div v-if="mostrarModalEspecialidad" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ editandoEspecialidad ? 'Editar Especialidad' : 'Nueva Especialidad' }}</h2>
            <button @click="cerrarModalEspecialidad" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="guardarEspecialidad">
              <div class="form-group">
                <label for="nombreEspecialidad">Nombre:</label>
                <input 
                  type="text" 
                  id="nombreEspecialidad" 
                  v-model="especialidadForm.nombre" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="descripcionEspecialidad">Descripción:</label>
                <textarea 
                  id="descripcionEspecialidad" 
                  v-model="especialidadForm.descripcion" 
                  class="form-control"
                  rows="3"
                ></textarea>
              </div>
              
              <div v-if="errorEspecialidad" class="alert alert-danger">
                {{ errorEspecialidad }}
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalEspecialidad" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="guardandoEspecialidad">
                  {{ guardandoEspecialidad ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Modal para crear/editar subespecialidad -->
      <div v-if="mostrarModalSubespecialidad" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ editandoSubespecialidad ? 'Editar Subespecialidad' : 'Nueva Subespecialidad' }}</h2>
            <button @click="cerrarModalSubespecialidad" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="guardarSubespecialidad">
              <div class="form-group">
                <label for="especialidadId">Especialidad:</label>
                <select 
                  id="especialidadId" 
                  v-model="subespecialidadForm.especialidad_id" 
                  required
                  class="form-control"
                >
                  <option value="" disabled>Seleccione una especialidad</option>
                  <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                    {{ esp.nombre }}
                  </option>
                </select>
              </div>
              
              <div class="form-group">
                <label for="nombreSubespecialidad">Nombre:</label>
                <input 
                  type="text" 
                  id="nombreSubespecialidad" 
                  v-model="subespecialidadForm.nombre" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-group">
                <label for="descripcionSubespecialidad">Descripción:</label>
                <textarea 
                  id="descripcionSubespecialidad" 
                  v-model="subespecialidadForm.descripcion" 
                  class="form-control"
                  rows="3"
                ></textarea>
              </div>
              
              <div v-if="errorSubespecialidad" class="alert alert-danger">
                {{ errorSubespecialidad }}
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalSubespecialidad" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="guardandoSubespecialidad">
                  {{ guardandoSubespecialidad ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Modal de confirmación para eliminar -->
      <div v-if="mostrarModalConfirmacion" class="modal-overlay">
        <div class="modal-container modal-sm">
          <div class="modal-header">
            <h2>Confirmar Eliminación</h2>
            <button @click="cancelarEliminacion" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>{{ mensajeConfirmacion }}</p>
            
            <div v-if="errorEliminacion" class="alert alert-danger">
              {{ errorEliminacion }}
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="cancelarEliminacion" class="btn btn-outline">Cancelar</button>
              <button type="button" @click="confirmarEliminacion" class="btn btn-danger" :disabled="eliminando">
                {{ eliminando ? 'Eliminando...' : 'Eliminar' }}
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
    name: 'Especialidades',
    data() {
      return {
        // Especialidades
        especialidades: [],
        especialidadSeleccionada: null,
        filtroEspecialidad: '',
        cargandoEspecialidades: false,
        
        // Subespecialidades
        subespecialidades: [],
        filtroSubespecialidad: '',
        cargandoSubespecialidades: false,
        
        // Modal especialidad
        mostrarModalEspecialidad: false,
        editandoEspecialidad: false,
        guardandoEspecialidad: false,
        errorEspecialidad: null,
        especialidadForm: {
          id: null,
          nombre: '',
          descripcion: ''
        },
        
        // Modal subespecialidad
        mostrarModalSubespecialidad: false,
        editandoSubespecialidad: false,
        guardandoSubespecialidad: false,
        errorSubespecialidad: null,
        subespecialidadForm: {
          id: null,
          nombre: '',
          descripcion: '',
          especialidad_id: ''
        },
        
        // Modal confirmación eliminar
        mostrarModalConfirmacion: false,
        mensajeConfirmacion: '',
        errorEliminacion: null,
        eliminando: false,
        elementoAEliminar: null,
        tipoElemento: null
      }
    },
    computed: {
      especialidadesFiltradas() {
        if (!this.filtroEspecialidad) return this.especialidades;
        
        const filtro = this.filtroEspecialidad.toLowerCase();
        return this.especialidades.filter(esp => 
          esp.nombre.toLowerCase().includes(filtro)
        );
      },
      subespecialidadesFiltradas() {
        if (!this.filtroSubespecialidad) return this.subespecialidades;
        
        const filtro = this.filtroSubespecialidad.toLowerCase();
        return this.subespecialidades.filter(sub => 
          sub.nombre.toLowerCase().includes(filtro)
        );
      }
    },
    mounted() {
      this.cargarEspecialidades();
    },
    methods: {
      // ========== MÉTODOS PARA ESPECIALIDADES ==========
      async cargarEspecialidades() {
        this.cargandoEspecialidades = true;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/lgm/api/doctores/especialidades_crud.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.especialidades = response.data;
        } catch (error) {
          console.error('Error al cargar especialidades:', error);
        } finally {
          this.cargandoEspecialidades = false;
        }
      },
      
      seleccionarEspecialidad(especialidad) {
        this.especialidadSeleccionada = especialidad;
        this.cargarSubespecialidades(especialidad.id);
      },
      
      abrirModalEspecialidad() {
        this.especialidadForm = {
          id: null,
          nombre: '',
          descripcion: ''
        };
        this.editandoEspecialidad = false;
        this.errorEspecialidad = null;
        this.mostrarModalEspecialidad = true;
      },
      
      editarEspecialidad(especialidad) {
        this.especialidadForm = {
          id: especialidad.id,
          nombre: especialidad.nombre,
          descripcion: especialidad.descripcion || ''
        };
        this.editandoEspecialidad = true;
        this.errorEspecialidad = null;
        this.mostrarModalEspecialidad = true;
      },
      
      cerrarModalEspecialidad() {
        this.mostrarModalEspecialidad = false;
      },
      
      async guardarEspecialidad() {
        if (!this.especialidadForm.nombre) {
          this.errorEspecialidad = "El nombre de la especialidad es requerido.";
          return;
        }
        
        this.guardandoEspecialidad = true;
        this.errorEspecialidad = null;
        
        try {
          const token = localStorage.getItem('token');
          
          if (this.editandoEspecialidad) {
            // Actualizar especialidad existente
            const response = await axios.put(`/lgm/api/doctores/especialidades_crud.php?id=${this.especialidadForm.id}`, 
              this.especialidadForm, 
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
            
            if (response.data && response.data.message) {
              this.cerrarModalEspecialidad();
              await this.cargarEspecialidades();
              
              // Si la especialidad editada es la seleccionada, actualizar la selección
              if (this.especialidadSeleccionada && this.especialidadSeleccionada.id === this.especialidadForm.id) {
                const especialidadActualizada = this.especialidades.find(e => e.id === this.especialidadForm.id);
                if (especialidadActualizada) {
                  this.especialidadSeleccionada = especialidadActualizada;
                }
              }
            }
          } else {
            // Crear nueva especialidad
            const response = await axios.post('/lgm/api/doctores/especialidades_crud.php', 
              this.especialidadForm, 
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
            
            if (response.data && response.data.id) {
              this.cerrarModalEspecialidad();
              await this.cargarEspecialidades();
            }
          }
        } catch (error) {
          console.error('Error al guardar especialidad:', error);
          this.errorEspecialidad = error.response?.data?.error || "Error al guardar especialidad. Por favor, intente nuevamente.";
        } finally {
          this.guardandoEspecialidad = false;
        }
      },
      
      confirmarEliminarEspecialidad(especialidad) {
        this.elementoAEliminar = especialidad;
        this.tipoElemento = 'especialidad';
        this.mensajeConfirmacion = `¿Está seguro que desea eliminar la especialidad "${especialidad.nombre}"?`;
        this.errorEliminacion = null;
        this.mostrarModalConfirmacion = true;
      },
      
      // ========== MÉTODOS PARA SUBESPECIALIDADES ==========
      async cargarSubespecialidades(especialidadId) {
        this.cargandoSubespecialidades = true;
        this.subespecialidades = [];
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`/lgm/api/doctores/get_subespecialidades.php?especialidad_id=${especialidadId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.subespecialidades = response.data;
        } catch (error) {
          console.error('Error al cargar subespecialidades:', error);
        } finally {
          this.cargandoSubespecialidades = false;
        }
      },
      
      abrirModalSubespecialidad() {
        if (!this.especialidadSeleccionada) return;
        
        this.subespecialidadForm = {
          id: null,
          nombre: '',
          descripcion: '',
          especialidad_id: this.especialidadSeleccionada.id
        };
        this.editandoSubespecialidad = false;
        this.errorSubespecialidad = null;
        this.mostrarModalSubespecialidad = true;
      },
      
      editarSubespecialidad(subespecialidad) {
        this.subespecialidadForm = {
          id: subespecialidad.id,
          nombre: subespecialidad.nombre,
          descripcion: subespecialidad.descripcion || '',
          especialidad_id: subespecialidad.especialidad_id
        };
        this.editandoSubespecialidad = true;
        this.errorSubespecialidad = null;
        this.mostrarModalSubespecialidad = true;
      },
      
      cerrarModalSubespecialidad() {
        this.mostrarModalSubespecialidad = false;
      },
      
      async guardarSubespecialidad() {
        if (!this.subespecialidadForm.nombre || !this.subespecialidadForm.especialidad_id) {
          this.errorSubespecialidad = "El nombre y la especialidad son requeridos.";
          return;
        }
        
        this.guardandoSubespecialidad = true;
        this.errorSubespecialidad = null;
        
        try {
          const token = localStorage.getItem('token');
          
          if (this.editandoSubespecialidad) {
            // Actualizar subespecialidad existente
            const response = await axios.put(`/lgm/api/doctores/subespecialidades_crud.php?id=${this.subespecialidadForm.id}`, 
              this.subespecialidadForm, 
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
            
            if (response.data && response.data.message) {
              this.cerrarModalSubespecialidad();
              
              // Si la especialidad de la subespecialidad es diferente a la seleccionada, recargar
              if (this.subespecialidadForm.especialidad_id !== this.especialidadSeleccionada.id) {
                const especialidadNueva = this.especialidades.find(e => e.id === this.subespecialidadForm.especialidad_id);
                if (especialidadNueva) {
                  this.seleccionarEspecialidad(especialidadNueva);
                }
              } else {
                this.cargarSubespecialidades(this.especialidadSeleccionada.id);
              }
            }
          } else {
            // Crear nueva subespecialidad
            const response = await axios.post('/lgm/api/doctores/subespecialidades_crud.php', 
              this.subespecialidadForm, 
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
            
            if (response.data && response.data.id) {
              this.cerrarModalSubespecialidad();
              this.cargarSubespecialidades(this.especialidadSeleccionada.id);
            }
          }
        } catch (error) {
          console.error('Error al guardar subespecialidad:', error);
          this.errorSubespecialidad = error.response?.data?.error || "Error al guardar subespecialidad. Por favor, intente nuevamente.";
        } finally {
          this.guardandoSubespecialidad = false;
        }
      },
      
      confirmarEliminarSubespecialidad(subespecialidad) {
        this.elementoAEliminar = subespecialidad;
        this.tipoElemento = 'subespecialidad';
        this.mensajeConfirmacion = `¿Está seguro que desea eliminar la subespecialidad "${subespecialidad.nombre}"?`;
        this.errorEliminacion = null;
        this.mostrarModalConfirmacion = true;
      },
      
      // ========== MÉTODOS PARA CONFIRMACIÓN DE ELIMINACIÓN ==========
      cancelarEliminacion() {
        this.mostrarModalConfirmacion = false;
        this.elementoAEliminar = null;
        this.tipoElemento = null;
      },
      
      async confirmarEliminacion() {
        if (!this.elementoAEliminar || !this.tipoElemento) {
          this.cancelarEliminacion();
          return;
        }
        
        this.eliminando = true;
        this.errorEliminacion = null;
        
        try {
          const token = localStorage.getItem('token');
          
          if (this.tipoElemento === 'especialidad') {
            // Eliminar especialidad
            const response = await axios.delete(`/lgm/api/doctores/especialidades_crud.php?id=${this.elementoAEliminar.id}`, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            
            if (response.data && response.data.message) {
              this.mostrarModalConfirmacion = false;
              
              // Si la especialidad eliminada es la seleccionada, limpiar selección
              if (this.especialidadSeleccionada && this.especialidadSeleccionada.id === this.elementoAEliminar.id) {
                this.especialidadSeleccionada = null;
                this.subespecialidades = [];
              }
              
              await this.cargarEspecialidades();
            }
          } else if (this.tipoElemento === 'subespecialidad') {
            // Eliminar subespecialidad
            const response = await axios.delete(`/lgm/api/doctores/subespecialidades_crud.php?id=${this.elementoAEliminar.id}`, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            
            if (response.data && response.data.message) {
              this.mostrarModalConfirmacion = false;
              await this.cargarSubespecialidades(this.especialidadSeleccionada.id);
            }
          }
        } catch (error) {
          console.error(`Error al eliminar ${this.tipoElemento}:`, error);
          this.errorEliminacion = error.response?.data?.error || `Error al eliminar ${this.tipoElemento}. Por favor, intente nuevamente.`;
        } finally {
          this.eliminando = false;
        }
      }
    }
  }
  </script>
  
  <style scoped>
  .especialidades-container {
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
    height: 600px;
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
  
  .search-box {
    display: flex;
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
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
  
  .list-container {
    flex: 1;
    overflow-y: auto;
    padding: 0;
  }
  
  .list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .list-item:hover {
    background-color: #f8f9fa;
  }
  
  .list-item.active {
    background-color: #e9ecef;
  }
  
  .item-info {
    flex: 1;
  }
  
  .item-info h3 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 500;
  }
  
  .item-info p {
    margin: 0;
    font-size: 14px;
    color: #6c757d;
  }
  
  .item-actions {
    display: flex;
    gap: 10px;
  }
  
  .btn-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: transparent;
    border: 1px solid #ced4da;
    border-radius: 4px;
    color: var(--secondary-color);
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-icon:hover {
    background-color: #e9ecef;
    color: var(--primary-color);
  }
  
  .loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 20px;
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
  
  .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 20px;
    text-align: center;
  }
  
  .empty-state i {
    font-size: 48px;
    color: var(--secondary-color);
    margin-bottom: 15px;
  }
  
  .empty-state p {
    margin-bottom: 20px;
    color: var(--secondary-color);
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
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }
  
  .modal-container.modal-sm {
    max-width: 400px;
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
    font-size: 20px;
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
  
  .btn {
    padding: 10px 15px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-primary {
    background-color: var(--primary-color);
    border: 1px solid var(--primary-color);
    color: white;
  }
  
  .btn-danger {
    background-color: #dc3545;
    border: 1px solid #dc3545;
    color: white;
  }
  
  .btn-outline {
    background-color: transparent;
    border: 1px solid #ced4da;
    color: var(--dark-color);
  }
  
  .btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  /* Responsive */
  @media (max-width: 992px) {
    .cards-container {
      grid-template-columns: 1fr;
    }
    
    .card {
      height: 500px;
    }
  }
  </style>