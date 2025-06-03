<template>
    <div class="gestion-titulares-container">
      <div class="header-section">
        <h1>Gestión de Titulares</h1>
        <button @click="abrirModalNuevo" class="btn btn-primary">
          <i class="fas fa-plus"></i> Nuevo Titular
        </button>
      </div>
      
      <div class="filters-section">
        <div class="search-box">
          <input 
            type="text" 
            v-model="filtros.busqueda" 
            placeholder="Buscar por nombre, cédula o número de afiliado" 
            @input="aplicarFiltros"
            class="form-control"
          >
          <button class="search-btn">
            <i class="fas fa-search"></i>
          </button>
        </div>
        
        <div class="filter-options">
          <div class="filter-item">
            <label>Estado:</label>
            <select v-model="filtros.estado" @change="aplicarFiltros" class="form-control">
              <option value="">Todos</option>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
              <option value="Suspendido">Suspendido</option>
            </select>
          </div>
          
          <button @click="resetearFiltros" class="btn btn-outline">
            <i class="fas fa-sync"></i> Resetear
          </button>
        </div>
      </div>
      
      <div v-if="cargando" class="loading-container">
        <div class="spinner"></div>
        <p>Cargando titulares...</p>
      </div>
      
      <div v-else-if="titulares.length === 0" class="empty-state">
        <i class="fas fa-users"></i>
        <p>No se encontraron titulares que coincidan con los criterios de búsqueda.</p>
        <button @click="abrirModalNuevo" class="btn btn-primary">
          Agregar Nuevo Titular
        </button>
      </div>
      
      <div v-else>
        <div class="table-responsive">
          <table class="titulares-table">
            <thead>
              <tr>
                <th @click="ordenarPor('nombre')" class="sortable">
                  Nombre Completo 
                  <i v-if="ordenamiento.campo === 'nombre'" 
                     :class="['fas', ordenamiento.ascendente ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="ordenarPor('cedula')" class="sortable">
                  Cédula 
                  <i v-if="ordenamiento.campo === 'cedula'" 
                     :class="['fas', ordenamiento.ascendente ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th @click="ordenarPor('numero_afiliado')" class="sortable">
                  Número de Afiliado 
                  <i v-if="ordenamiento.campo === 'numero_afiliado'" 
                     :class="['fas', ordenamiento.ascendente ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th>Contacto</th>
                <th @click="ordenarPor('estado')" class="sortable">
                  Estado 
                  <i v-if="ordenamiento.campo === 'estado'" 
                     :class="['fas', ordenamiento.ascendente ? 'fa-sort-up' : 'fa-sort-down']"></i>
                </th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="titular in titularesFiltrados" :key="titular.id">
                <td>{{ titular.nombre }} {{ titular.apellido }}</td>
                <td>{{ titular.cedula }}</td>
                <td>{{ titular.numero_afiliado }}</td>
                <td>
                  <div class="contacto-info">
                    <span v-if="titular.telefono">
                      <i class="fas fa-phone"></i> {{ titular.telefono }}
                    </span>
                    <span v-if="titular.email">
                      <i class="fas fa-envelope"></i> {{ titular.email }}
                    </span>
                  </div>
                </td>
                <td>
                  <span :class="['estado-badge', `estado-${titular.estado.toLowerCase()}`]">
                    {{ titular.estado }}
                  </span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button @click="verDetalles(titular)" class="btn-icon" title="Ver detalles">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button @click="editarTitular(titular)" class="btn-icon" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click="abrirModalCambiarEstado(titular)" class="btn-icon" title="Cambiar estado">
                      <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button @click="abrirModalAgregarPaciente(titular)" class="btn-icon" title="Agregar beneficiario">
                      <i class="fas fa-user-plus"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="pagination">
          <button 
            @click="cambiarPagina(paginaActual - 1)" 
            :disabled="paginaActual === 1"
            class="btn-page"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          
          <span class="pagination-info">
            Página {{ paginaActual }} de {{ totalPaginas }}
          </span>
          
          <button 
            @click="cambiarPagina(paginaActual + 1)" 
            :disabled="paginaActual >= totalPaginas"
            class="btn-page"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
      
      <!-- Modal para nuevo o editar titular -->
      <div v-if="mostrarModalTitular" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ editandoTitular ? 'Editar Titular' : 'Nuevo Titular' }}</h2>
            <button @click="cerrarModalTitular" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="guardarTitular">
              <div class="form-row">
                <div class="form-group">
                  <label for="nombre">Nombre:</label>
                  <input 
                    type="text" 
                    id="nombre" 
                    v-model="titularForm.nombre" 
                    required
                    class="form-control"
                  >
                </div>
                
                <div class="form-group">
                  <label for="apellido">Apellido:</label>
                  <input 
                    type="text" 
                    id="apellido" 
                    v-model="titularForm.apellido" 
                    required
                    class="form-control"
                  >
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="cedula">Cédula de Identidad:</label>
                  <input 
                    type="text" 
                    id="cedula" 
                    v-model="titularForm.cedula" 
                    required
                    class="form-control"
                    :disabled="editandoTitular"
                  >
                </div>
                
                <div class="form-group">
                  <label for="numero_afiliado">Número de Afiliado:</label>
                  <input 
                    type="text" 
                    id="numero_afiliado" 
                    v-model="titularForm.numero_afiliado" 
                    required
                    class="form-control"
                  >
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="telefono">Teléfono:</label>
                  <input 
                    type="tel" 
                    id="telefono" 
                    v-model="titularForm.telefono" 
                    required
                    class="form-control"
                  >
                </div>
                
                <div class="form-group">
                  <label for="email">Email:</label>
                  <input 
                    type="email" 
                    id="email" 
                    v-model="titularForm.email" 
                    class="form-control"
                  >
                </div>
              </div>
              
              <div class="form-group">
                <label for="estado">Estado:</label>
                <select 
                  id="estado" 
                  v-model="titularForm.estado" 
                  required
                  class="form-control"
                >
                  <option value="Activo">Activo</option>
                  <option value="Inactivo">Inactivo</option>
                  <option value="Suspendido">Suspendido</option>
                </select>
              </div>
              
              <div class="form-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="titularForm.es_paciente">
                  El titular también es paciente
                </label>
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalTitular" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="guardando">
                  {{ guardando ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Modal para cambiar estado -->
      <div v-if="mostrarModalEstado" class="modal-overlay">
        <div class="modal-container modal-sm">
          <div class="modal-header">
            <h2>Cambiar Estado</h2>
            <button @click="cerrarModalEstado" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>Cambiar el estado de <strong>{{ titularSeleccionado.nombre }} {{ titularSeleccionado.apellido }}</strong>:</p>
            
            <div class="form-group">
              <select v-model="nuevoEstado" class="form-control">
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
                <option value="Suspendido">Suspendido</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="motivo">Motivo del cambio:</label>
              <textarea id="motivo" v-model="motivoCambio" rows="3" class="form-control"></textarea>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalEstado" class="btn btn-outline">Cancelar</button>
            <button @click="cambiarEstado" class="btn btn-primary" :disabled="cambiandoEstado">
              {{ cambiandoEstado ? 'Procesando...' : 'Confirmar Cambio' }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Modal para agregar beneficiario -->
      <div v-if="mostrarModalBeneficiario" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Agregar Beneficiario</h2>
            <button @click="cerrarModalBeneficiario" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <p>Agregar beneficiario para el titular: <strong>{{ titularSeleccionado.nombre }} {{ titularSeleccionado.apellido }}</strong></p>
            
            <form @submit.prevent="guardarBeneficiario">
              <div class="form-row">
                <div class="form-group">
                  <label for="paciente_nombre">Nombre:</label>
                  <input 
                    type="text" 
                    id="paciente_nombre" 
                    v-model="beneficiarioForm.nombre" 
                    required
                    class="form-control"
                  >
                </div>
                
                <div class="form-group">
                  <label for="paciente_apellido">Apellido:</label>
                  <input 
                    type="text" 
                    id="paciente_apellido" 
                    v-model="beneficiarioForm.apellido" 
                    required
                    class="form-control"
                  >
                </div>
              </div>
              
              <div class="form-group">
                <label for="paciente_cedula">Cédula de Identidad:</label>
                <input 
                  type="text" 
                  id="paciente_cedula" 
                  v-model="beneficiarioForm.cedula" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="paciente_telefono">Teléfono:</label>
                  <input 
                    type="tel" 
                    id="paciente_telefono" 
                    v-model="beneficiarioForm.telefono" 
                    class="form-control"
                  >
                </div>
                
                <div class="form-group">
                  <label for="paciente_email">Email:</label>
                  <input 
                    type="email" 
                    id="paciente_email" 
                    v-model="beneficiarioForm.email" 
                    class="form-control"
                  >
                </div>
              </div>
              
              <div class="form-group">
                <label for="parentesco">Parentesco:</label>
                <select id="parentesco" v-model="beneficiarioForm.parentesco" required class="form-control">
                  <option value="Cónyuge">Cónyuge</option>
                  <option value="Hijo/a">Hijo/a</option>
                  <option value="Padre/Madre">Padre/Madre</option>
                  <option value="Hermano/a">Hermano/a</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
              
              <div class="form-group" v-if="beneficiarioForm.parentesco === 'Otro'">
                <label for="otro_parentesco">Especificar parentesco:</label>
                <input 
                  type="text" 
                  id="otro_parentesco" 
                  v-model="beneficiarioForm.otro_parentesco" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalBeneficiario" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="guardandoBeneficiario">
                  {{ guardandoBeneficiario ? 'Guardando...' : 'Guardar Beneficiario' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Modal de detalles -->
      <div v-if="mostrarModalDetalles" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Detalles del Titular</h2>
            <button @click="cerrarModalDetalles" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="titular-info">
              <h3>{{ titularSeleccionado.nombre }} {{ titularSeleccionado.apellido }}</h3>
              
              <div class="info-section">
                <div class="info-row">
                  <div class="info-label">Cédula:</div>
                  <div class="info-value">{{ titularSeleccionado.cedula }}</div>
                </div>
                
                <div class="info-row">
                  <div class="info-label">Número de Afiliado:</div>
                  <div class="info-value">{{ titularSeleccionado.numero_afiliado }}</div>
                </div>
                
                <div class="info-row">
                  <div class="info-label">Teléfono:</div>
                  <div class="info-value">{{ titularSeleccionado.telefono }}</div>
                </div>
                
                <div class="info-row">
                  <div class="info-label">Email:</div>
                  <div class="info-value">{{ titularSeleccionado.email || 'No especificado' }}</div>
                </div>
                
                <div class="info-row">
                  <div class="info-label">Estado:</div>
                  <div class="info-value">
                    <span :class="['estado-badge', `estado-${titularSeleccionado.estado.toLowerCase()}`]">
                      {{ titularSeleccionado.estado }}
                    </span>
                  </div>
                </div>
                
                <div class="info-row">
                  <div class="info-label">Es Paciente:</div>
                  <div class="info-value">{{ titularSeleccionado.es_paciente ? 'Sí' : 'No' }}</div>
                </div>
              </div>
            </div>
            
            <div class="beneficiarios-seccion">
              <h3>Beneficiarios</h3>
              
              <div v-if="cargandoBeneficiarios" class="loading-mini">
                <div class="spinner-mini"></div>
                <span>Cargando beneficiarios...</span>
              </div>
              
              <div v-else-if="beneficiarios.length === 0" class="empty-beneficiarios">
                <p>No se encontraron beneficiarios para este titular.</p>
                <button @click="abrirModalAgregarPaciente(titularSeleccionado)" class="btn btn-sm btn-primary">
                  <i class="fas fa-plus"></i> Agregar Beneficiario
                </button>
              </div>
              
              <div v-else class="beneficiarios-list">
                <div v-for="beneficiario in beneficiarios" :key="beneficiario.id" class="beneficiario-card">
                  <div class="beneficiario-info">
                    <h4>{{ beneficiario.nombre }} {{ beneficiario.apellido }}</h4>
                    <p class="parentesco">{{ beneficiario.parentesco }}</p>
                    <div class="beneficiario-details">
                      <p><i class="fas fa-id-card"></i> {{ beneficiario.cedula }}</p>
                      <p v-if="beneficiario.telefono"><i class="fas fa-phone"></i> {{ beneficiario.telefono }}</p>
                    </div>
                  </div>
                  <div class="beneficiario-actions">
                    <button @click="editarBeneficiario(beneficiario)" class="btn-icon" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalDetalles" class="btn btn-primary">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'GestionTitulares',
    data() {
      return {
        titulares: [],
        filtros: {
          busqueda: '',
          estado: ''
        },
        ordenamiento: {
          campo: 'nombre',
          ascendente: true
        },
        paginaActual: 1,
        itemsPorPagina: 10,
        cargando: false,
        
        // Modal nuevo/editar titular
        mostrarModalTitular: false,
        titularForm: {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          numero_afiliado: '',
          estado: 'Activo',
          es_paciente: false
        },
        editandoTitular: false,
        guardando: false,
        
        // Modal cambiar estado
        mostrarModalEstado: false,
        titularSeleccionado: {},
        nuevoEstado: '',
        motivoCambio: '',
        cambiandoEstado: false,
        
        // Modal agregar beneficiario
        mostrarModalBeneficiario: false,
        beneficiarioForm: {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          parentesco: '',
          otro_parentesco: ''
        },
        guardandoBeneficiario: false,
        
        // Modal detalles
        mostrarModalDetalles: false,
        beneficiarios: [],
        cargandoBeneficiarios: false
      };
    },
    computed: {
      titularesFiltrados() {
        let resultado = [...this.titulares];
        
        // Aplicar filtros
        if (this.filtros.busqueda) {
          const busqueda = this.filtros.busqueda.toLowerCase();
          resultado = resultado.filter(titular => 
            `${titular.nombre} ${titular.apellido}`.toLowerCase().includes(busqueda) ||
            titular.cedula.toLowerCase().includes(busqueda) ||
            titular.numero_afiliado.toLowerCase().includes(busqueda)
          );
        }
        
        if (this.filtros.estado) {
          resultado = resultado.filter(titular => 
            titular.estado === this.filtros.estado
          );
        }
        
        // Aplicar ordenamiento
        resultado.sort((a, b) => {
          let valorA, valorB;
          
          if (this.ordenamiento.campo === 'nombre') {
            valorA = `${a.nombre} ${a.apellido}`.toLowerCase();
            valorB = `${b.nombre} ${b.apellido}`.toLowerCase();
          } else {
            valorA = a[this.ordenamiento.campo].toLowerCase();
            valorB = b[this.ordenamiento.campo].toLowerCase();
          }
          
          if (this.ordenamiento.ascendente) {
            return valorA.localeCompare(valorB);
          } else {
            return valorB.localeCompare(valorA);
          }
        });
        
        return resultado;
      },
      titularesPaginados() {
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        const fin = inicio + this.itemsPorPagina;
        return this.titularesFiltrados.slice(inicio, fin);
      },
      totalPaginas() {
        return Math.ceil(this.titularesFiltrados.length / this.itemsPorPagina);
      }
    },
    mounted() {
      this.cargarTitulares();
    },
    methods: {
      async cargarTitulares() {
        this.cargando = true;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/titulares/listar.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.titulares = response.data;
        } catch (error) {
          console.error('Error al cargar titulares:', error);
          alert('Error al cargar la lista de titulares. Por favor, intente nuevamente.');
        } finally {
          this.cargando = false;
        }
      },
      
      aplicarFiltros() {
        this.paginaActual = 1; // Volver a la primera página al filtrar
      },
      
      resetearFiltros() {
        this.filtros = {
          busqueda: '',
          estado: ''
        };
        this.paginaActual = 1;
      },
      
      ordenarPor(campo) {
        if (this.ordenamiento.campo === campo) {
          this.ordenamiento.ascendente = !this.ordenamiento.ascendente;
        } else {
          this.ordenamiento.campo = campo;
          this.ordenamiento.ascendente = true;
        }
      },
      
      cambiarPagina(pagina) {
        if (pagina > 0 && pagina <= this.totalPaginas) {
          this.paginaActual = pagina;
        }
      },
      
      // Modal nuevo titular
      abrirModalNuevo() {
        this.titularForm = {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          numero_afiliado: '',
          estado: 'Activo',
          es_paciente: false
        };
        this.editandoTitular = false;
        this.mostrarModalTitular = true;
      },
      
      // Modal editar titular
      editarTitular(titular) {
        this.titularForm = {
          id: titular.id,
          nombre: titular.nombre,
          apellido: titular.apellido,
          cedula: titular.cedula,
          telefono: titular.telefono,
          email: titular.email || '',
          numero_afiliado: titular.numero_afiliado,
          estado: titular.estado,
          es_paciente: titular.es_paciente
        };
        this.editandoTitular = true;
        this.mostrarModalTitular = true;
      },
      
      cerrarModalTitular() {
        this.mostrarModalTitular = false;
      },
      
      async guardarTitular() {
        if (!this.titularForm.nombre || !this.titularForm.apellido || !this.titularForm.cedula || 
            !this.titularForm.telefono || !this.titularForm.numero_afiliado) {
          alert('Por favor, complete todos los campos obligatorios.');
          return;
        }
        
        this.guardando = true;
        
        try {
          const token = localStorage.getItem('token');
          let response;
          
          if (this.editandoTitular) {
            response = await axios.put('/api/titulares/actualizar.php', this.titularForm, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
          } else {
            response = await axios.post('/api/titulares/crear.php', this.titularForm, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
          }
          
          if (response.data && (response.data.id || response.data.message)) {
            await this.cargarTitulares();
            this.cerrarModalTitular();
            alert(this.editandoTitular ? 'Titular actualizado correctamente.' : 'Titular creado correctamente.');
          }
        } catch (error) {
          console.error('Error al guardar titular:', error);
          
          if (error.response && error.response.data && error.response.data.error) {
            alert(error.response.data.error);
          } else {
            alert('Error al guardar el titular. Por favor, intente nuevamente.');
          }
        } finally {
          this.guardando = false;
        }
      },
      
      // Modal cambiar estado
      abrirModalCambiarEstado(titular) {
        this.titularSeleccionado = {...titular};
        this.nuevoEstado = titular.estado;
        this.motivoCambio = '';
        this.mostrarModalEstado = true;
      },
      
      cerrarModalEstado() {
        this.mostrarModalEstado = false;
        this.titularSeleccionado = {};
        this.nuevoEstado = '';
        this.motivoCambio = '';
      },
      
      async cambiarEstado() {
        if (!this.motivoCambio.trim()) {
          alert('Por favor, ingrese el motivo del cambio de estado.');
          return;
        }
        
        this.cambiandoEstado = true;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.put('/api/titulares/cambiar-estado.php', {
            id: this.titularSeleccionado.id,
            estado: this.nuevoEstado,
            motivo: this.motivoCambio
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          if (response.data && response.data.message) {
            await this.cargarTitulares();
            this.cerrarModalEstado();
            alert('Estado del titular actualizado correctamente.');
          }
        } catch (error) {
          console.error('Error al cambiar estado del titular:', error);
          alert('Error al cambiar el estado del titular. Por favor, intente nuevamente.');
        } finally {
          this.cambiandoEstado = false;
        }
      },
      
      // Modal agregar beneficiario
      abrirModalAgregarPaciente(titular) {
        this.titularSeleccionado = {...titular};
        this.beneficiarioForm = {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          parentesco: '',
          otro_parentesco: ''
        };
        this.mostrarModalBeneficiario = true;
      },
      
      cerrarModalBeneficiario() {
        this.mostrarModalBeneficiario = false;
        this.titularSeleccionado = {};
        this.beneficiarioForm = {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          parentesco: '',
          otro_parentesco: ''
        };
      },
        
        async guardarBeneficiario() {
  if (!this.beneficiarioForm.nombre || !this.beneficiarioForm.apellido || 
      !this.beneficiarioForm.cedula || !this.beneficiarioForm.parentesco ||
      (this.beneficiarioForm.parentesco === 'Otro' && !this.beneficiarioForm.otro_parentesco)) {
    alert('Por favor, complete todos los campos obligatorios.');
    return;
  }
  
  this.guardandoBeneficiario = true;
  
  try {
    const token = localStorage.getItem('token');
    
    const payload = {
      ...this.beneficiarioForm,
      titular_id: this.titularSeleccionado.id,
      parentesco: this.beneficiarioForm.parentesco === 'Otro' 
                  ? this.beneficiarioForm.otro_parentesco 
                  : this.beneficiarioForm.parentesco,
      tipo: 'asegurado'
    };
    
    const response = await axios.post('/api/pacientes/crear.php', payload, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (response.data && response.data.id) {
      this.cerrarModalBeneficiario();
      alert('Beneficiario agregado correctamente.');
      
      // Si el modal de detalles está abierto, actualizar la lista de beneficiarios
      if (this.mostrarModalDetalles) {
        this.cargarBeneficiarios(this.titularSeleccionado.id);
      }
    }
  } catch (error) {
    console.error('Error al guardar beneficiario:', error);
    
    if (error.response && error.response.data && error.response.data.error) {
      alert(error.response.data.error);
    } else {
      alert('Error al agregar el beneficiario. Por favor, intente nuevamente.');
    }
  } finally {
    this.guardandoBeneficiario = false;
  }
},

// Modal detalles
async verDetalles(titular) {
  this.titularSeleccionado = {...titular};
  this.mostrarModalDetalles = true;
  await this.cargarBeneficiarios(titular.id);
},

cerrarModalDetalles() {
  this.mostrarModalDetalles = false;
  this.titularSeleccionado = {};
  this.beneficiarios = [];
},

async cargarBeneficiarios(titularId) {
  this.cargandoBeneficiarios = true;
  
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get(`/api/pacientes/listar-por-titular.php?titular_id=${titularId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    this.beneficiarios = response.data;
  } catch (error) {
    console.error('Error al cargar beneficiarios:', error);
  } finally {
    this.cargandoBeneficiarios = false;
  }
},

editarBeneficiario(beneficiario) {
  // Implementar la edición de beneficiarios si es necesario
  alert('Funcionalidad de editar beneficiario aún no implementada.');
}
}
};
</script>

<style scoped>
.gestion-titulares-container {
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

.titulares-table {
  width: 100%;
  border-collapse: collapse;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.titulares-table th,
.titulares-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.titulares-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: var(--dark-color);
}

.titulares-table th.sortable {
  cursor: pointer;
}

.titulares-table th.sortable:hover {
  background-color: #e9ecef;
}

.titulares-table tr:hover {
  background-color: #f8f9fa;
}

.contacto-info {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.contacto-info span {
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--secondary-color);
}

.estado-badge {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.estado-activo {
  background-color: #d4edda;
  color: #155724;
}

.estado-inactivo {
  background-color: #f8d7da;
  color: #721c24;
}

.estado-suspendido {
  background-color: #fff3cd;
  color: #856404;
}

.action-buttons {
  display: flex;
  gap: 5px;
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

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 20px;
  gap: 15px;
}

.btn-page {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: white;
  border: 1px solid #ced4da;
  border-radius: 4px;
  color: var(--dark-color);
  cursor: pointer;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 14px;
  color: var(--secondary-color);
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
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

.empty-state {
  text-align: center;
  padding: 50px 0;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

/* Estilos para modales */
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

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-group {
  flex: 1;
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

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
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

.btn-sm {
  padding: 5px 10px;
  font-size: 14px;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Estilos para el modal de detalles */
.titular-info h3 {
  margin-top: 0;
  margin-bottom: 20px;
  color: var(--dark-color);
}

.info-section {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 20px;
}

.info-row {
  display: flex;
  margin-bottom: 10px;
}

.info-label {
  width: 150px;
  font-weight: 500;
  color: var(--dark-color);
}

.beneficiarios-seccion {
  margin-top: 30px;
}

.beneficiarios-seccion h3 {
  margin-bottom: 15px;
  color: var(--dark-color);
}

.loading-mini {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px 0;
  color: var(--secondary-color);
}

.spinner-mini {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.empty-beneficiarios {
  padding: 20px;
  text-align: center;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.beneficiarios-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
}

.beneficiario-card {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
  display: flex;
  justify-content: space-between;
}

.beneficiario-info h4 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.parentesco {
  color: var(--primary-color);
  font-size: 14px;
  margin-bottom: 10px;
}

.beneficiario-details {
  display: flex;
  flex-direction: column;
  gap: 5px;
  font-size: 14px;
  color: var(--secondary-color);
}

.beneficiario-details p {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 5px;
}

.table-responsive {
  overflow-x: auto;
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  
  .header-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .filter-options {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .filter-item {
    width: 100%;
  }
  
  .titulares-table th:nth-child(4),
  .titulares-table td:nth-child(4) {
    display: none;
  }
}
</style>