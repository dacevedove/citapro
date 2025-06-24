<template>
  <div class="gestion-seguros-container">
    <div class="header-section">
      <div class="header-info">
        <h2>
          <i class="fas fa-shield-alt"></i>
          Seguros Médicos
        </h2>
        <p class="header-subtitle">
          Gestione los seguros médicos del paciente {{ pacienteNombre }}
        </p>
      </div>
      <div class="header-actions">
        <button @click="abrirModalNuevoSeguro" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Agregar Seguro
        </button>
      </div>
    </div>

    <!-- Lista de seguros -->
    <div v-if="cargando" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando seguros...</p>
    </div>

    <div v-else-if="seguros.length === 0" class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-shield-alt"></i>
      </div>
      <h3>No hay seguros registrados</h3>
      <p>Este paciente no tiene seguros médicos asociados.</p>
      <button @click="abrirModalNuevoSeguro" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        Agregar Primer Seguro
      </button>
    </div>

    <div v-else class="seguros-grid">
      <div 
        v-for="seguro in seguros" 
        :key="seguro.id" 
        class="seguro-card"
        :class="{
          'principal': seguro.tipo_cobertura === 'principal',
          'inactivo': seguro.estado !== 'activo',
          'vencido': seguro.estado_vigencia === 'vencido',
          'vence-hoy': seguro.estado_vigencia === 'vence_hoy'
        }"
      >
        <!-- Badge de tipo de cobertura -->
        <div class="cobertura-badge" :class="seguro.tipo_cobertura">
          {{ seguro.tipo_cobertura_texto }}
        </div>

        <!-- Información de la aseguradora -->
        <div class="seguro-header">
          <div class="aseguradora-info">
            <div class="aseguradora-logo">
              <i class="fas fa-building"></i>
            </div>
            <div class="aseguradora-details">
              <h4>{{ seguro.aseguradora_nombre }}</h4>
              <p class="poliza-numero">Póliza: {{ seguro.numero_poliza }}</p>
            </div>
          </div>
          <div class="estado-badge" :class="seguro.estado">
            {{ formatearEstado(seguro.estado) }}
          </div>
        </div>

        <!-- Información del seguro -->
        <div class="seguro-body">
          <div class="seguro-details">
            <div class="detail-item">
              <span class="detail-label">Vigencia:</span>
              <span class="detail-value" :class="getVigenciaClass(seguro)">
                {{ formatearVigencia(seguro) }}
              </span>
            </div>

            <div v-if="seguro.beneficiario_principal" class="detail-item">
              <span class="detail-label">Condición:</span>
              <span class="detail-value">
                {{ seguro.beneficiario_principal ? 'Beneficiario Principal' : 'Beneficiario' }}
              </span>
            </div>

            <div v-if="seguro.parentesco" class="detail-item">
              <span class="detail-label">Parentesco:</span>
              <span class="detail-value">{{ seguro.parentesco }}</span>
            </div>

            <div v-if="seguro.nombre_titular" class="detail-item">
              <span class="detail-label">Titular:</span>
              <span class="detail-value">{{ seguro.nombre_titular }}</span>
            </div>

            <div v-if="seguro.observaciones" class="detail-item observaciones">
              <span class="detail-label">Observaciones:</span>
              <span class="detail-value">{{ seguro.observaciones }}</span>
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="seguro-actions">
          <button 
            @click="editarSeguro(seguro)" 
            class="btn-action edit"
            title="Editar seguro"
          >
            <i class="fas fa-edit"></i>
          </button>
          
          <button 
            @click="verHistorial(seguro)" 
            class="btn-action history"
            title="Ver historial"
          >
            <i class="fas fa-history"></i>
          </button>

          <button 
            v-if="seguro.estado === 'activo'"
            @click="cambiarEstadoSeguro(seguro, 'inactivo')" 
            class="btn-action disable"
            title="Desactivar seguro"
          >
            <i class="fas fa-pause"></i>
          </button>

          <button 
            v-else
            @click="cambiarEstadoSeguro(seguro, 'activo')" 
            class="btn-action enable"
            title="Activar seguro"
          >
            <i class="fas fa-play"></i>
          </button>

          <button 
            @click="eliminarSeguro(seguro)" 
            class="btn-action delete"
            title="Eliminar seguro"
            v-if="puedeEliminar(seguro)"
          >
            <i class="fas fa-trash"></i>
          </button>
        </div>

        <!-- Indicador de vencimiento próximo -->
        <div v-if="seguro.dias_vencimiento <= 30 && seguro.dias_vencimiento > 0" class="vencimiento-alerta">
          <i class="fas fa-exclamation-triangle"></i>
          Vence en {{ seguro.dias_vencimiento }} día{{ seguro.dias_vencimiento !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar seguro -->
    <div v-if="mostrarModalSeguro" class="modal-overlay">
      <div class="modal-container large">
        <div class="modal-header">
          <h3>{{ editandoSeguro ? 'Editar Seguro' : 'Nuevo Seguro' }}</h3>
          <button @click="cerrarModalSeguro" class="close-btn">&times;</button>
        </div>

        <div class="modal-body">
          <form @submit.prevent="guardarSeguro">
            <div class="form-grid">
              <div class="form-group">
                <label for="aseguradora_id">Aseguradora *</label>
                <select 
                  id="aseguradora_id" 
                  v-model="formSeguro.aseguradora_id" 
                  class="form-control" 
                  required
                  :disabled="editandoSeguro"
                >
                  <option value="">Seleccione una aseguradora</option>
                  <option 
                    v-for="aseguradora in aseguradoras" 
                    :key="aseguradora.id" 
                    :value="aseguradora.id"
                  >
                    {{ aseguradora.nombre_comercial }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label for="numero_poliza">Número de Póliza *</label>
                <input 
                  type="text" 
                  id="numero_poliza" 
                  v-model="formSeguro.numero_poliza" 
                  class="form-control" 
                  placeholder="Ej: POL-123456789"
                  required
                />
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="tipo_cobertura">Tipo de Cobertura *</label>
                <select 
                  id="tipo_cobertura" 
                  v-model="formSeguro.tipo_cobertura" 
                  class="form-control" 
                  required
                >
                  <option value="principal">Principal</option>
                  <option value="secundario">Secundario</option>
                  <option value="complementario">Complementario</option>
                </select>
                <small class="form-help">
                  El seguro principal será usado por defecto para las citas
                </small>
              </div>

              <div class="form-group">
                <label for="estado">Estado *</label>
                <select 
                  id="estado" 
                  v-model="formSeguro.estado" 
                  class="form-control" 
                  required
                >
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
                  <option value="suspendido">Suspendido</option>
                </select>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="fecha_inicio">Fecha de Inicio *</label>
                <input 
                  type="date" 
                  id="fecha_inicio" 
                  v-model="formSeguro.fecha_inicio" 
                  class="form-control" 
                  required
                />
              </div>

              <div class="form-group">
                <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                <input 
                  type="date" 
                  id="fecha_vencimiento" 
                  v-model="formSeguro.fecha_vencimiento" 
                  class="form-control"
                />
                <small class="form-help">
                  Opcional. Dejar vacío si no tiene vencimiento
                </small>
              </div>
            </div>

            <div class="form-group">
              <label class="checkbox-label">
                <input 
                  type="checkbox" 
                  v-model="formSeguro.beneficiario_principal"
                />
                <span class="checkbox-custom"></span>
                Es beneficiario principal de esta póliza
              </label>
            </div>

            <div v-if="!formSeguro.beneficiario_principal" class="form-grid">
              <div class="form-group">
                <label for="parentesco">Parentesco</label>
                <select 
                  id="parentesco" 
                  v-model="formSeguro.parentesco" 
                  class="form-control"
                >
                  <option value="">Seleccione el parentesco</option>
                  <option value="Cónyuge">Cónyuge</option>
                  <option value="Hijo/a">Hijo/a</option>
                  <option value="Padre">Padre</option>
                  <option value="Madre">Madre</option>
                  <option value="Hermano/a">Hermano/a</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>

              <div class="form-group">
                <label for="cedula_titular">Cédula del Titular</label>
                <input 
                  type="text" 
                  id="cedula_titular" 
                  v-model="formSeguro.cedula_titular" 
                  class="form-control" 
                  placeholder="V-12345678"
                />
              </div>
            </div>

            <div v-if="!formSeguro.beneficiario_principal" class="form-group">
              <label for="nombre_titular">Nombre del Titular</label>
              <input 
                type="text" 
                id="nombre_titular" 
                v-model="formSeguro.nombre_titular" 
                class="form-control" 
                placeholder="Nombre completo del titular"
              />
            </div>

            <div class="form-group">
              <label for="observaciones">Observaciones</label>
              <textarea 
                id="observaciones" 
                v-model="formSeguro.observaciones" 
                class="form-control" 
                rows="3"
                placeholder="Notas adicionales sobre este seguro..."
              ></textarea>
            </div>

            <div class="modal-footer">
              <button type="button" @click="cerrarModalSeguro" class="btn btn-outline">
                Cancelar
              </button>
              <button type="submit" class="btn btn-primary" :disabled="guardando">
                <i class="fas fa-spinner fa-spin" v-if="guardando"></i>
                <i class="fas fa-save" v-else></i>
                {{ guardando ? 'Guardando...' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal para historial -->
    <div v-if="mostrarModalHistorial" class="modal-overlay">
      <div class="modal-container medium">
        <div class="modal-header">
          <h3>Historial del Seguro</h3>
          <button @click="cerrarModalHistorial" class="close-btn">&times;</button>
        </div>

        <div class="modal-body">
          <div v-if="cargandoHistorial" class="loading-mini">
            <div class="spinner-mini"></div>
            <span>Cargando historial...</span>
          </div>

          <div v-else-if="historial.length === 0" class="empty-historial">
            <p>No hay cambios registrados para este seguro.</p>
          </div>

          <div v-else class="historial-timeline">
            <div 
              v-for="registro in historial" 
              :key="registro.id" 
              class="timeline-item"
            >
              <div class="timeline-marker" :class="registro.accion">
                <i :class="getIconoAccion(registro.accion)"></i>
              </div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <h4>{{ formatearAccion(registro.accion) }}</h4>
                  <span class="timeline-date">
                    {{ formatearFecha(registro.fecha_accion) }}
                  </span>
                </div>
                <div class="timeline-details">
                  <p v-if="registro.observaciones">{{ registro.observaciones }}</p>
                  <div v-if="registro.estado_anterior && registro.estado_nuevo" class="estado-cambio">
                    <span class="estado-badge" :class="registro.estado_anterior">
                      {{ formatearEstado(registro.estado_anterior) }}
                    </span>
                    <i class="fas fa-arrow-right"></i>
                    <span class="estado-badge" :class="registro.estado_nuevo">
                      {{ formatearEstado(registro.estado_nuevo) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="cerrarModalHistorial" class="btn btn-primary">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'GestionSeguros',
  props: {
    pacienteId: {
      type: [String, Number],
      required: true
    },
    pacienteNombre: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      seguros: [],
      aseguradoras: [],
      historial: [],
      
      // Estados
      cargando: false,
      guardando: false,
      cargandoHistorial: false,
      
      // Modales
      mostrarModalSeguro: false,
      mostrarModalHistorial: false,
      editandoSeguro: false,
      
      // Formulario
      formSeguro: {
        aseguradora_id: '',
        numero_poliza: '',
        tipo_cobertura: 'principal',
        estado: 'activo',
        fecha_inicio: '',
        fecha_vencimiento: '',
        beneficiario_principal: true,
        parentesco: '',
        cedula_titular: '',
        nombre_titular: '',
        observaciones: ''
      },
      
      seguroSeleccionado: null
    };
  },
  
  mounted() {
    this.cargarDatos();
  },
  
  methods: {
    async cargarDatos() {
      await Promise.all([
        this.cargarSeguros(),
        this.cargarAseguradoras()
      ]);
    },
    
    async cargarSeguros() {
      this.cargando = true;
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/pacientes/seguros/listar.php?paciente_id=${this.pacienteId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.seguros = response.data;
      } catch (error) {
        console.error('Error al cargar seguros:', error);
        this.$emit('error', 'Error al cargar los seguros del paciente');
      } finally {
        this.cargando = false;
      }
    },
    
    async cargarAseguradoras() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/aseguradoras/listar.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.aseguradoras = response.data;
      } catch (error) {
        console.error('Error al cargar aseguradoras:', error);
      }
    },
    
    abrirModalNuevoSeguro() {
      this.formSeguro = {
        aseguradora_id: '',
        numero_poliza: '',
        tipo_cobertura: 'principal',
        estado: 'activo',
        fecha_inicio: new Date().toISOString().split('T')[0],
        fecha_vencimiento: '',
        beneficiario_principal: true,
        parentesco: '',
        cedula_titular: '',
        nombre_titular: '',
        observaciones: ''
      };
      this.editandoSeguro = false;
      this.mostrarModalSeguro = true;
    },
    
    editarSeguro(seguro) {
      this.formSeguro = {
        id: seguro.id,
        aseguradora_id: seguro.aseguradora_id,
        numero_poliza: seguro.numero_poliza,
        tipo_cobertura: seguro.tipo_cobertura,
        estado: seguro.estado,
        fecha_inicio: seguro.fecha_inicio,
        fecha_vencimiento: seguro.fecha_vencimiento || '',
        beneficiario_principal: Boolean(seguro.beneficiario_principal),
        parentesco: seguro.parentesco || '',
        cedula_titular: seguro.cedula_titular || '',
        nombre_titular: seguro.nombre_titular || '',
        observaciones: seguro.observaciones || ''
      };
      this.editandoSeguro = true;
      this.mostrarModalSeguro = true;
    },
    
    async guardarSeguro() {
      this.guardando = true;
      try {
        const token = localStorage.getItem('token');
        const payload = {
          ...this.formSeguro,
          paciente_id: this.pacienteId
        };
        
        if (this.editandoSeguro) {
          await axios.put('/api/pacientes/seguros/actualizar.php', payload, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          this.$emit('success', 'Seguro actualizado correctamente');
        } else {
          // Usar endpoint de debug temporalmente
          await axios.post('/api/pacientes/seguros/debug-crear.php', payload, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          this.$emit('success', 'Seguro agregado correctamente');
        }
        
        this.cerrarModalSeguro();
        await this.cargarSeguros();
        
      } catch (error) {
        console.error('Error al guardar seguro:', error);
        this.$emit('error', error.response?.data?.error || 'Error al guardar el seguro');
      } finally {
        this.guardando = false;
      }
    },
    
    async cambiarEstadoSeguro(seguro, nuevoEstado) {
      const accion = nuevoEstado === 'activo' ? 'activar' : 'desactivar';
      
      if (!confirm(`¿Está seguro de que desea ${accion} este seguro?`)) {
        return;
      }
      
      try {
        const token = localStorage.getItem('token');
        await axios.put('/api/pacientes/seguros/actualizar.php', {
          id: seguro.id,
          estado: nuevoEstado
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.$emit('success', `Seguro ${accion === 'activar' ? 'activado' : 'desactivado'} correctamente`);
        await this.cargarSeguros();
        
      } catch (error) {
        console.error('Error al cambiar estado:', error);
        this.$emit('error', error.response?.data?.error || 'Error al cambiar el estado del seguro');
      }
    },
    
    async eliminarSeguro(seguro) {
      if (!confirm('¿Está seguro de que desea eliminar este seguro? Esta acción no se puede deshacer.')) {
        return;
      }
      
      try {
        const token = localStorage.getItem('token');
        await axios.delete('/api/pacientes/seguros/eliminar.php', {
          headers: { 'Authorization': `Bearer ${token}` },
          data: { id: seguro.id }
        });
        
        this.$emit('success', 'Seguro eliminado correctamente');
        await this.cargarSeguros();
        
      } catch (error) {
        console.error('Error al eliminar seguro:', error);
        this.$emit('error', error.response?.data?.error || 'Error al eliminar el seguro');
      }
    },
    
    async verHistorial(seguro) {
      this.seguroSeleccionado = seguro;
      this.mostrarModalHistorial = true;
      await this.cargarHistorial(seguro.id);
    },
    
    async cargarHistorial(seguroId) {
      this.cargandoHistorial = true;
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/pacientes/seguros/historial.php?seguro_id=${seguroId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.historial = response.data;
      } catch (error) {
        console.error('Error al cargar historial:', error);
        this.historial = [];
      } finally {
        this.cargandoHistorial = false;
      }
    },
    
    cerrarModalSeguro() {
      this.mostrarModalSeguro = false;
      this.editandoSeguro = false;
    },
    
    cerrarModalHistorial() {
      this.mostrarModalHistorial = false;
      this.seguroSeleccionado = null;
      this.historial = [];
    },
    
    // Métodos de formateo
    formatearEstado(estado) {
      const estados = {
        'activo': 'Activo',
        'inactivo': 'Inactivo',
        'suspendido': 'Suspendido'
      };
      return estados[estado] || estado;
    },
    
    formatearVigencia(seguro) {
      if (!seguro.fecha_vencimiento) {
        return 'Sin vencimiento';
      }
      
      const fechaVencimiento = new Date(seguro.fecha_vencimiento);
      const opciones = { year: 'numeric', month: 'short', day: 'numeric' };
      
      if (seguro.estado_vigencia === 'vencido') {
        return `Vencido el ${fechaVencimiento.toLocaleDateString('es-ES', opciones)}`;
      } else if (seguro.estado_vigencia === 'vence_hoy') {
        return 'Vence hoy';
      } else {
        return `Hasta ${fechaVencimiento.toLocaleDateString('es-ES', opciones)}`;
      }
    },
    
    getVigenciaClass(seguro) {
      return {
        'vigente': seguro.estado_vigencia === 'vigente',
        'vencido': seguro.estado_vigencia === 'vencido',
        'vence-hoy': seguro.estado_vigencia === 'vence_hoy'
      };
    },
    
    formatearFecha(fecha) {
      return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    formatearAccion(accion) {
      const acciones = {
        'activacion': 'Activación',
        'desactivacion': 'Desactivación',
        'renovacion': 'Renovación',
        'modificacion': 'Modificación',
        'suspension': 'Suspensión'
      };
      return acciones[accion] || accion;
    },
    
    getIconoAccion(accion) {
      const iconos = {
        'activacion': 'fas fa-play-circle',
        'desactivacion': 'fas fa-pause-circle',
        'renovacion': 'fas fa-sync-alt',
        'modificacion': 'fas fa-edit',
        'suspension': 'fas fa-ban'
      };
      return iconos[accion] || 'fas fa-circle';
    },
    
    puedeEliminar(seguro) {
      // Solo permitir eliminar seguros inactivos sin citas asociadas
      return seguro.estado === 'inactivo';
    }
  }
};
</script>

<style scoped>
.gestion-seguros-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.header-info h2 {
  margin: 0 0 4px 0;
  color: #343a40;
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.header-subtitle {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}

.seguros-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 20px;
  padding: 24px;
}

.seguro-card {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  background: white;
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}

.seguro-card:hover {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.seguro-card.principal {
  border-color: #007bff;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
}

.seguro-card.inactivo {
  opacity: 0.7;
  background: #f8f9fa;
}

.seguro-card.vencido {
  border-color: #dc3545;
  background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
}

.seguro-card.vence-hoy {
  border-color: #ffc107;
  background: linear-gradient(135deg, #ffffff 0%, #fffcf0 100%);
}

.cobertura-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cobertura-badge.principal {
  background: #007bff;
  color: white;
}

.cobertura-badge.secundario {
  background: #6c757d;
  color: white;
}

.cobertura-badge.complementario {
  background: #17a2b8;
  color: white;
}

.seguro-header {
  padding: 20px 20px 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.aseguradora-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.aseguradora-logo {
  width: 48px;
  height: 48px;
  background: #f8f9fa;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 20px;
}

.aseguradora-details h4 {
  margin: 0 0 4px 0;
  color: #343a40;
  font-size: 16px;
  font-weight: 600;
}

.poliza-numero {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
  font-family: monospace;
}

.estado-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
}

.estado-badge.activo {
  background: #d4edda;
  color: #155724;
}

.estado-badge.inactivo {
  background: #f8d7da;
  color: #721c24;
}

.estado-badge.suspendido {
  background: #fff3cd;
  color: #856404;
}

.seguro-body {
  padding: 0 20px 16px 20px;
}

.seguro-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  font-size: 14px;
  line-height: 1.4;
}

.detail-item.observaciones {
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.detail-label {
  color: #6c757d;
  font-weight: 500;
  min-width: 100px;
}

.detail-value {
  color: #343a40;
  text-align: right;
  flex: 1;
}

.detail-value.vigente {
  color: #28a745;
}

.detail-value.vencido {
  color: #dc3545;
  font-weight: 600;
}

.detail-value.vence-hoy {
  color: #ffc107;
  font-weight: 600;
}

.seguro-actions {
  padding: 16px 20px;
  background: #f8f9fa;
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  border-top: 1px solid #e9ecef;
}

.btn-action {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.btn-action.edit {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-action.edit:hover {
  background: #1976d2;
  color: white;
}

.btn-action.history {
  background: #f3e5f5;
  color: #7b1fa2;
}

.btn-action.history:hover {
  background: #7b1fa2;
  color: white;
}

.btn-action.disable {
  background: #fff3e0;
  color: #f57c00;
}

.btn-action.disable:hover {
  background: #f57c00;
  color: white;
}

.btn-action.enable {
  background: #e8f5e8;
  color: #2e7d32;
}

.btn-action.enable:hover {
  background: #2e7d32;
  color: white;
}

.btn-action.delete {
  background: #ffebee;
  color: #c62828;
}

.btn-action.delete:hover {
  background: #c62828;
  color: white;
}

.vencimiento-alerta {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: #ffc107;
  color: #212529;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Estados de carga y vacío */
.loading-container, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state .empty-icon {
  font-size: 48px;
  color: #6c757d;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: #343a40;
  font-size: 18px;
}

.empty-state p {
  margin: 0 0 20px 0;
  color: #6c757d;
}

/* Estilos de modales */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-container.large {
  max-width: 700px;
}

.modal-container.medium {
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  color: #343a40;
  font-size: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6c757d;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.close-btn:hover {
  background: #f8f9fa;
}

.modal-body {
  padding: 24px;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Formularios */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #343a40;
  font-size: 14px;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-help {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #6c757d;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
  display: none;
}

.checkbox-custom {
  width: 18px;
  height: 18px;
  border: 2px solid #ced4da;
  border-radius: 3px;
  position: relative;
  transition: all 0.2s;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
  background: #007bff;
  border-color: #007bff;
}

.checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
  content: '';
  position: absolute;
  top: 1px;
  left: 5px;
  width: 6px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

/* Botones */
.btn {
  padding: 10px 16px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  text-decoration: none;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn-outline {
  background: transparent;
  border: 1px solid #ced4da;
  color: #495057;
}

.btn-outline:hover:not(:disabled) {
  background: #e9ecef;
  border-color: #adb5bd;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Historial */
.loading-mini {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  color: #6c757d;
}

.spinner-mini {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.empty-historial {
  padding: 40px;
  text-align: center;
  color: #6c757d;
}

.historial-timeline {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.timeline-item {
  display: flex;
  gap: 16px;
  position: relative;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 15px;
  top: 32px;
  bottom: -20px;
  width: 2px;
  background: #e9ecef;
}

.timeline-marker {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.timeline-marker.activacion {
  background: #d4edda;
  color: #155724;
}

.timeline-marker.desactivacion {
  background: #f8d7da;
  color: #721c24;
}

.timeline-marker.modificacion {
  background: #e3f2fd;
  color: #1976d2;
}

.timeline-marker.renovacion {
  background: #e8f5e8;
  color: #2e7d32;
}

.timeline-marker.suspension {
  background: #fff3cd;
  color: #856404;
}

.timeline-content {
  flex: 1;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.timeline-header h4 {
  margin: 0;
  color: #343a40;
  font-size: 16px;
}

.timeline-date {
  font-size: 12px;
  color: #6c757d;
}

.timeline-details p {
  margin: 0 0 8px 0;
  color: #495057;
  font-size: 14px;
}

.estado-cambio {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }

  .seguros-grid {
    grid-template-columns: 1fr;
    padding: 16px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .modal-container {
    margin: 10px;
  }

  .modal-header {
    padding: 16px;
  }

  .modal-body {
    padding: 16px;
  }

  .modal-footer {
    padding: 12px 16px;
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }

  .seguro-card {
    min-width: unset;
  }

  .detail-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
  }

  .detail-value {
    text-align: left;
  }
}

@media (max-width: 480px) {
  .aseguradora-info {
    flex-direction: column;
    text-align: center;
    gap: 8px;
  }

  .seguro-header {
    flex-direction: column;
    gap: 12px;
  }

  .seguro-actions {
    justify-content: center;
  }
}
</style>