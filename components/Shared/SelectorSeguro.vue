<template>
  <div class="selector-seguro">
    <div class="selector-header">
      <h4>
        <i class="fas fa-shield-alt"></i>
        Seleccionar Seguro Médico
      </h4>
      <p class="selector-subtitle">
        Elija el seguro que utilizará para esta cita médica
      </p>
    </div>

    <div v-if="cargando" class="loading-state">
      <div class="spinner-small"></div>
      <span>Cargando seguros del paciente...</span>
    </div>

    <div v-else-if="seguros.length === 0" class="no-seguros">
      <div class="no-seguros-icon">
        <i class="fas fa-shield-alt"></i>
      </div>
      <h5>Sin seguros registrados</h5>
      <p>Este paciente no tiene seguros médicos activos. La cita se creará como paciente particular.</p>
      <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <span>Puede agregar seguros desde la gestión del paciente después de crear la cita.</span>
      </div>
    </div>

    <div v-else class="seguros-list">
      <!-- Opción para paciente particular -->
      <label class="seguro-option particular" :class="{ active: seguroSeleccionado === null }">
        <input 
          type="radio" 
          :value="null" 
          v-model="seguroSeleccionado"
          @change="emitirCambio"
        />
        <div class="option-content">
          <div class="option-header">
            <div class="option-icon particular">
              <i class="fas fa-user"></i>
            </div>
            <div class="option-info">
              <h5>Paciente Particular</h5>
              <p>Sin seguro médico</p>
            </div>
            <div class="option-badge particular">
              Particular
            </div>
          </div>
          <div class="option-description">
            La cita se procesará como paciente particular, sin cobertura de seguro.
          </div>
        </div>
      </label>

      <!-- Opciones de seguros disponibles -->
      <label 
        v-for="seguro in seguros" 
        :key="seguro.id" 
        class="seguro-option"
        :class="{ 
          active: seguroSeleccionado === seguro.id,
          principal: seguro.tipo_cobertura === 'principal',
          vencido: seguro.estado_vigencia === 'vencido' || seguro.estado !== 'activo'
        }"
      >
        <input 
          type="radio" 
          :value="seguro.id" 
          v-model="seguroSeleccionado"
          @change="emitirCambio"
          :disabled="seguro.estado_vigencia === 'vencido' || seguro.estado !== 'activo'"
        />
        
        <div class="option-content">
          <div class="option-header">
            <div class="option-icon" :class="seguro.tipo_cobertura">
              <i class="fas fa-building"></i>
            </div>
            <div class="option-info">
              <h5>{{ seguro.aseguradora_nombre }}</h5>
              <p class="poliza">Póliza: {{ seguro.numero_poliza }}</p>
            </div>
            <div class="option-badges">
              <span class="option-badge" :class="seguro.tipo_cobertura">
                {{ seguro.tipo_cobertura_texto }}
              </span>
              <span v-if="seguro.estado !== 'activo'" class="option-badge estado" :class="seguro.estado">
                {{ formatearEstado(seguro.estado) }}
              </span>
            </div>
          </div>
          
          <div class="option-details">
            <div class="detail-row">
              <span class="detail-label">Vigencia:</span>
              <span class="detail-value" :class="getVigenciaClass(seguro)">
                {{ formatearVigencia(seguro) }}
              </span>
            </div>
            
            <div v-if="seguro.beneficiario_principal" class="detail-row">
              <span class="detail-label">Condición:</span>
              <span class="detail-value">
                {{ seguro.beneficiario_principal ? 'Beneficiario Principal' : 'Beneficiario' }}
              </span>
            </div>
            
            <div v-if="seguro.parentesco" class="detail-row">
              <span class="detail-label">Parentesco:</span>
              <span class="detail-value">{{ seguro.parentesco }}</span>
            </div>
          </div>

          <!-- Alertas de estado -->
          <div v-if="seguro.estado_vigencia === 'vence_hoy'" class="option-alert warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Este seguro vence hoy</span>
          </div>
          
          <div v-else-if="seguro.dias_vencimiento <= 30 && seguro.dias_vencimiento > 0" class="option-alert info">
            <i class="fas fa-info-circle"></i>
            <span>Vence en {{ seguro.dias_vencimiento }} día{{ seguro.dias_vencimiento !== 1 ? 's' : '' }}</span>
          </div>
          
          <div v-else-if="seguro.estado_vigencia === 'vencido'" class="option-alert error">
            <i class="fas fa-times-circle"></i>
            <span>Seguro vencido - No disponible</span>
          </div>
          
          <div v-else-if="seguro.estado !== 'activo'" class="option-alert error">
            <i class="fas fa-pause-circle"></i>
            <span>Seguro {{ seguro.estado }} - No disponible</span>
          </div>
        </div>
      </label>
    </div>

    <!-- Información adicional -->
    <div v-if="seguros.length > 0" class="selector-footer">
      <div class="info-box">
        <div class="info-icon">
          <i class="fas fa-lightbulb"></i>
        </div>
        <div class="info-content">
          <strong>Recomendación:</strong>
          <p>
            Se recomienda usar el seguro principal para la mayoría de consultas. 
            Los seguros secundarios o complementarios pueden usarse para servicios específicos.
          </p>
        </div>
      </div>
      
      <div v-if="seguroRecomendado" class="recomendacion">
        <button 
          @click="seleccionarRecomendado" 
          class="btn-recomendacion"
          type="button"
        >
          <i class="fas fa-magic"></i>
          Usar seguro recomendado ({{ getNombreSeguro(seguroRecomendado) }})
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SelectorSeguro',
  props: {
    pacienteId: {
      type: [String, Number],
      required: true
    },
    modelValue: {
      type: [String, Number],
      default: null
    },
    autoSeleccionar: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:modelValue', 'seguro-seleccionado'],
  data() {
    return {
      seguros: [],
      cargando: false,
      seguroSeleccionado: this.modelValue
    };
  },
  computed: {
    seguroRecomendado() {
      // Buscar seguro principal activo
      const principal = this.seguros.find(s => 
        s.tipo_cobertura === 'principal' && 
        s.estado === 'activo' && 
        s.estado_vigencia === 'vigente'
      );
      
      if (principal) return principal.id;
      
      // Si no hay principal, buscar cualquier seguro activo
      const activo = this.seguros.find(s => 
        s.estado === 'activo' && 
        s.estado_vigencia === 'vigente'
      );
      
      return activo ? activo.id : null;
    }
  },
  watch: {
    pacienteId: {
      immediate: true,
      handler(newId) {
        if (newId) {
          this.cargarSeguros();
        }
      }
    },
    modelValue(newValue) {
      this.seguroSeleccionado = newValue;
    }
  },
  methods: {
    async cargarSeguros() {
      if (!this.pacienteId) return;
      
      this.cargando = true;
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/pacientes/seguros/listar.php?paciente_id=${this.pacienteId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.seguros = response.data.filter(seguro => 
          seguro.estado === 'activo' || seguro.estado_vigencia !== 'vencido'
        );
        
        // Auto-seleccionar si está habilitado
        if (this.autoSeleccionar && !this.seguroSeleccionado && this.seguroRecomendado) {
          this.seguroSeleccionado = this.seguroRecomendado;
          this.emitirCambio();
        }
        
      } catch (error) {
        console.error('Error al cargar seguros:', error);
        this.seguros = [];
      } finally {
        this.cargando = false;
      }
    },
    
    emitirCambio() {
      this.$emit('update:modelValue', this.seguroSeleccionado);
      
      const seguroData = this.seguroSeleccionado 
        ? this.seguros.find(s => s.id === this.seguroSeleccionado)
        : null;
        
      this.$emit('seguro-seleccionado', {
        id: this.seguroSeleccionado,
        data: seguroData,
        esParticular: this.seguroSeleccionado === null
      });
    },
    
    seleccionarRecomendado() {
      if (this.seguroRecomendado) {
        this.seguroSeleccionado = this.seguroRecomendado;
        this.emitirCambio();
      }
    },
    
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
    
    getNombreSeguro(seguroId) {
      const seguro = this.seguros.find(s => s.id === seguroId);
      return seguro ? seguro.aseguradora_nombre : '';
    }
  }
};
</script>

<style scoped>
.selector-seguro {
  background: white;
  border-radius: 12px;
  border: 1px solid #e9ecef;
  overflow: hidden;
}

.selector-header {
  padding: 20px 24px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.selector-header h4 {
  margin: 0 0 4px 0;
  color: #343a40;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.selector-subtitle {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px;
  color: #6c757d;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.no-seguros {
  padding: 40px 24px;
  text-align: center;
}

.no-seguros-icon {
  font-size: 48px;
  color: #6c757d;
  margin-bottom: 16px;
}

.no-seguros h5 {
  margin: 0 0 8px 0;
  color: #343a40;
  font-size: 18px;
}

.no-seguros p {
  margin: 0 0 20px 0;
  color: #6c757d;
}

.alert {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 6px;
  font-size: 14px;
}

.alert.alert-info {
  background: #e3f2fd;
  color: #1976d2;
  border: 1px solid #bbdefb;
}

.seguros-list {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.seguro-option {
  display: block;
  cursor: pointer;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
  position: relative;
}

.seguro-option:hover:not(.vencido) {
  border-color: #007bff;
  box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.seguro-option.active {
  border-color: #007bff;
  background: rgba(0, 123, 255, 0.02);
}

.seguro-option.principal {
  border-color: #28a745;
}

.seguro-option.principal.active {
  border-color: #28a745;
  background: rgba(40, 167, 69, 0.02);
}

.seguro-option.particular {
  border-color: #6c757d;
}

.seguro-option.particular.active {
  border-color: #6c757d;
  background: rgba(108, 117, 125, 0.02);
}

.seguro-option.vencido {
  opacity: 0.6;
  cursor: not-allowed;
  background: #f8f9fa;
}

.seguro-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.option-content {
  padding: 16px;
}

.option-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.option-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: white;
}

.option-icon.principal {
  background: #28a745;
}

.option-icon.secundario {
  background: #6c757d;
}

.option-icon.complementario {
  background: #17a2b8;
}

.option-icon.particular {
  background: #6c757d;
}

.option-info {
  flex: 1;
}

.option-info h5 {
  margin: 0 0 2px 0;
  color: #343a40;
  font-size: 14px;
  font-weight: 600;
}

.option-info .poliza {
  margin: 0;
  color: #6c757d;
  font-size: 12px;
  font-family: monospace;
}

.option-badges {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-end;
}

.option-badge {
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.option-badge.principal {
  background: #28a745;
  color: white;
}

.option-badge.secundario {
  background: #6c757d;
  color: white;
}

.option-badge.complementario {
  background: #17a2b8;
  color: white;
}

.option-badge.particular {
  background: #6c757d;
  color: white;
}

.option-badge.estado.inactivo {
  background: #f8d7da;
  color: #721c24;
}

.option-badge.estado.suspendido {
  background: #fff3cd;
  color: #856404;
}

.option-details {
  margin-bottom: 8px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
  font-size: 12px;
}

.detail-label {
  color: #6c757d;
  font-weight: 500;
}

.detail-value {
  color: #343a40;
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

.option-description {
  color: #6c757d;
  font-size: 12px;
  line-height: 1.4;
}

.option-alert {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  padding: 6px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.option-alert.warning {
  background: #fff3cd;
  color: #856404;
}

.option-alert.info {
  background: #cce5ff;
  color: #0056b3;
}

.option-alert.error {
  background: #f8d7da;
  color: #721c24;
}

.selector-footer {
  padding: 16px 24px;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.info-box {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: #e3f2fd;
  border-radius: 6px;
  border: 1px solid #bbdefb;
}

.info-icon {
  color: #1976d2;
  font-size: 16px;
  margin-top: 2px;
}

.info-content strong {
  color: #1976d2;
  font-size: 13px;
}

.info-content p {
  margin: 4px 0 0 0;
  color: #1976d2;
  font-size: 12px;
  line-height: 1.4;
}

.recomendacion {
  text-align: center;
}

.btn-recomendacion {
  background: #28a745;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.btn-recomendacion:hover {
  background: #1e7e34;
}

/* Responsive */
@media (max-width: 768px) {
  .option-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }

  .option-badges {
    align-items: flex-start;
    flex-direction: row;
  }

  .detail-row {
    flex-direction: column;
    gap: 2px;
  }

  .detail-value {
    font-weight: 500;
  }

  .selector-header {
    padding: 16px;
  }

  .seguros-list {
    padding: 12px;
  }

  .option-content {
    padding: 12px;
  }

  .selector-footer {
    padding: 12px 16px;
  }

  .info-box {
    flex-direction: column;
    gap: 8px;
  }
}

@media (max-width: 480px) {
  .option-icon {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }

  .option-info h5 {
    font-size: 13px;
  }

  .option-info .poliza {
    font-size: 11px;
  }

  .option-badge {
    font-size: 9px;
  }
}
</style>