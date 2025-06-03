<template>
    <div class="notificaciones-container">
      <h2 class="mb-4">Historial de Notificaciones</h2>
      
      <!-- Filtros -->
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
              <label for="filtro-estado" class="form-label">Estado:</label>
              <select id="filtro-estado" class="form-select" v-model="filtros.estado" @change="cargarNotificaciones">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="enviada">Enviada</option>
                <option value="fallida">Fallida</option>
                <option value="cancelada">Cancelada</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="filtro-tipo" class="form-label">Tipo:</label>
              <select id="filtro-tipo" class="form-select" v-model="filtros.tipo" @change="cargarNotificaciones">
                <option value="">Todos</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="email">Email</option>
                <option value="sms">SMS</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label for="filtro-fecha" class="form-label">Fecha:</label>
              <input type="date" id="filtro-fecha" class="form-control" v-model="filtros.fecha" @change="cargarNotificaciones">
            </div>
          </div>
        </div>
      </div>
      
      <!-- Alerta para mensajes -->
      <div v-if="alert.show" :class="`alert alert-${alert.type} alert-dismissible fade show`" role="alert">
        {{ alert.message }}
        <button type="button" class="btn-close" @click="closeAlert" aria-label="Close"></button>
      </div>
      
      <!-- Tabla de Notificaciones -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Listado de Notificaciones</span>
          <button class="btn btn-sm btn-outline-primary" @click="cargarNotificaciones">
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
                  <th>Tipo</th>
                  <th>Paciente</th>
                  <th>Contacto</th>
                  <th>Cita</th>
                  <th>Estado</th>
                  <th>Intentos</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="notificacion in notificaciones" :key="notificacion.id" 
                    :class="{ 
                      'table-success': notificacion.estado === 'enviada',
                      'table-danger': notificacion.estado === 'fallida',
                      'table-warning': notificacion.estado === 'pendiente'
                    }">
                  <td>{{ notificacion.id }}</td>
                  <td>{{ formatDateTime(notificacion.fecha_envio) }}</td>
                  <td>
                    <span class="badge" :class="getBadgeClassForTipo(notificacion.tipo)">
                      {{ getTipoText(notificacion.tipo) }}
                    </span>
                  </td>
                  <td>{{ notificacion.paciente.nombre }}</td>
                  <td>
                    <div v-if="notificacion.tipo === 'whatsapp' || notificacion.tipo === 'sms'">
                      {{ formatTelefono(notificacion.paciente.telefono) }}
                    </div>
                    <div v-else-if="notificacion.tipo === 'email'">
                      {{ notificacion.paciente.email }}
                    </div>
                  </td>
                  <td>
                    <div v-if="notificacion.cita.id">
                      Dr. {{ notificacion.cita.doctor.nombre }} <br>
                      <small>{{ formatDate(notificacion.cita.fecha) }} - {{ formatTime(notificacion.cita.hora) }}</small>
                    </div>
                    <div v-else>No disponible</div>
                  </td>
                  <td>
                    <span class="badge" :class="getBadgeClassForEstado(notificacion.estado)">
                      {{ getEstadoText(notificacion.estado) }}
                    </span>
                  </td>
                  <td class="text-center">{{ notificacion.intentos }}</td>
                  <td class="text-center">
                    <div class="btn-group">
                      <button 
                        class="btn btn-sm btn-info" 
                        @click="verDetalles(notificacion)"
                        title="Ver detalles"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        v-if="notificacion.estado === 'fallida' || notificacion.estado === 'pendiente'"
                        class="btn btn-sm btn-success" 
                        @click="reenviar(notificacion)"
                        title="Reintentar envío"
                      >
                        <i class="fas fa-redo"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="notificaciones.length === 0">
                  <td colspan="9" class="text-center">No hay notificaciones que coincidan con los criterios de búsqueda</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- Modal de detalles -->
      <div class="modal" :class="{ 'show': showDetallesModal }" v-if="showDetallesModal">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Detalles de la Notificación</h5>
              <button type="button" class="close" @click="showDetallesModal = false">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body" v-if="notificacionSeleccionada">
              <div class="row mb-3">
                <div class="col-md-6">
                  <h6>Información del envío</h6>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">ID</h6>
                      <span>{{ notificacionSeleccionada.id }}</span>
                    </div>
                  </div>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Tipo</h6>
                      <span>{{ getTipoText(notificacionSeleccionada.tipo) }}</span>
                    </div>
                  </div>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Estado</h6>
                      <span :class="getBadgeClassForEstado(notificacionSeleccionada.estado)">
                        {{ getEstadoText(notificacionSeleccionada.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Fecha de envío</h6>
                      <span>{{ formatDateTime(notificacionSeleccionada.fecha_envio) }}</span>
                    </div>
                  </div>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Intentos</h6>
                      <span>{{ notificacionSeleccionada.intentos }}</span>
                    </div>
                  </div>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Creado por</h6>
                      <span>{{ notificacionSeleccionada.creado_por.nombre }}</span>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6">
                  <h6>Información del destinatario y cita</h6>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Paciente</h6>
                      <span>{{ notificacionSeleccionada.paciente.nombre }}</span>
                    </div>
                  </div>
                  <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Contacto</h6>
                      <span>{{ formatTelefono(notificacionSeleccionada.paciente.telefono) }}</span>
                    </div>
                  </div>
                  <div class="list-group-item" v-if="notificacionSeleccionada.cita.id">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">ID Cita</h6>
                      <span>{{ notificacionSeleccionada.cita.id }}</span>
                    </div>
                  </div>
                  <div class="list-group-item" v-if="notificacionSeleccionada.cita.id">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Doctor</h6>
                      <span>{{ notificacionSeleccionada.cita.doctor.nombre }}</span>
                    </div>
                  </div>
                  <div class="list-group-item" v-if="notificacionSeleccionada.cita.id">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Especialidad</h6>
                      <span>{{ notificacionSeleccionada.cita.especialidad }}</span>
                    </div>
                  </div>
                  <div class="list-group-item" v-if="notificacionSeleccionada.cita.id">
                    <div class="d-flex w-100 justify-content-between">
                      <h6 class="mb-1">Fecha y hora</h6>
                      <span>{{ formatDate(notificacionSeleccionada.cita.fecha) }} {{ formatTime(notificacionSeleccionada.cita.hora) }}</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-12">
                  <h6>Mensaje enviado</h6>
                  <div class="card">
                    <div class="card-body">
                      <div v-if="mensajeFormateado" class="mensaje-preview">
                        <div class="whatsapp-preview">
                          <div class="whatsapp-header">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                          </div>
                          <div class="whatsapp-message">
                            Hola <strong>{{ mensajeFormateado.nombre_paciente }}</strong>, su cita médica ha sido confirmada:
                            <br><br>
                            <strong>Doctor:</strong> Dr. {{ mensajeFormateado.nombre_doctor }}<br>
                            <strong>Especialidad:</strong> {{ mensajeFormateado.especialidad }}<br>
                            <strong>Fecha:</strong> {{ mensajeFormateado.fecha }}<br>
                            <strong>Hora:</strong> {{ mensajeFormateado.hora }}<br>
                            <strong>Consultorio:</strong> {{ mensajeFormateado.consultorio }}
                            <br><br>
                            Por favor, confirme su asistencia respondiendo "SI" a este mensaje.
                            <br><br>
                            Si necesita cancelar o reprogramar, por favor contáctenos al 123-456-7890.
                            <br><br>
                            Le recordamos llegar 15 minutos antes de su hora programada con su documento de identidad.
                            <br><br>
                            ¡Gracias por confiar en LGM Clinic!
                          </div>
                        </div>
                      </div>
                      <pre v-else class="mensaje-raw">{{ notificacionSeleccionada.mensaje }}</pre>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="row" v-if="notificacionSeleccionada.respuesta_api">
                <div class="col-12">
                  <h6>Respuesta de la API</h6>
                  <div class="card">
                    <div class="card-body">
                      <pre class="api-response">{{ formatJSON(notificacionSeleccionada.respuesta_api) }}</pre>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button 
                v-if="notificacionSeleccionada && (notificacionSeleccionada.estado === 'fallida' || notificacionSeleccionada.estado === 'pendiente')"
                class="btn btn-success" 
                @click="reenviar(notificacionSeleccionada)"
              >
                <i class="fas fa-redo"></i> Reintentar envío
              </button>
              <button type="button" class="btn btn-secondary" @click="showDetallesModal = false">
                Cerrar
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
    name: 'NotificacionesHistorial',
    data() {
      return {
        notificaciones: [],
        isLoading: true,
        showDetallesModal: false,
        notificacionSeleccionada: null,
        mensajeFormateado: null,
        filtros: {
          estado: '',
          tipo: '',
          fecha: ''
        },
        alert: {
          show: false,
          type: 'success',
          message: ''
        }
      };
    },
    created() {
      this.cargarNotificaciones();
    },
    methods: {
      async cargarNotificaciones() {
        this.isLoading = true;
        
        try {
          // Construir URL con parámetros de filtro
          let url = '/lgm/api/notificaciones/listar_notificaciones.php';
          const params = new URLSearchParams();
          
          if (this.filtros.estado) {
            params.append('estado', this.filtros.estado);
          }
          
          if (this.filtros.tipo) {
            params.append('tipo', this.filtros.tipo);
          }
          
          if (this.filtros.fecha) {
            params.append('fecha', this.filtros.fecha);
          }
          
          if (params.toString()) {
            url += '?' + params.toString();
          }
          
          const response = await axios.get(url);
          this.notificaciones = response.data.data || [];
          
        } catch (error) {
          console.error('Error cargando notificaciones:', error);
          this.showAlert('danger', 'Error al cargar las notificaciones');
        } finally {
          this.isLoading = false;
        }
      },
      verDetalles(notificacion) {
        this.notificacionSeleccionada = notificacion;
        this.procesarMensaje(notificacion);
        this.showDetallesModal = true;
      },
      procesarMensaje(notificacion) {
        // Intentar procesar el mensaje para mostrar el formato WhatsApp
        try {
          if (notificacion.tipo === 'whatsapp' && notificacion.mensaje) {
            const mensajeData = JSON.parse(notificacion.mensaje);
            
            // Formato esperado para WhatsApp
            if (Array.isArray(mensajeData) && mensajeData.length >= 6) {
              this.mensajeFormateado = {
                nombre_paciente: mensajeData[0]?.text || '',
                nombre_doctor: mensajeData[1]?.text || '',
                especialidad: mensajeData[2]?.text || '',
                fecha: mensajeData[3]?.text || '',
                hora: mensajeData[4]?.text || '',
                consultorio: mensajeData[5]?.text || ''
              };
              return;
            }
          }
          
          // Si no se pudo procesar, mostrar el mensaje raw
          this.mensajeFormateado = null;
        } catch (error) {
          console.error('Error procesando mensaje:', error);
          this.mensajeFormateado = null;
        }
      },
      async reenviar(notificacion) {
        try {
          this.showAlert('info', 'Reintentando envío de notificación...');
          
          // Solicitar reenvío al mismo endpoint
          const response = await axios.post('/lgm/api/notificaciones/whatsapp_notificacion.php', {
            asignacion_id: notificacion.asignacion_id,
            cita_id: notificacion.cita.id
          });
          
          if (response.data.success) {
            this.showAlert('success', 'Notificación reenviada exitosamente');
            // Cerrar modal si está abierto
            this.showDetallesModal = false;
            // Recargar notificaciones
            this.cargarNotificaciones();
          } else {
            this.showAlert('danger', 'Error al reenviar la notificación: ' + response.data.message);
          }
          
        } catch (error) {
          console.error('Error reenviando notificación:', error);
          this.showAlert('danger', 'Error al reenviar la notificación');
        }
      },
      formatDateTime(dateTimeString) {
        if (!dateTimeString) return '';
        const date = new Date(dateTimeString);
        return date.toLocaleString('es-ES');
      },
      formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES');
      },
      formatTime(timeString) {
        if (!timeString) return '';
        
        // Convertir formato 24h a 12h con am/pm
        const [hours, minutes] = timeString.split(':');
        const h = parseInt(hours);
        const ampm = h >= 12 ? 'pm' : 'am';
        const h12 = h % 12 || 12; // Convertir 0 a 12
        
        return `${h12}:${minutes} ${ampm}`;
      },
      formatTelefono(telefono) {
        if (!telefono) return '-';
        
        // Formato básico para teléfonos de Panamá
        const cleaned = telefono.replace(/\D/g, '');
        
        if (cleaned.length === 8) {
          return cleaned.replace(/(\d{4})(\d{4})/, '$1-$2');
        } else if (cleaned.length > 8) {
          // Con código de país 
          return '+' + cleaned;
        }
        
        return telefono;
      },
      formatJSON(json) {
        try {
          if (typeof json === 'string') {
            json = JSON.parse(json);
          }
          return JSON.stringify(json, null, 2);
        } catch (e) {
          return json;
        }
      },
      getBadgeClassForTipo(tipo) {
        switch (tipo) {
          case 'whatsapp':
            return 'bg-success';
          case 'email':
            return 'bg-primary';
          case 'sms':
            return 'bg-info';
          default:
            return 'bg-secondary';
        }
      },
      getBadgeClassForEstado(estado) {
        switch (estado) {
          case 'enviada':
            return 'bg-success';
          case 'pendiente':
            return 'bg-warning text-dark';
          case 'fallida':
            return 'bg-danger';
          case 'cancelada':
            return 'bg-secondary';
          default:
            return 'bg-secondary';
        }
      },
      getTipoText(tipo) {
        switch (tipo) {
          case 'whatsapp':
            return 'WhatsApp';
          case 'email':
            return 'Email';
          case 'sms':
            return 'SMS';
          default:
            return tipo;
        }
      },
      getEstadoText(estado) {
        switch (estado) {
          case 'enviada':
            return 'Enviada';
          case 'pendiente':
            return 'Pendiente';
          case 'fallida':
            return 'Fallida';
          case 'cancelada':
            return 'Cancelada';
          default:
            return estado;
        }
      },
      limpiarFiltros() {
        this.filtros = {
          estado: '',
          tipo: '',
          fecha: ''
        };
        this.cargarNotificaciones();
      },
      showAlert(type, message) {
        this.alert = {
          show: true,
          type,
          message
        };
        
        setTimeout(() => {
          this.closeAlert();
        }, 5000);
      },
      closeAlert() {
        this.alert.show = false;
      }
    }
  };
  </script>
  
  <style scoped>
  .notificaciones-container {
    padding: 20px;
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
  
  .badge {
    padding: 6px 10px;
    border-radius: 4px;
    font-weight: 500;
  }
  
  .btn-group .btn {
    margin-right: 3px;
    border-radius: 4px;
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
    border-radius: 0 0 8px 8px;
  }
  
  .list-group-item {
    border-left: none;
    border-right: none;
    padding: 10px 0;
  }
  
  .list-group-item:first-child {
    border-top: none;
  }
  
  .mensaje-raw, .api-response {
    background-color: #f7f7f7;
    padding: 15px;
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
    font-size: 0.85rem;
    white-space: pre-wrap;
  }
  
  .whatsapp-preview {
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    overflow: hidden;
    max-width: 700px;
    margin: 0 auto;
  }
  
  .whatsapp-header {
    background-color: #075e54;
    color: white;
    padding: 10px 15px;
    font-weight: bold;
  }
  
  .whatsapp-header i {
    margin-right: 8px;
  }
  
  .whatsapp-message {
    padding: 15px;
    background-color: #ece5dd;
    white-space: pre-line;
    font-size: 0.9rem;
    line-height: 1.4;
  }
  </style>