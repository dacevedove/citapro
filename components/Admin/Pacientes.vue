<template>
  <div class="pacientes-container">
    <h1>Gestión de Pacientes</h1>
    
    <div class="header-section">
      <button class="btn btn-primary" @click="abrirModalCrear">
        <i class="fas fa-plus"></i> Nuevo Paciente
      </button>
    </div>
    
    <div class="filters-section">
      <div class="search-box">
        <input 
          type="text" 
          v-model="filtros.busqueda"
          placeholder="Buscar por nombre o cédula" 
          class="form-control"
          @keyup.enter="cargarPacientes"
        >
        <button class="search-btn" @click="cargarPacientes">
          <i class="fas fa-search"></i>
        </button>
      </div>
      
      <div class="filter-options">
        <div class="filter-item">
          <label>Tipo:</label>
          <select class="form-control" v-model="filtros.tipo" @change="cargarPacientes">
            <option value="">Todos</option>
            <option value="asegurado">Asegurado</option>
            <option value="particular">Particular</option>
          </select>
        </div>
        
        <button class="btn btn-outline" @click="resetearFiltros">
          <i class="fas fa-sync"></i> Resetear
        </button>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="pacientes-table">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Cédula</th>
            <th>Contacto</th>
            <th>Seguros Activos</th>
            <th>Tipo</th>
            <th>Última Cita</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="text-center">Cargando...</td>
          </tr>
          <tr v-else-if="pacientes.length === 0">
            <td colspan="7" class="text-center">No hay pacientes registrados</td>
          </tr>
          <tr v-else v-for="paciente in pacientes" :key="paciente.id">
            <td>{{ paciente.nombre }} {{ paciente.apellido }}</td>
            <td>{{ paciente.cedula }}</td>
            <td>
              {{ paciente.telefono }}<br>
              <span v-if="paciente.email">{{ paciente.email }}</span>
            </td>
            <td>
              <div v-if="paciente.tipo === 'asegurado'" class="seguros-indicator">
                <span class="seguros-count" :class="getSegurosClass(paciente.seguros_activos_count)">
                  {{ paciente.seguros_activos_count || 0 }}
                </span>
                <small>seguros</small>
                <button 
                  v-if="paciente.seguros_activos_count > 0" 
                  class="btn-icon-small" 
                  title="Ver seguros"
                  @click="gestionarSeguros(paciente)"
                >
                  <i class="fas fa-shield-alt"></i>
                </button>
              </div>
              <span v-else class="text-muted">N/A</span>
            </td>
            <td>
              <span v-if="paciente.tipo === 'particular'">Particular</span>
              <span v-else-if="paciente.es_titular === 1">Titular</span>
              <span v-else>Beneficiario</span>
            </td>
            <td>
              <span v-if="paciente.ultima_cita">
                {{ formatearFecha(paciente.ultima_cita.fecha) }} - {{ paciente.ultima_cita.especialidad }}
                <br>
                <span :class="getEstadoClass(paciente.ultima_cita.estado)">{{ paciente.ultima_cita.estado }}</span>
              </span>
              <span v-else>Sin citas</span>
            </td>
            <td>
              <button class="btn-icon" title="Ver detalles" @click="verDetalles(paciente)">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn-icon" title="Editar" @click="editarPaciente(paciente)">
                <i class="fas fa-edit"></i>
              </button>
              <button 
                v-if="paciente.tipo === 'asegurado'" 
                class="btn-icon" 
                title="Gestionar seguros" 
                @click="gestionarSeguros(paciente)"
              >
                <i class="fas fa-shield-alt"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
<!-- Modal para ver detalles del paciente -->
<div class="modal" v-if="showDetallesModal && pacienteSeleccionado">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Detalles del Paciente</h2>
      <button class="close-btn" @click="cerrarModalDetalles">×</button>
    </div>
    <div class="modal-body">
      <div class="detalles-paciente">
        <div class="detalle-grupo">
          <h3>Información Personal</h3>
          <p><strong>Nombre completo:</strong> {{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellido }}</p>
          <p><strong>Cédula:</strong> {{ pacienteSeleccionado.cedula }}</p>
          <p><strong>Teléfono:</strong> {{ pacienteSeleccionado.telefono }}</p>
          <p><strong>Email:</strong> {{ pacienteSeleccionado.email || 'No registrado' }}</p>
        </div>
        
        <div class="detalle-grupo">
          <h3>Información de Seguro</h3>
          <p><strong>Tipo:</strong> {{ pacienteSeleccionado.tipo === 'asegurado' ? 'Asegurado' : 'Particular' }}</p>
          <p v-if="pacienteSeleccionado.tipo === 'asegurado'">
            <strong>Condición:</strong> {{ pacienteSeleccionado.es_titular === 1 ? 'Titular' : 'Beneficiario' }}
          </p>
          <p v-if="pacienteSeleccionado.aseguradora_nombre">
            <strong>Aseguradora:</strong> {{ pacienteSeleccionado.aseguradora_nombre }}
          </p>
          <p v-if="pacienteSeleccionado.numero_afiliado">
            <strong>Número de Afiliado:</strong> {{ pacienteSeleccionado.numero_afiliado }}
          </p>
          <p v-if="pacienteSeleccionado.parentesco">
            <strong>Parentesco:</strong> {{ pacienteSeleccionado.parentesco }}
          </p>
        </div>
        
        <div class="detalle-grupo" v-if="pacienteSeleccionado.ultima_cita">
          <h3>Última Cita</h3>
          <p><strong>Fecha:</strong> {{ formatearFecha(pacienteSeleccionado.ultima_cita.fecha) }}</p>
          <p><strong>Especialidad:</strong> {{ pacienteSeleccionado.ultima_cita.especialidad }}</p>
          <p><strong>Estado:</strong> <span :class="getEstadoClass(pacienteSeleccionado.ultima_cita.estado)">{{ pacienteSeleccionado.ultima_cita.estado }}</span></p>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" @click="cerrarModalDetalles">Cerrar</button>
      <button class="btn btn-primary" @click="editarPaciente(pacienteSeleccionado)">Editar</button>
    </div>
  </div>
</div>

<!-- Modal para editar paciente -->
<div class="modal" v-if="showEditarModal && pacienteSeleccionado">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Editar Paciente</h2>
      <button class="close-btn" @click="cerrarModalEditar">×</button>
    </div>
    <div class="modal-body">
      <!-- Formulario similar al de creación pero con los datos cargados -->
      <!-- Si es particular -->
      <div v-if="tipoPaciente === 'particular'">
        <div class="form-group">
          <label for="edit-particular-nombre">Nombre:</label>
          <input type="text" id="edit-particular-nombre" v-model="formData.nombre" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-particular-apellido">Apellido:</label>
          <input type="text" id="edit-particular-apellido" v-model="formData.apellido" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-particular-cedula">Cédula:</label>
          <input type="text" id="edit-particular-cedula" v-model="formData.cedula" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-particular-telefono">Teléfono:</label>
          <input type="text" id="edit-particular-telefono" v-model="formData.telefono" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-particular-email">Email:</label>
          <input type="email" id="edit-particular-email" v-model="formData.email" class="form-control">
        </div>
      </div>
      
      <!-- Si es asegurado -->
      <div v-if="tipoPaciente === 'asegurado'">
        <!-- Campos para beneficiario o titular -->
        <div class="form-group">
          <label for="edit-asegurado-nombre">Nombre:</label>
          <input type="text" id="edit-asegurado-nombre" v-model="formData.nombre" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-asegurado-apellido">Apellido:</label>
          <input type="text" id="edit-asegurado-apellido" v-model="formData.apellido" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-asegurado-cedula">Cédula:</label>
          <input type="text" id="edit-asegurado-cedula" v-model="formData.cedula" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-asegurado-telefono">Teléfono:</label>
          <input type="text" id="edit-asegurado-telefono" v-model="formData.telefono" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="edit-asegurado-email">Email:</label>
          <input type="email" id="edit-asegurado-email" v-model="formData.email" class="form-control">
        </div>
        
        <!-- Si es titular, mostrar selector de aseguradora -->
        <div v-if="pacienteSeleccionado.es_titular" class="form-group">
          <label for="edit-aseguradora">Aseguradora:</label>
          <select id="edit-aseguradora" v-model="formData.aseguradora_id" class="form-control" required>
            <option value="">Seleccione una aseguradora</option>
            <option v-for="aseguradora in aseguradoras" :key="aseguradora.id" :value="aseguradora.id">
              {{ aseguradora.nombre_comercial }}
            </option>
          </select>
        </div>
        
        <!-- Si es beneficiario, mostrar selección de parentesco -->
        <div v-if="!pacienteSeleccionado.es_titular" class="form-group">
          <label for="edit-parentesco">Parentesco:</label>
          <select id="edit-parentesco" v-model="formData.parentesco" class="form-control">
            <option value="conyuge">Cónyuge</option>
            <option value="hijo">Hijo/a</option>
            <option value="padre">Padre</option>
            <option value="madre">Madre</option>
            <option value="otro">Otro</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" @click="cerrarModalEditar">Cancelar</button>
      <button class="btn btn-primary" @click="actualizarPaciente" :disabled="loading">Guardar Cambios</button>
    </div>
  </div>
</div>
    <!-- Modal para crear paciente -->
    <div class="modal" v-if="showCrearModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>{{ formTitulo }}</h2>
          <button class="close-btn" @click="cerrarModal">×</button>
        </div>
        <div class="modal-body">
          <div class="tabs">
            <button 
              :class="['tab-btn', tipoPaciente === 'asegurado' ? 'active' : '']" 
              @click="tipoPaciente = 'asegurado'"
            >
              Paciente Asegurado
            </button>
            <button 
              :class="['tab-btn', tipoPaciente === 'particular' ? 'active' : '']" 
              @click="tipoPaciente = 'particular'"
            >
              Paciente Particular
            </button>
          </div>

          <!-- Formulario para paciente asegurado -->
          <div v-if="tipoPaciente === 'asegurado'">
            <div class="info-banner-simplified">
              <i class="fas fa-info-circle"></i>
              <div>
                <strong>Paciente Asegurado</strong>
                <p>Primero crearemos el paciente, luego podrás gestionar sus seguros médicos.</p>
              </div>
            </div>

            <div class="form-group">
              <label for="asegurado-nombre">Nombre:</label>
              <input type="text" id="asegurado-nombre" v-model="formData.nombre" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="asegurado-apellido">Apellido:</label>
              <input type="text" id="asegurado-apellido" v-model="formData.apellido" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="asegurado-cedula">Cédula:</label>
              <input type="text" id="asegurado-cedula" v-model="formData.cedula" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="asegurado-telefono">Teléfono:</label>
              <input type="text" id="asegurado-telefono" v-model="formData.telefono" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="asegurado-email">Email:</label>
              <input type="email" id="asegurado-email" v-model="formData.email" class="form-control">
            </div>
            
            <div class="seguros-hint">
              <i class="fas fa-shield-alt"></i>
              <span>Los seguros médicos se configurarán después de crear el paciente</span>
            </div>
          </div>

          <!-- Formulario para paciente particular -->
          <div v-if="tipoPaciente === 'particular'">
            <div class="form-group">
              <label for="particular-nombre">Nombre:</label>
              <input type="text" id="particular-nombre" v-model="formData.nombre" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="particular-apellido">Apellido:</label>
              <input type="text" id="particular-apellido" v-model="formData.apellido" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="particular-cedula">Cédula:</label>
              <input type="text" id="particular-cedula" v-model="formData.cedula" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="particular-telefono">Teléfono:</label>
              <input type="text" id="particular-telefono" v-model="formData.telefono" class="form-control" required>
            </div>
            <div class="form-group">
              <label for="particular-email">Email:</label>
              <input type="email" id="particular-email" v-model="formData.email" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="cerrarModal">Cancelar</button>
          <button class="btn btn-primary" @click="guardarPaciente" :disabled="loading">Guardar</button>
        </div>
      </div>
    </div>

    <!-- Modal para gestionar seguros -->
    <div class="modal" v-if="showSegurosModal && pacienteSeleccionado">
      <div class="modal-content large">
        <div class="modal-header">
          <h2>
            <i class="fas fa-shield-alt"></i>
            Gestión de Seguros - {{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellido }}
          </h2>
          <button class="close-btn" @click="cerrarModalSeguros">×</button>
        </div>
        <div class="modal-body">
          <GestionSeguros 
            :paciente-id="pacienteSeleccionado.id"
            :paciente-nombre="`${pacienteSeleccionado.nombre} ${pacienteSeleccionado.apellido}`"
            @success="onSegurosSuccess"
            @error="onSegurosError"
          />
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="cerrarModalSeguros">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import GestionSeguros from '../Shared/GestionSeguros.vue';

export default {
  name: 'Pacientes',
  components: {
    GestionSeguros
  },
  data() {
    return {
      pacientes: [],
      aseguradoras: [],
      loading: false,
      filtros: {
        busqueda: '',
        aseguradora_id: '',
        tipo: ''
      },
      showCrearModal: false,
      tipoPaciente: 'asegurado',
      formData: {
        nombre: '',
        apellido: '',
        cedula: '',
        telefono: '',
        email: ''
      },
      showDetallesModal: false,
      showEditarModal: false,
      showSegurosModal: false,
      pacienteSeleccionado: null,
    };
  },
  computed: {
    formTitulo() {
      return this.tipoPaciente === 'particular' ? 
        'Nuevo Paciente Particular' : 
        'Nuevo Paciente Asegurado';
    }
  },
  mounted() {
    // Cargar datos iniciales
    this.cargarPacientes();
  },
  methods: {
      verDetalles(paciente) {
      this.pacienteSeleccionado = paciente;
      this.showDetallesModal = true;
    },
    
    async editarPaciente(paciente) {
      // Asegurarse de que las aseguradoras estén cargadas
      if (this.aseguradoras.length === 0) {
        await this.cargarAseguradoras();
      }
      
      // Clonar el paciente para evitar modificar directamente el objeto de la lista
      this.pacienteSeleccionado = JSON.parse(JSON.stringify(paciente));
      
      // Configurar el formulario según el tipo de paciente
      this.tipoPaciente = paciente.tipo;
      this.esNuevoTitular = paciente.es_titular === 1;
      
      // Llenar el formulario con los datos del paciente
      this.formData = {
        nombre: paciente.nombre,
        apellido: paciente.apellido,
        cedula: paciente.cedula,
        telefono: paciente.telefono,
        email: paciente.email || '',
        estado: paciente.estado || 'activo',
        numero_afiliado: paciente.numero_afiliado || '',
        parentesco: paciente.parentesco || 'otro',
        aseguradora_id: paciente.aseguradora_id || ''
      };
      
      // Si es beneficiario, buscamos y cargamos los datos del titular
      if (paciente.titular_id) {
        this.cargarDatosTitular(paciente.titular_id);
      }
      
      this.showEditarModal = true;
    },
    
    cargarDatosTitular(titularId) {
      this.loading = true;
      axios.get(`/api/titulares/obtener.php?id=${titularId}`, {
        headers: {
          'Authorization': `Bearer ${this.getToken()}`
        }
      })
      .then(response => {
        this.titularSeleccionado = response.data;
      })
      .catch(error => {
        console.error('Error al cargar datos del titular:', error);
      })
      .finally(() => {
        this.loading = false;
      });
    },
    
    // Actualizar paciente
    async actualizarPaciente() {
      this.loading = true;
      try {
        let pacienteData = {
          id: this.pacienteSeleccionado.id,
          nombre: this.formData.nombre,
          apellido: this.formData.apellido,
          cedula: this.formData.cedula,
          telefono: this.formData.telefono,
          email: this.formData.email || '',
          tipo: this.tipoPaciente,
          es_titular: this.pacienteSeleccionado.es_titular
        };
        
        //// Agregar campos específicos según el tipo de paciente
        if (this.tipoPaciente === 'asegurado') {
          if (this.pacienteSeleccionado.es_titular === 1) {
            // Para titulares, incluir la aseguradora
            pacienteData.aseguradora_id = parseInt(this.formData.aseguradora_id);
          } else if (this.titularSeleccionado) {
            // Para beneficiarios
            pacienteData.titular_id = this.titularSeleccionado.id;
            pacienteData.parentesco = this.formData.parentesco;
          }
        }
        
        const response = await axios.post('/api/pacientes/actualizar.php', pacienteData, {
          headers: {
            'Authorization': `Bearer ${this.getToken()}`,
            'Content-Type': 'application/json'
          }
        });
        
        alert('Paciente actualizado correctamente');
        this.cerrarModalEditar();
        this.cargarPacientes();
      } catch (error) {
        console.error('Error al actualizar paciente:', error);
        alert(error.response?.data?.message || 'Error al actualizar paciente');
      } finally {
        this.loading = false;
      }
    },
    
    cerrarModalEditar() {
      this.showEditarModal = false;
      this.pacienteSeleccionado = null;
      this.resetearFormulario();
    },
    
    cerrarModalDetalles() {
      this.showDetallesModal = false;
      this.pacienteSeleccionado = null;
    },
    
    gestionarSeguros(paciente) {
      this.pacienteSeleccionado = paciente;
      this.showSegurosModal = true;
    },
    
    cerrarModalSeguros() {
      this.showSegurosModal = false;
      this.pacienteSeleccionado = null;
      // Recargar pacientes para actualizar contadores de seguros
      this.cargarPacientes();
    },
    
    onSegurosSuccess(message) {
      // Mostrar mensaje de éxito
      alert(message);
    },
    
    onSegurosError(message) {
      // Mostrar mensaje de error
      alert(message);
    },
    
    getSegurosClass(count) {
      if (!count || count === 0) return 'seguros-none';
      if (count === 1) return 'seguros-single';
      return 'seguros-multiple';
    },

    async cargarAseguradoras() {
      try {
        const response = await axios.get('/api/aseguradoras/listar.php', {
          headers: {
            'Authorization': `Bearer ${this.getToken()}`
          }
        });
        this.aseguradoras = response.data;
      } catch (error) {
        console.error('Error al cargar aseguradoras:', error);
        // Si hay error, usamos datos de ejemplo
        this.aseguradoras = [
          { id: 1, nombre_comercial: 'Seguros Capital' }
        ];
      }
    },
    
    async cargarPacientes() {
      this.loading = true;
      try {
        let url = '/api/pacientes/listar.php';
        let params = {};
        
        // Filtro de búsqueda por texto
        if (this.filtros.busqueda) {
          params.busqueda = this.filtros.busqueda;
        }
        
        // Filtro de aseguradora
        if (this.filtros.aseguradora_id) {
          if (this.filtros.aseguradora_id === 'particular') {
            params.tipo = 'particular';
          } else {
            params.aseguradora_id = this.filtros.aseguradora_id;
          }
        }
        
        // Filtro por tipo
        if (this.filtros.tipo) {
          if (this.filtros.tipo === 'particular') {
            params.tipo = 'particular';
          } else if (this.filtros.tipo === 'titular') {
            params.es_titular = 1;
            params.tipo = 'asegurado';
          } else if (this.filtros.tipo === 'beneficiario') {
            params.es_titular = 0;
            params.tipo = 'asegurado';
          }
        }
        
        const response = await axios.get(url, {
          params,
          headers: {
            'Authorization': `Bearer ${this.getToken()}`
          }
        });
        
        this.pacientes = response.data;
        
        // Llamar a cargarAseguradoras después de cargar pacientes
        if (!this.aseguradoras.length) {
          this.cargarAseguradoras();
        }
      } catch (error) {
        console.error('Error al cargar pacientes:', error);
      } finally {
        this.loading = false;
      }
    },
    
    // Nuevo método para cargar aseguradoras al abrir el modal
    async abrirModalCrear() {
      this.showCrearModal = true;
      await this.cargarAseguradoras(); // Aseguramos que las aseguradoras estén cargadas
    },
    
    resetearFiltros() {
      this.filtros = {
        busqueda: '',
        aseguradora_id: '',
        tipo: ''
      };
      this.cargarPacientes();
    },
    formatearFecha(fecha) {
      if (!fecha) return '';
      return new Date(fecha).toLocaleDateString('es-ES');
    },
    getEstadoClass(estado) {
      const clases = {
        'solicitada': 'estado-solicitada',
        'asignada': 'estado-asignada',
        'confirmada': 'estado-confirmada',
        'cancelada': 'estado-cancelada',
        'completada': 'estado-completada'
      };
      return clases[estado] || '';
    },
    cerrarModal() {
      this.showCrearModal = false;
      this.resetearFormulario();
    },
    resetearFormulario() {
      this.tipoPaciente = 'asegurado';
      this.formData = {
        nombre: '',
        apellido: '',
        cedula: '',
        telefono: '',
        email: ''
      };
    },
    async guardarPaciente() {
      this.loading = true;
      try {
        // Validar campos obligatorios
        if (!this.formData.nombre || !this.formData.apellido || !this.formData.cedula || 
            !this.formData.telefono) {
          throw new Error('Debe completar todos los campos obligatorios');
        }
        
        const pacienteData = {
          nombre: this.formData.nombre,
          apellido: this.formData.apellido,
          cedula: this.formData.cedula,
          telefono: this.formData.telefono,
          email: this.formData.email || '',
          tipo: this.tipoPaciente
        };
        
        console.log('Enviando datos de paciente:', JSON.stringify(pacienteData));
        
        const response = await axios.post('/api/pacientes/crear.php', pacienteData, {
          headers: {
            'Authorization': `Bearer ${this.getToken()}`,
            'Content-Type': 'application/json'
          }
        });
        
        console.log('Respuesta del servidor:', response.data);
        
        const mensaje = this.tipoPaciente === 'asegurado' ? 
          'Paciente creado exitosamente. Ahora puede gestionar sus seguros médicos desde la tabla.' :
          'Paciente particular creado exitosamente';
          
        alert(mensaje);
        
        this.cerrarModal();
        this.cargarPacientes();
        
        // Si es asegurado, mostrar hint sobre gestión de seguros
        if (this.tipoPaciente === 'asegurado') {
          setTimeout(() => {
            alert('💡 Consejo: Use el botón "Gestionar Seguros" en la tabla para agregar los seguros médicos del paciente.');
          }, 1000);
        }
        
      } catch (error) {
        console.error('Error al guardar paciente:', error);
        alert(error.response?.data?.error || error.message || 'Error al guardar paciente');
      } finally {
        this.loading = false;
      }
    },
    getToken() {
      // Aquí deberías recuperar el token JWT de tu almacenamiento (localStorage, etc.)
      return localStorage.getItem('token');
    }
  }
}
</script>

<style scoped>
.pacientes-container {
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

.pacientes-table {
  width: 100%;
  border-collapse: collapse;
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pacientes-table th,
.pacientes-table td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.pacientes-table th {
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
}

.btn-primary {
  background-color: var(--primary-color);
  border: 1px solid var(--primary-color);
  color: white;
}

.btn-secondary {
  background-color: #6c757d;
  border: 1px solid #6c757d;
  color: white;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-icon {
  background: none;
  border: none;
  color: var(--primary-color);
  font-size: 16px;
  cursor: pointer;
  margin-right: 8px;
}

.estado-solicitada {
  color: #ff9800;
}

.estado-asignada {
  color: #2196f3;
}

.estado-confirmada {
  color: #4caf50;
}

.estado-cancelada {
  color: #f44336;
}

.estado-completada {
  color: #4caf50;
}

/* Modal styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
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
  font-size: 1.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* Tabs */
.tabs {
  display: flex;
  margin-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.tab-btn {
  padding: 10px 15px;
  border: none;
  background: none;
  cursor: pointer;
  font-weight: 500;
  color: #6c757d;
}

.tab-btn.active {
  color: var(--primary-color);
  border-bottom: 2px solid var(--primary-color);
}

/* Form styles */
.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.search-group {
  display: flex;
}

.search-group input {
  flex: 1;
  border-radius: 4px 0 0 4px;
}

.btn-search {
  padding: 10px 15px;
  background-color: var(--primary-color);
  color: white;
  border: none;
  border-radius: 0 4px 4px 0;
  cursor: pointer;
}

.titular-info {
  background-color: #f8f9fa;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.titular-info h3 {
  margin-top: 0;
  margin-bottom: 10px;
  font-size: 1.2rem;
}

.titular-info p {
  margin: 5px 0;
}

.radio-group {
  display: flex;
  gap: 20px;
  margin-bottom: 15px;
}

.radio-option {
  display: flex;
  align-items: center;
}

.radio-option input[type="radio"] {
  margin-right: 5px;
}

.checkbox-group {
  display: flex;
  align-items: center;
}

.checkbox-group input[type="checkbox"] {
  margin-right: 5px;
}

/* Estilos para los detalles del paciente */
.detalles-paciente {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detalle-grupo {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
}

.detalle-grupo h3 {
  margin-top: 0;
  margin-bottom: 10px;
  color: var(--primary-color);
  font-size: 1.1rem;
}

.detalle-grupo p {
  margin: 5px 0;
  line-height: 1.4;
}

/* Estilos para indicadores de seguros */
.seguros-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
}

.seguros-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  font-size: 12px;
  font-weight: 600;
  color: white;
}

.seguros-count.seguros-none {
  background-color: #6c757d;
}

.seguros-count.seguros-single {
  background-color: #28a745;
}

.seguros-count.seguros-multiple {
  background-color: #007bff;
}

.seguros-indicator small {
  font-size: 11px;
  color: #6c757d;
}

.btn-icon-small {
  background: none;
  border: none;
  color: #007bff;
  font-size: 14px;
  cursor: pointer;
  padding: 2px;
  border-radius: 3px;
  transition: background-color 0.2s;
}

.btn-icon-small:hover {
  background-color: rgba(0, 123, 255, 0.1);
}

.text-muted {
  color: #6c757d;
  font-style: italic;
}

/* Modal large */
.modal-content.large {
  max-width: 900px;
  width: 95%;
}

/* Nuevos estilos para el flujo simplificado */
.info-banner-simplified {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #e3f2fd;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 24px;
  border-left: 4px solid #2196f3;
}

.info-banner-simplified i {
  color: #2196f3;
  margin-top: 2px;
  font-size: 18px;
}

.info-banner-simplified strong {
  color: #1976d2;
  display: block;
  margin-bottom: 4px;
}

.info-banner-simplified p {
  margin: 0;
  color: #1976d2;
  font-size: 14px;
}

.seguros-hint {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: #f0f8ff;
  border: 1px dashed #007bff;
  border-radius: 6px;
  margin-top: 20px;
  color: #0056b3;
  font-size: 14px;
}

.seguros-hint i {
  color: #007bff;
}
</style>