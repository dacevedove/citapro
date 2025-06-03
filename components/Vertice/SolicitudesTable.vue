<template>
  <div class="solicitudes-container">
    <div class="header-actions">
      <h2>Solicitudes</h2>
      <button class="btn btn-primary" @click="showAddModal = true">
        <i class="fas fa-plus-circle"></i> Nueva Solicitud
      </button>
    </div>

    <!-- Filtros en layout de tres columnas -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Filtros</span>
        <button class="btn btn-sm btn-outline-secondary" @click="limpiarFiltros">
          <i class="fas fa-eraser"></i> Limpiar
        </button>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="filtro-estatus" class="form-label">Estatus:</label>
            <select id="filtro-estatus" class="form-select" v-model="filtros.estatus" @change="aplicarFiltros">
              <option value="">Todos</option>
              <option value="pendiente">Pendiente</option>
              <option value="procesada">Procesada</option>
              <option value="rechazada">Rechazada</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="filtro-fecha" class="form-label">Fecha:</label>
            <input type="date" id="filtro-fecha" class="form-control" v-model="filtros.fecha" @change="aplicarFiltros">
          </div>
          <div class="col-md-4 mb-3">
            <label for="filtro-especialidad" class="form-label">Especialidad:</label>
            <select id="filtro-especialidad" class="form-select" v-model="filtros.especialidad" @change="aplicarFiltros">
              <option value="">Todas</option>
              <option v-for="especialidad in especialidades" :key="especialidad.id" :value="especialidad.id">
                {{ especialidad.nombre }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Alerta para mensajes -->
    <div v-if="alert.show" :class="`alert alert-${alert.type} alert-dismissible fade show`" role="alert">
      {{ alert.message }}
      <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
    </div>

    <!-- Tabla de Solicitudes -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Listado de Solicitudes</span>
        <button class="btn btn-sm btn-outline-primary" @click="loadSolicitudes">
          <i class="fas fa-sync-alt"></i> Actualizar
        </button>
      </div>
      <div class="card-body">
        <div v-if="isLoading" class="text-center my-4">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
        
        <div class="table-responsive" v-else>
          <table class="table table-striped table-hover">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Cédula Titular</th>
                <th>Cédula Paciente</th>
                <th>Nombre Paciente</th>
                <th>Teléfono</th>
                <th>Fecha Nacimiento</th>
                <th>Sexo</th>
                <th>Especialidad</th>
                <th>Estatus</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="solicitud in solicitudesFiltradas" :key="solicitud.id" :class="{ 'bg-light': solicitud.estatus !== 'pendiente' }">
                <td>{{ solicitud.numero_id || solicitud.id }}</td>
                <td>{{ formatDate(solicitud.fecha_solicitud) }}</td>
                <td>{{ solicitud.cedula_titular }}</td>
                <td>{{ solicitud.cedula_paciente }}</td>
                <td>{{ solicitud.nombre_paciente }}</td>
                <td>{{ solicitud.telefono }}</td>
                <td>{{ formatDate(solicitud.fecha_nacimiento) }}</td>
                <td>{{ solicitud.sexo === 'M' ? 'Masculino' : 'Femenino' }}</td>
                <td>{{ getEspecialidadNombre(solicitud.especialidad_requerida) }}</td>
                <td>
                  <span class="badge" :class="getStatusBadgeClass(solicitud.estatus)">
                    {{ getStatusText(solicitud.estatus) }}
                  </span>
                </td>
                <td class="text-center">
                  <div class="btn-group">
                    <button 
                      v-if="solicitud.estatus === 'pendiente'" 
                      class="btn btn-sm btn-primary" 
                      @click="asignarSolicitud(solicitud)"
                      title="Asignar cita"
                    >
                      <i class="fas fa-calendar-check"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-info" 
                      @click="editarSolicitud(solicitud)"
                      title="Editar solicitud"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-danger" 
                      @click="confirmarEliminar(solicitud)"
                      title="Eliminar solicitud"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="solicitudesFiltradas.length === 0">
                <td colspan="11" class="text-center">No hay solicitudes que coincidan con los criterios de búsqueda</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal para agregar/editar solicitud con mejor uso del espacio -->
    <div class="modal" :class="{ 'show': showAddModal || showEditModal }" v-if="showAddModal || showEditModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ showEditModal ? 'Editar Solicitud' : 'Nueva Solicitud' }}</h5>
            <button type="button" class="close" @click="cerrarModal">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">

              <!-- Información de la asignación (solo si está procesada) -->
              <div v-if="solicitudAsignacion && showEditModal && formSolicitud.estatus === 'procesada'" class="mt-4">
                <div class="card bg-light">
                  <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-calendar-check text-primary"></i> Información de la Cita Asignada</h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="mb-3">
                          <strong>Doctor:</strong> {{ solicitudAsignacion.doctor_nombre }}
                        </div>
                        <div class="mb-3">
                          <strong>Fecha:</strong> {{ formatDate(solicitudAsignacion.fecha_cita) }}
                        </div>
                        <div class="mb-3">
                          <strong>Consultorio:</strong> {{ solicitudAsignacion.consultorio_nombre || 'No asignado' }}
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-3">
                          <strong>Hora:</strong> {{ formatTime(solicitudAsignacion.hora_inicio) }}
                        </div>
                        <div class="mb-3">
                          <strong style="margin-right:.5em">Estado:</strong> 
                          <span class="badge bg-success">Asignada</span>
                        </div>
                        <div class="mb-3">
                          <button 
                            type="button" 
                            class="btn btn-danger btn-sm"
                            @click="confirmarEliminarAsignacion"
                          >
                            <i class="fas fa-unlink"></i> Eliminar Asignación
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            <form @submit.prevent="submitSolicitud">
              <div class="row">
                <!-- Primera columna -->
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="numero_id">ID</label>
                    <input type="text" class="form-control" id="numero_id" v-model="formSolicitud.numero_id" 
                           required :disabled="!formEditableFields && showEditModal">
                  </div>
                  <div class="form-group mb-3">
                    <label for="cedula_titular">Cédula Titular</label>
                    <input type="text" class="form-control" id="cedula_titular" v-model="formSolicitud.cedula_titular" 
                           required :disabled="!formEditableFields && showEditModal">
                  </div>
                  <div class="form-group mb-3">
                    <label for="cedula_paciente">Cédula Paciente</label>
                    <input type="text" class="form-control" id="cedula_paciente" v-model="formSolicitud.cedula_paciente" 
                           required :disabled="!formEditableFields && showEditModal">
                  </div>
                  <div class="form-group mb-3">
                    <label for="nombre_paciente">Nombre Paciente</label>
                    <input type="text" class="form-control" id="nombre_paciente" v-model="formSolicitud.nombre_paciente" 
                           required :disabled="!formEditableFields && showEditModal">
                  </div>
                </div>
                
                <!-- Segunda columna -->
                <div class="col-md-6">
                  <div class="form-group mb-3">
                    <label for="telefono">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" v-model="formSolicitud.telefono" 
                           required :disabled="!formEditableFields && showEditModal">
                  </div>
                  <div class="form-group mb-3">
                    <label for="fecha_nacimiento">Fecha Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" v-model="formSolicitud.fecha_nacimiento" 
                           required :disabled="!formEditableFields && showEditModal">
                  </div>
                  <div class="form-group mb-3">
                    <label for="sexo">Sexo</label>
                    <select class="form-control" id="sexo" v-model="formSolicitud.sexo" 
                            required :disabled="!formEditableFields && showEditModal">
                      <option value="M">Masculino</option>
                      <option value="F">Femenino</option>
                    </select>
                  </div>
                  <div class="form-group mb-3">
                    <label for="especialidad_requerida">Especialidad Requerida</label>
                    <select class="form-control" id="especialidad_requerida" v-model="formSolicitud.especialidad_requerida" 
                            required :disabled="!formEditableFields && showEditModal">
                      <option value="">Seleccione una especialidad</option>
                      <option v-for="especialidad in especialidades" :key="especialidad.id" :value="especialidad.id">
                        {{ especialidad.nombre }}
                      </option>
                    </select>
                  </div>
                </div>
              </div>
              
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="cerrarModal">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                  {{ showEditModal ? 'Actualizar' : 'Guardar' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal" :class="{ 'show': showDeleteModal }" v-if="showDeleteModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmar Eliminación</h5>
            <button type="button" class="close" @click="showDeleteModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>¿Está seguro que desea eliminar la solicitud de <strong>{{ solicitudAEliminar?.nombre_paciente }}</strong>?</p>
            <p class="text-danger">Esta acción no se puede deshacer.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="eliminarSolicitud">Eliminar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmación para eliminar asignación -->
    <div class="modal" :class="{ 'show': showDeleteAsignacionModal }" v-if="showDeleteAsignacionModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmar Eliminación de Asignación</h5>
            <button type="button" class="close" @click="showDeleteAsignacionModal = false">
              <span>&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>¿Está seguro que desea eliminar la asignación de cita para <strong>{{ solicitudAEditar?.nombre_paciente }}</strong>?</p>
            <p>La solicitud volverá a estar en estado <span class="badge bg-warning text-dark">Pendiente</span>.</p>
            <p class="text-danger">Esta acción no se puede deshacer.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showDeleteAsignacionModal = false">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="eliminarAsignacion">Eliminar Asignación</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SolicitudesTable',
  data() {
    return {
      solicitudes: [],
      especialidades: [],
      isLoading: true,
      showAddModal: false,
      showEditModal: false,
      showDeleteModal: false,
      showDeleteAsignacionModal: false,  // Modal para confirmar eliminación de asignación
      solicitudAEditar: null,
      solicitudAEliminar: null,
      solicitudAsignacion: null,  // Datos de la asignación/cita
      formSolicitud: this.getEmptySolicitud(),
      formEditableFields: true,  // Controla si los campos del formulario son editables
      filtros: {
        estatus: '',
        fecha: '',
        especialidad: ''
      },
      alert: {
        show: false,
        type: 'success',
        message: ''
      }
    };
  },
  computed: {
    solicitudesFiltradas() {
      if (!this.filtros.estatus && !this.filtros.fecha && !this.filtros.especialidad) {
        return this.solicitudes;
      }
      
      return this.solicitudes.filter(solicitud => {
        // Filtrar por estatus
        if (this.filtros.estatus && solicitud.estatus !== this.filtros.estatus) {
          return false;
        }
        
        // Filtrar por fecha
        if (this.filtros.fecha) {
          const fechaSolicitud = new Date(solicitud.fecha_solicitud).toISOString().split('T')[0];
          if (fechaSolicitud !== this.filtros.fecha) {
            return false;
          }
        }
        
        // Filtrar por especialidad
        if (this.filtros.especialidad && solicitud.especialidad_requerida != this.filtros.especialidad) {
          return false;
        }
        
        return true;
      });
    }
  },
  created() {
    this.loadSolicitudes();
    this.loadEspecialidades();
  },
  methods: {
    async loadSolicitudes() {
      this.isLoading = true;
      
      try {
        const response = await axios.get('/lgm/api/vertice/listar_solicitudes.php');
        this.solicitudes = response.data.data || [];
      } catch (error) {
        console.error('Error cargando solicitudes:', error);
        this.showAlert('danger', 'Error al cargar las solicitudes');
      } finally {
        this.isLoading = false;
      }
    },
    async loadEspecialidades() {
      try {
        const response = await axios.get('/lgm/api/doctores/especialidades.php');
        this.especialidades = response.data;
      } catch (error) {
        console.error('Error cargando especialidades:', error);
        this.showAlert('danger', 'Error al cargar las especialidades');
      }
    },
    getEspecialidadNombre(id) {
      if (!id) return 'No especificada';
      const especialidad = this.especialidades.find(esp => esp.id == id);
      return especialidad ? especialidad.nombre : id;
    },
    submitSolicitud() {
      if (this.showEditModal) {
        this.actualizarSolicitud();
      } else {
        this.crearSolicitud();
      }
    },
    async crearSolicitud() {
      try {
        // Asegúrate de que la fecha de solicitud se establezca a la fecha actual
        const solicitudData = {
          ...this.formSolicitud,
          fecha_solicitud: new Date().toISOString().split('T')[0]
        };
        
        await axios.post('/lgm/api/vertice/crear_solicitud.php', solicitudData);
        this.showAlert('success', 'Solicitud creada exitosamente');
        this.cerrarModal();
        this.loadSolicitudes();
      } catch (error) {
        console.error('Error creando solicitud:', error);
        this.showAlert('danger', 'Error al crear la solicitud');
      }
    },
    async actualizarSolicitud() {
      try {
        // Verificar si hay una cita asignada y los campos están deshabilitados
        if (!this.formEditableFields && this.showEditModal) {
          this.showAlert('warning', 'No se pueden actualizar los datos. Elimine la asignación de cita primero.');
          return;
        }
        
        // Asegurarse de enviar el ID de la solicitud
        const solicitudData = {
          ...this.formSolicitud,
          id: this.solicitudAEditar.id
        };
        
        await axios.post('/lgm/api/vertice/actualizar_solicitud.php', solicitudData);
        this.showAlert('success', 'Solicitud actualizada exitosamente');
        this.cerrarModal();
        this.loadSolicitudes();
      } catch (error) {
        console.error('Error actualizando solicitud:', error);
        this.showAlert('danger', 'Error al actualizar la solicitud');
      }
    },
    async eliminarSolicitud() {
      try {
        await axios.post('/lgm/api/vertice/eliminar_solicitud.php', { id: this.solicitudAEliminar.id });
        this.showAlert('success', 'Solicitud eliminada exitosamente');
        this.showDeleteModal = false;
        this.solicitudAEliminar = null;
        this.loadSolicitudes();
      } catch (error) {
        console.error('Error eliminando solicitud:', error);
        this.showAlert('danger', 'Error al eliminar la solicitud');
      }
    },
    asignarSolicitud(solicitud) {
      this.$router.push({ 
        name: 'vertice-asignacion', 
        params: { solicitudId: solicitud.id } 
      });
    },
    // Método para cargar la información de asignación al editar una solicitud
    async editarSolicitud(solicitud) {
      this.solicitudAEditar = solicitud;
      this.formSolicitud = { ...solicitud };
      
      // Si la solicitud está procesada, cargar información de la asignación
      if (solicitud.estatus === 'procesada') {
        await this.cargarDatosAsignacion(solicitud.id);
        
        // Deshabilitar edición si ya hay una cita asignada
        if (this.solicitudAsignacion) {
          this.formEditableFields = false;
          this.showAlert('warning', 'No se pueden editar los datos mientras exista una cita asignada. Elimine la asignación primero si necesita modificar la información.');
        } else {
          this.formEditableFields = true;
        }
      } else {
        this.solicitudAsignacion = null;
        this.formEditableFields = true;
      }
      
      this.showEditModal = true;
    },
    
    // Método para cargar datos de la asignación
    async cargarDatosAsignacion(solicitudId) {
      try {
        this.isLoading = true;
        const response = await axios.get(`/lgm/api/vertice/obtener_asignacion.php?solicitud_id=${solicitudId}`);
        if (response.data && response.data.data) {
          this.solicitudAsignacion = response.data.data;
        } else {
          this.solicitudAsignacion = null;
        }
      } catch (error) {
        console.error('Error cargando datos de asignación:', error);
        this.showAlert('danger', 'Error al cargar los datos de la asignación');
        this.solicitudAsignacion = null;
      } finally {
        this.isLoading = false;
      }
    },
    
    // Método para formatear la hora
    formatTime(timeString) {
      if (!timeString) return 'No asignada';
      // Convertir a formato de hora local
      try {
        // Intentar formatear la hora si viene en formato HH:MM:SS
        const [hours, minutes] = timeString.split(':');
        return `${hours}:${minutes}`;
      } catch (error) {
        return timeString;
      }
    },
    
    // Método para confirmar eliminación de asignación
    confirmarEliminarAsignacion() {
      this.showDeleteAsignacionModal = true;
    },
    
    // Método para eliminar la asignación
    async eliminarAsignacion() {
      try {
        const response = await axios.post('/lgm/api/vertice/eliminar_asignacion.php', { 
          solicitud_id: this.solicitudAEditar.id 
        });
        
        this.showAlert('success', 'Asignación eliminada exitosamente');
        this.showDeleteAsignacionModal = false;
        
        // Actualizamos el estado de la solicitud a pendiente en el formulario
        this.formSolicitud.estatus = 'pendiente';
        this.solicitudAsignacion = null;
        this.formEditableFields = true;
        
        // Recargamos las solicitudes
        await this.loadSolicitudes();
      } catch (error) {
        console.error('Error eliminando asignación:', error);
        this.showAlert('danger', 'Error al eliminar la asignación');
      }
    },
    
    confirmarEliminar(solicitud) {
      this.solicitudAEliminar = solicitud;
      this.showDeleteModal = true;
    },
    cerrarModal() {
      this.showAddModal = false;
      this.showEditModal = false;
      this.solicitudAEditar = null;
      this.solicitudAsignacion = null;
      this.formSolicitud = this.getEmptySolicitud();
      this.formEditableFields = true;
    },
    getEmptySolicitud() {
      return {
        numero_id: '',
        cedula_titular: '',
        cedula_paciente: '',
        nombre_paciente: '',
        telefono: '',
        fecha_nacimiento: '',
        sexo: 'M',
        especialidad_requerida: ''
      };
    },
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES');
    },
    getStatusBadgeClass(status) {
      switch (status) {
        case 'pendiente':
          return 'bg-warning text-dark';
        case 'procesada':
          return 'bg-success text-white';
        case 'rechazada':
          return 'bg-danger text-white';
        default:
          return 'bg-secondary text-white';
      }
    },
    getStatusText(status) {
      switch (status) {
        case 'pendiente':
          return 'Pendiente';
        case 'procesada':
          return 'Procesada';
        case 'rechazada':
          return 'Rechazada';
        default:
          return 'Desconocido';
      }
    },
    showAlert(type, message) {
      this.alert = {
        show: true,
        type: type, // success, danger, warning, info
        message: message
      };
      
      // Auto-cerrar la alerta después de 5 segundos
      setTimeout(() => {
        this.closeAlert();
      }, 5000);
    },
    closeAlert() {
      this.alert.show = false;
    },
    aplicarFiltros() {
      // Los filtros se aplican automáticamente a través del computed property
      console.log('Filtros aplicados:', this.filtros);
    },
    limpiarFiltros() {
      this.filtros = {
        estatus: '',
        fecha: '',
        especialidad: ''
      };
    }
  }
};
</script>

<style scoped>
.solicitudes-container {
  padding: 20px;
}

.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.card {
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
  border: none;
  border-radius: 8px;
}

.card-header {
  background-color: #f8f9fa;
  padding: 12px 20px;
  font-weight: 500;
  border-radius: 8px 8px 0 0 !important;
}

.card-body {
  padding: 20px;
}

.table {
  margin-bottom: 0;
}

.table th {
  font-weight: 600;
  padding: 12px 10px;
}

.table td {
  padding: 12px 10px;
  vertical-align: middle;
}

.btn-group .btn {
  margin-right: 3px;
  border-radius: 4px;
}

.badge {
  padding: 6px 10px;
  border-radius: 4px;
  font-weight: 500;
}

.modal-lg {
  max-width: 800px;
}

.modal {
  display: none;
  position: fixed;
  z-index: 1050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.4);
}

.modal.show {
  display: block;
}

.modal-dialog {
  margin: 5% auto;
}

.modal-content {
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  border: none;
}

.modal-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  padding: 15px 20px;
  border-radius: 8px 8px 0 0;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  border-top: 1px solid #dee2e6;
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
  border-radius: 0 0 8px 8px;
}

.form-label {
  margin-bottom: 8px;
  font-weight: 500;
}

.form-control, .form-select {
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #ced4da;
}

.form-control:focus, .form-select:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.alert {
  border-radius: 6px;
}

.btn {
  border-radius: 6px;
  padding: 8px 16px;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 0.85rem;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}
</style>