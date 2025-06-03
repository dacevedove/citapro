<template>
    <div class="gestion-pacientes-container">
      <div class="header-section">
        <h1>Gestión de Pacientes</h1>
        <button @click="abrirModalNuevo" class="btn btn-primary">
          <i class="fas fa-plus"></i> Nuevo Paciente
        </button>
      </div>
      
      <div class="filters-section">
        <div class="search-box">
          <input 
            type="text" 
            v-model="filtros.busqueda" 
            placeholder="Buscar por nombre o cédula" 
            @input="aplicarFiltros"
            class="form-control"
          >
          <button class="search-btn">
            <i class="fas fa-search"></i>
          </button>
        </div>
        
        <div class="filter-options">
          <div class="filter-item">
            <label>Titular:</label>
            <select v-model="filtros.titular_id" @change="aplicarFiltros" class="form-control">
              <option value="">Todos</option>
              <option v-for="titular in titulares" :key="titular.id" :value="titular.id">
                {{ titular.nombre }} {{ titular.apellido }} - {{ titular.numero_afiliado }}
              </option>
            </select>
          </div>
          
          <button @click="resetearFiltros" class="btn btn-outline">
            <i class="fas fa-sync"></i> Resetear
          </button>
        </div>
      </div>
      
      <div v-if="cargando" class="loading-container">
        <div class="spinner"></div>
        <p>Cargando pacientes...</p>
      </div>
      
      <div v-else-if="pacientes.length === 0" class="empty-state">
        <i class="fas fa-user-injured"></i>
        <p>No se encontraron pacientes que coincidan con los criterios de búsqueda.</p>
        <button @click="abrirModalNuevo" class="btn btn-primary">
          Agregar Nuevo Paciente
        </button>
      </div>
      
      <div v-else>
        <div class="table-responsive">
          <table class="pacientes-table">
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
                <th>Contacto</th>
                <th>Titular</th>
                <th>Parentesco</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="paciente in pacientesPaginados" :key="paciente.id">
                <td>{{ paciente.nombre }} {{ paciente.apellido }}</td>
                <td>{{ paciente.cedula }}</td>
                <td>
                  <div class="contacto-info">
                    <span v-if="paciente.telefono">
                      <i class="fas fa-phone"></i> {{ paciente.telefono }}
                    </span>
                    <span v-if="paciente.email">
                      <i class="fas fa-envelope"></i> {{ paciente.email }}
                    </span>
                  </div>
                </td>
                <td>
                  <span v-if="paciente.es_titular">
                    Es titular
                  </span>
                  <span v-else>
                    {{ obtenerNombreTitular(paciente.titular_id) }}
                  </span>
                </td>
                <td>{{ paciente.parentesco || '-' }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="verHistorialCitas(paciente)" class="btn-icon" title="Ver historial de citas">
                      <i class="fas fa-history"></i>
                    </button>
                    <button @click="editarPaciente(paciente)" class="btn-icon" title="Editar">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click="solicitarCita(paciente)" class="btn-icon" title="Solicitar cita">
                      <i class="fas fa-calendar-plus"></i>
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
      
      <!-- Modal para nuevo o editar paciente -->
      <div v-if="mostrarModalPaciente" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>{{ editandoPaciente ? 'Editar Paciente' : 'Nuevo Paciente' }}</h2>
            <button @click="cerrarModalPaciente" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="guardarPaciente">
              <div class="form-group">
                <label>
                  <input type="checkbox" v-model="pacienteForm.es_titular" @change="actualizarFormulario">
                  Este paciente es un titular
                </label>
              </div>
              
              <div v-if="!pacienteForm.es_titular" class="form-group">
                <label for="titular_id">Titular:</label>
                <select 
                  id="titular_id" 
                  v-model="pacienteForm.titular_id" 
                  required
                  class="form-control"
                >
                  <option value="" disabled selected>Seleccione un titular</option>
                  <option v-for="titular in titulares" :key="titular.id" :value="titular.id">
                    {{ titular.nombre }} {{ titular.apellido }} - {{ titular.numero_afiliado }}
                  </option>
                </select>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="nombre">Nombre:</label>
                  <input 
                    type="text" 
                    id="nombre" 
                    v-model="pacienteForm.nombre" 
                    required
                    class="form-control"
                  >
                </div>
                
                <div class="form-group">
                  <label for="apellido">Apellido:</label>
                  <input 
                    type="text" 
                    id="apellido" 
                    v-model="pacienteForm.apellido" 
                    required
                    class="form-control"
                  >
                </div>
              </div>
              
              <div class="form-group">
                <label for="cedula">Cédula de Identidad:</label>
                <input 
                  type="text" 
                  id="cedula" 
                  v-model="pacienteForm.cedula" 
                  required
                  class="form-control"
                  :disabled="editandoPaciente"
                >
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="telefono">Teléfono:</label>
                  <input 
                    type="tel" 
                    id="telefono" 
                    v-model="pacienteForm.telefono" 
                    required
                    class="form-control"
                  >
                </div>
                
                <div class="form-group">
                  <label for="email">Email:</label>
                  <input 
                    type="email" 
                    id="email" 
                    v-model="pacienteForm.email" 
                    class="form-control"
                  >
                </div>
              </div>
              
              <div v-if="!pacienteForm.es_titular" class="form-group">
                <label for="parentesco">Parentesco:</label>
                <select id="parentesco" v-model="pacienteForm.parentesco" required class="form-control">
                  <option value="" disabled selected>Seleccione un parentesco</option>
                  <option value="Cónyuge">Cónyuge</option>
                  <option value="Hijo/a">Hijo/a</option>
                  <option value="Padre/Madre">Padre/Madre</option>
                  <option value="Hermano/a">Hermano/a</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
              
              <div v-if="!pacienteForm.es_titular && pacienteForm.parentesco === 'Otro'" class="form-group">
                <label for="otro_parentesco">Especificar parentesco:</label>
                <input 
                  type="text" 
                  id="otro_parentesco" 
                  v-model="pacienteForm.otro_parentesco" 
                  required
                  class="form-control"
                >
              </div>
              
              <div class="modal-footer">
                <button type="button" @click="cerrarModalPaciente" class="btn btn-outline">Cancelar</button>
                <button type="submit" class="btn btn-primary" :disabled="guardando">
                  {{ guardando ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      
      <!-- Modal para historial de citas -->
      <div v-if="mostrarModalHistorial" class="modal-overlay">
        <div class="modal-container">
          <div class="modal-header">
            <h2>Historial de Citas</h2>
            <button @click="cerrarModalHistorial" class="close-btn">&times;</button>
          </div>
          
          <div class="modal-body">
            <div class="paciente-info">
              <h3>{{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellido }}</h3>
              <p class="cedula">CI: {{ pacienteSeleccionado.cedula }}</p>
            </div>
            
            <div v-if="cargandoHistorial" class="loading-mini">
              <div class="spinner-mini"></div>
              <span>Cargando historial de citas...</span>
            </div>
            
            <div v-else-if="historialCitas.length === 0" class="empty-historial">
              <p>No hay citas registradas para este paciente.</p>
              <button @click="solicitarCita(pacienteSeleccionado)" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Solicitar Nueva Cita
              </button>
            </div>
            
            <div v-else class="historial-citas">
              <div v-for="cita in historialCitas" :key="cita.id" class="cita-card">
                <div class="cita-header">
                  <div class="cita-fecha">
                    <i class="fas fa-calendar-alt"></i>
                    {{ formatearFecha(cita.fecha) }}
                    <span v-if="cita.hora">
                      {{ formatearHora(cita.hora) }}
                    </span>
                  </div>
                  <span :class="['cita-estado', `estado-${cita.estado.toLowerCase()}`]">
                    {{ cita.estado }}
                  </span>
                </div>
                
                <div class="cita-body">
                  <div class="cita-info">
                    <div class="cita-especialidad">{{ cita.especialidad }}</div>
                    <p v-if="cita.doctor_nombre" class="cita-doctor">
                      <i class="fas fa-user-md"></i> Dr. {{ cita.doctor_nombre }}
                    </p>
                    <p v-if="cita.consultorio_nombre" class="cita-consultorio">
                      <i class="fas fa-clinic-medical"></i> {{ cita.consultorio_nombre }} ({{ cita.consultorio_ubicacion }})
                    </p>
                  </div>
                  <div class="cita-descripcion">
                    <strong>Motivo:</strong> {{ cita.descripcion }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="modal-footer">
            <button @click="cerrarModalHistorial" class="btn btn-primary">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    name: 'GestionPacientes',
    data() {
      return {
        pacientes: [],
        titulares: [],
        filtros: {
          busqueda: '',
          titular_id: ''
        },
        ordenamiento: {
          campo: 'nombre',
          ascendente: true
        },
        paginaActual: 1,
        itemsPorPagina: 10,
        cargando: false,
        
        // Modal nuevo/editar paciente
        mostrarModalPaciente: false,
        pacienteForm: {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          titular_id: '',
          es_titular: false,
          parentesco: '',
          otro_parentesco: ''
        },
        editandoPaciente: false,
        guardando: false,
        
        // Modal historial de citas
        mostrarModalHistorial: false,
        pacienteSeleccionado: {},
        historialCitas: [],
        cargandoHistorial: false
      };
    },
    computed: {
      pacientesFiltrados() {
        let resultado = [...this.pacientes];
        
        // Aplicar filtros
        if (this.filtros.busqueda) {
          const busqueda = this.filtros.busqueda.toLowerCase();
          resultado = resultado.filter(paciente => 
            `${paciente.nombre} ${paciente.apellido}`.toLowerCase().includes(busqueda) ||
            paciente.cedula.toLowerCase().includes(busqueda)
          );
        }
        
        if (this.filtros.titular_id) {
          resultado = resultado.filter(paciente => 
            (paciente.titular_id === this.filtros.titular_id) ||
            (paciente.es_titular && paciente.id === this.filtros.titular_id)
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
      pacientesPaginados() {
        const inicio = (this.paginaActual - 1) * this.itemsPorPagina;
        const fin = inicio + this.itemsPorPagina;
        return this.pacientesFiltrados.slice(inicio, fin);
      },
      totalPaginas() {
        return Math.ceil(this.pacientesFiltrados.length / this.itemsPorPagina);
      }
    },
    mounted() {
      this.cargarTitulares();
      this.cargarPacientes();
    },
    methods: {
      async cargarTitulares() {
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/titulares/listar.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.titulares = response.data;
        } catch (error) {
          console.error('Error al cargar titulares:', error);
        }
      },
      
      async cargarPacientes() {
        this.cargando = true;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get('/api/pacientes/listar.php', {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.pacientes = response.data;
        } catch (error) {
          console.error('Error al cargar pacientes:', error);
          alert('Error al cargar la lista de pacientes. Por favor, intente nuevamente.');
        } finally {
          this.cargando = false;
        }
      },
      
      obtenerNombreTitular(titularId) {
        if (!titularId) return '-';
        
        const titular = this.titulares.find(t => t.id === titularId);
        return titular ? `${titular.nombre} ${titular.apellido}` : '-';
      },
      
      aplicarFiltros() {
        this.paginaActual = 1; // Volver a la primera página al filtrar
      },
      
      resetearFiltros() {
        this.filtros = {
          busqueda: '',
          titular_id: ''
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
      
      formatearFecha(fecha) {
        if (!fecha) return '-';
        
        const opciones = { day: '2-digit', month: '2-digit', year: 'numeric' };
        return new Date(fecha).toLocaleDateString('es-ES', opciones);
      },
      
      formatearHora(hora) {
        if (!hora) return '';
        
        // Convertir formato de 24 horas a formato AM/PM
        const [hh, mm] = hora.split(':');
        let h = parseInt(hh);
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12; // Convertir 0 a 12 para 12 AM
        
        return `${h}:${mm} ${ampm}`;
      },
      
      // Modal nuevo paciente
      abrirModalNuevo() {
        this.pacienteForm = {
          nombre: '',
          apellido: '',
          cedula: '',
          telefono: '',
          email: '',
          titular_id: '',
          es_titular: false,
          parentesco: '',
          otro_parentesco: ''
        };
        this.editandoPaciente = false;
        this.mostrarModalPaciente = true;
      },
      
      // Modal editar paciente
      editarPaciente(paciente) {
        this.pacienteForm = {
          id: paciente.id,
          nombre: paciente.nombre,
          apellido: paciente.apellido,
          cedula: paciente.cedula,
          telefono: paciente.telefono,
          email: paciente.email || '',
          titular_id: paciente.titular_id || '',
          es_titular: paciente.es_titular,
          parentesco: paciente.parentesco || '',
          otro_parentesco: ''
        };
        
        if (this.pacienteForm.parentesco && !['Cónyuge', 'Hijo/a', 'Padre/Madre', 'Hermano/a'].includes(this.pacienteForm.parentesco)) {
          this.pacienteForm.otro_parentesco = this.pacienteForm.parentesco;
          this.pacienteForm.parentesco = 'Otro';
        }
        
        this.editandoPaciente = true;
        this.mostrarModalPaciente = true;
      },
      
      cerrarModalPaciente() {
        this.mostrarModalPaciente = false;
      },
      
      actualizarFormulario() {
        if (this.pacienteForm.es_titular) {
          this.pacienteForm.titular_id = '';
          this.pacienteForm.parentesco = '';
          this.pacienteForm.otro_parentesco = '';
        }
      },
      
      async guardarPaciente() {
        if (!this.pacienteForm.nombre || !this.pacienteForm.apellido || !this.pacienteForm.cedula || 
            !this.pacienteForm.telefono || (!this.pacienteForm.es_titular && !this.pacienteForm.titular_id)) {
          alert('Por favor, complete todos los campos obligatorios.');
          return;
        }
        
        if (!this.pacienteForm.es_titular && this.pacienteForm.parentesco === 'Otro' && !this.pacienteForm.otro_parentesco) {
          alert('Por favor, especifique el parentesco.');
          return;
        }
        
        this.guardando = true;
        
        try {
          const token = localStorage.getItem('token');
          
          // Preparar datos para enviar
          const payload = {
            ...this.pacienteForm,
            parentesco: this.pacienteForm.parentesco === 'Otro' ? this.pacienteForm.otro_parentesco : this.pacienteForm.parentesco,
            tipo: 'asegurado'
          };
          
          let response;
          
          if (this.editandoPaciente) {
            response = await axios.put('/api/pacientes/actualizar.php', payload, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
          } else {
            response = await axios.post('/api/pacientes/crear.php', payload, {
              headers: { 'Authorization': `Bearer ${token}` }
            });
          }
          
          if (response.data && (response.data.id || response.data.message)) {
            await this.cargarPacientes();
            this.cerrarModalPaciente();
            alert(this.editandoPaciente ? 'Paciente actualizado correctamente.' : 'Paciente creado correctamente.');
          }
        } catch (error) {
          console.error('Error al guardar paciente:', error);
          
          if (error.response && error.response.data && error.response.data.error) {
            alert(error.response.data.error);
          } else {
            alert('Error al guardar el paciente. Por favor, intente nuevamente.');
          }
        } finally {
          this.guardando = false;
        }
      },
      
      // Modal historial de citas
      async verHistorialCitas(paciente) {
        this.pacienteSeleccionado = {...paciente};
        this.mostrarModalHistorial = true;
        await this.cargarHistorialCitas(paciente.id);
      },
      
      async cargarHistorialCitas(pacienteId) {
        this.cargandoHistorial = true;
        
        try {
          const token = localStorage.getItem('token');
          const response = await axios.get(`/api/citas/historial-paciente.php?paciente_id=${pacienteId}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          this.historialCitas = response.data;
        } catch (error) {
          console.error('Error al cargar historial de citas:', error);
        } finally {
          this.cargandoHistorial = false;
        }
      },
      
      cerrarModalHistorial() {
        this.mostrarModalHistorial = false;
        this.pacienteSeleccionado = {};
        this.historialCitas = [];
      },
      
      // Solicitar cita para el paciente
      solicitarCita(paciente) {
        // Almacenar el ID del paciente en el localStorage para usarlo en el componente de solicitar cita
        localStorage.setItem('paciente_seleccionado_id', paciente.id);
        localStorage.setItem('paciente_seleccionado_nombre', `${paciente.nombre} ${paciente.apellido}`);
        
        // Redirigir a la página de solicitar cita
        this.$router.push('/aseguradora/solicitar-cita');
      }
    }
  };
  </script>
  
  <style scoped>
  .gestion-pacientes-container {
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
  
  .pacientes-table th.sortable {
    cursor: pointer;
  }
  
  .pacientes-table th.sortable:hover {
    background-color: #e9ecef;
  }
  
  .pacientes-table tr:hover {
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

/* Estilos para modal de historial */
.paciente-info {
  margin-bottom: 20px;
}

.paciente-info h3 {
  margin: 0 0 5px 0;
  color: var(--dark-color);
}

.cedula {
  color: var(--secondary-color);
  margin: 0;
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

.empty-historial {
  padding: 20px;
  text-align: center;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.historial-citas {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.cita-card {
  background-color: #f8f9fa;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.cita-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  background-color: #e9ecef;
}

.cita-fecha {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--dark-color);
}

.cita-estado {
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.estado-solicitada {
  background-color: #fff3cd;
  color: #856404;
}

.estado-asignada {
  background-color: #d1ecf1;
  color: #0c5460;
}

.estado-confirmada {
  background-color: #d4edda;
  color: #155724;
}

.estado-cancelada {
  background-color: #f8d7da;
  color: #721c24;
}

.estado-completada {
  background-color: #e2e3e5;
  color: #383d41;
}

.cita-body {
  padding: 15px;
}

.cita-info {
  margin-bottom: 10px;
}

.cita-especialidad {
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 5px;
}

.cita-doctor, .cita-consultorio {
  margin: 5px 0;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--secondary-color);
  font-size: 14px;
}

.cita-descripcion {
  font-size: 14px;
  color: var(--dark-color);
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
}
</style>