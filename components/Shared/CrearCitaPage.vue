<template>
  <div class="crear-cita-page">
    <div class="page-header">
      <div class="header-content">
        <div class="header-info">
          <h1>Nueva Cita</h1>
          <p class="header-subtitle">Complete la información para crear una nueva cita médica</p>
        </div>
        <div class="header-actions">
          <button @click="$router.go(-1)" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i>
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="page-content">
      <div class="form-container">
        <!-- Progreso del formulario -->
        <div class="progress-bar">
          <div class="progress-step" :class="{ active: paso >= 1, completed: paso > 1 }">
            <div class="step-number">1</div>
            <span>Paciente</span>
          </div>
          <div class="progress-line" :class="{ active: paso > 1 }"></div>
          <div class="progress-step" :class="{ active: paso >= 2, completed: paso > 2 }">
            <div class="step-number">2</div>
            <span>Información de la Cita</span>
          </div>
          <div class="progress-line" :class="{ active: paso > 2 }"></div>
          <div class="progress-step" :class="{ active: paso >= 3 }">
            <div class="step-number">3</div>
            <span>Confirmación</span>
          </div>
        </div>

        <!-- Paso 1: Información del Paciente -->
        <div v-if="paso === 1" class="form-step">
          <div class="step-header">
            <h2>
              <i class="fas fa-user"></i>
              Información del Paciente
            </h2>
            <p>Busque al paciente por cédula o registre uno nuevo</p>
          </div>

          <div class="form-section">
            <div class="search-container">
              <label class="form-label">Cédula del paciente</label>
              <div class="search-input-group">
                <input 
                  type="text" 
                  v-model="cedulaPaciente" 
                  placeholder="Ingrese la cédula del paciente (V-12345678)" 
                  class="form-control search-input"
                  @keyup.enter="buscarPacientePorCedula"
                  ref="cedulaInput"
                />
                <button 
                  @click="buscarPacientePorCedula" 
                  class="btn btn-search" 
                  :disabled="buscandoPaciente || !cedulaPaciente.trim()"
                >
                  <i class="fas fa-search" v-if="!buscandoPaciente"></i>
                  <i class="fas fa-spinner fa-spin" v-else></i>
                  Buscar
                </button>
              </div>
              <div v-if="errorBusqueda" class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                {{ errorBusqueda }}
              </div>
            </div>

            <!-- Paciente encontrado -->
            <div v-if="pacienteEncontrado" class="patient-found">
              <div class="success-message">
                <i class="fas fa-check-circle"></i>
                Paciente encontrado
              </div>
              <div class="patient-card">
                <div class="patient-header">
                  <div class="patient-avatar">
                    <i class="fas fa-user"></i>
                  </div>
                  <div class="patient-info">
                    <h3>{{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellido }}</h3>
                    <p class="patient-details">
                      <span><strong>Cédula:</strong> {{ pacienteSeleccionado.cedula }}</span>
                      <span v-if="pacienteSeleccionado.telefono"><strong>Teléfono:</strong> {{ pacienteSeleccionado.telefono }}</span>
                    </p>
                  </div>
                  <div class="patient-type">
                    <span :class="`type-badge ${pacienteSeleccionado.tipo}`">
                      {{ pacienteSeleccionado.tipo === 'asegurado' ? 'Asegurado' : 'Particular' }}
                    </span>
                  </div>
                </div>
                <div v-if="pacienteSeleccionado.aseguradora_nombre" class="patient-insurance">
                  <i class="fas fa-building"></i>
                  <span>{{ pacienteSeleccionado.aseguradora_nombre }}</span>
                </div>
              </div>
            </div>

            <!-- Formulario para nuevo paciente -->
            <div v-if="mostrarFormularioNuevoPaciente" class="new-patient-form">
              <div class="info-banner">
                <i class="fas fa-info-circle"></i>
                <div>
                  <strong>Paciente no encontrado</strong>
                  <p>Complete los siguientes datos para registrar un nuevo paciente</p>
                </div>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Nombre *</label>
                  <input 
                    type="text" 
                    v-model="nuevoPaciente.nombre" 
                    class="form-control" 
                    placeholder="Nombre del paciente"
                    required 
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Apellido *</label>
                  <input 
                    type="text" 
                    v-model="nuevoPaciente.apellido" 
                    class="form-control" 
                    placeholder="Apellido del paciente"
                    required 
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Teléfono</label>
                  <input 
                    type="text" 
                    v-model="nuevoPaciente.telefono" 
                    class="form-control" 
                    placeholder="0414-1234567"
                  />
                </div>

                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input 
                    type="email" 
                    v-model="nuevoPaciente.email" 
                    class="form-control" 
                    placeholder="email@ejemplo.com"
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Tipo de paciente</label>
                <div class="radio-group">
                  <label class="radio-option">
                    <input type="radio" v-model="nuevoPaciente.tipo" value="asegurado" />
                    <span class="radio-custom"></span>
                    <div class="radio-content">
                      <strong>Asegurado</strong>
                      <small>Paciente con seguro médico</small>
                    </div>
                  </label>
                  <label class="radio-option">
                    <input type="radio" v-model="nuevoPaciente.tipo" value="particular" />
                    <span class="radio-custom"></span>
                    <div class="radio-content">
                      <strong>Particular</strong>
                      <small>Paciente sin seguro médico</small>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Búsqueda de titular si es asegurado -->
              <div v-if="nuevoPaciente.tipo === 'asegurado'" class="titular-search">
                <label class="form-label">Cédula del Titular *</label>
                <div class="search-input-group">
                  <input 
                    type="text" 
                    v-model="cedulaTitular" 
                    placeholder="Cédula del titular del seguro" 
                    class="form-control"
                    @keyup.enter="buscarTitularPorCedula"
                  />
                  <button 
                    @click="buscarTitularPorCedula" 
                    class="btn btn-search" 
                    :disabled="buscandoTitular || !cedulaTitular.trim()"
                  >
                    <i class="fas fa-search" v-if="!buscandoTitular"></i>
                    <i class="fas fa-spinner fa-spin" v-else></i>
                    Buscar
                  </button>
                </div>
                <div v-if="errorBusquedaTitular" class="error-message">
                  <i class="fas fa-exclamation-triangle"></i>
                  {{ errorBusquedaTitular }}
                </div>

                <div v-if="titularSeleccionado.id" class="titular-found">
                  <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    Titular encontrado
                  </div>
                  <div class="patient-card small">
                    <div class="patient-header">
                      <div class="patient-avatar small">
                        <i class="fas fa-user-tie"></i>
                      </div>
                      <div class="patient-info">
                        <h4>{{ titularSeleccionado.nombre }} {{ titularSeleccionado.apellido }}</h4>
                        <p class="patient-details">
                          <span><strong>Cédula:</strong> {{ titularSeleccionado.cedula }}</span>
                          <span v-if="titularSeleccionado.aseguradora_nombre">
                            <strong>Aseguradora:</strong> {{ titularSeleccionado.aseguradora_nombre }}
                          </span>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="step-actions">
            <button 
              @click="siguientePaso" 
              :disabled="!puedeAvanzarPaso1" 
              class="btn btn-primary btn-large"
            >
              Continuar
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- Paso 2: Información de la Cita -->
        <div v-if="paso === 2" class="form-step">
          <div class="step-header">
            <h2>
              <i class="fas fa-calendar-plus"></i>
              Información de la Cita
            </h2>
            <p>Configure los detalles de la cita médica</p>
          </div>

          <div class="form-section">
            <!-- Selección de seguro si el paciente es asegurado -->
            <div v-if="pacienteSeleccionado.tipo === 'asegurado' && segurosDelPaciente.length > 0" class="insurance-selection">
              <div class="section-header">
                <h3>
                  <i class="fas fa-shield-alt"></i>
                  Seguro Médico
                </h3>
                <p>Seleccione el seguro con el que se gestionará esta cita</p>
              </div>
              
              <div class="insurance-options">
                <label 
                  v-for="seguro in segurosDelPaciente" 
                  :key="seguro.id" 
                  class="insurance-option"
                  :class="{ 'selected': cita.paciente_seguro_id === seguro.id }"
                >
                  <input 
                    type="radio" 
                    v-model="cita.paciente_seguro_id" 
                    :value="seguro.id"
                    name="seguro"
                  />
                  <div class="insurance-card">
                    <div class="insurance-header">
                      <div class="insurance-icon">
                        <i class="fas fa-building"></i>
                      </div>
                      <div class="insurance-info">
                        <h4>{{ seguro.aseguradora_nombre }}</h4>
                        <p class="policy-number">Póliza: {{ seguro.numero_poliza }}</p>
                      </div>
                      <div class="coverage-badge" :class="seguro.tipo_cobertura">
                        {{ seguro.tipo_cobertura === 'principal' ? 'Principal' : 
                           seguro.tipo_cobertura === 'secundario' ? 'Secundario' : 'Complementario' }}
                      </div>
                    </div>
                    <div v-if="seguro.fecha_vencimiento" class="insurance-validity">
                      <i class="fas fa-calendar-check"></i>
                      <span>Vigente hasta {{ formatearFecha(seguro.fecha_vencimiento) }}</span>
                    </div>
                  </div>
                </label>
                
                <!-- Opción para pago particular -->
                <label class="insurance-option" :class="{ 'selected': cita.paciente_seguro_id === null }">
                  <input 
                    type="radio" 
                    v-model="cita.paciente_seguro_id" 
                    :value="null"
                    name="seguro"
                  />
                  <div class="insurance-card particular">
                    <div class="insurance-header">
                      <div class="insurance-icon">
                        <i class="fas fa-dollar-sign"></i>
                      </div>
                      <div class="insurance-info">
                        <h4>Pago Particular</h4>
                        <p class="policy-number">Sin cobertura de seguro</p>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">Especialidad *</label>
                <select v-model="cita.especialidad_id" class="form-control" required>
                  <option value="">Seleccione una especialidad</option>
                  <option v-for="esp in especialidades" :key="esp.id" :value="esp.id">
                    {{ esp.nombre }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label">Categoría de Cita *</label>
                <select v-model="cita.tipo_bloque_id" class="form-control" required>
                  <option value="">Seleccione una categoría</option>
                  <option v-for="tipo in tiposBloque" :key="tipo.id" :value="tipo.id">
                    {{ tipo.nombre }}
                  </option>
                </select>
                <small class="form-help">
                  La categoría determina el tipo de consulta (APS, Consulta Privada, etc.)
                </small>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Motivo/Descripción *</label>
              <textarea 
                v-model="cita.descripcion" 
                rows="4" 
                class="form-control" 
                placeholder="Describa el motivo de la consulta o síntomas del paciente"
                required
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Tipo de asignación</label>
              <div class="assignment-options">
                <label class="assignment-option">
                  <input type="radio" v-model="cita.asignacion_inicial" value="ninguna" />
                  <div class="option-card">
                    <div class="option-icon">
                      <i class="fas fa-clock"></i>
                    </div>
                    <div class="option-content">
                      <strong>Sin asignar</strong>
                      <small>La cita se creará sin asignar a ningún doctor</small>
                    </div>
                  </div>
                </label>

                <label class="assignment-option" v-if="userRole !== 'doctor'">
                  <input type="radio" v-model="cita.asignacion_inicial" value="doctor" />
                  <div class="option-card">
                    <div class="option-icon">
                      <i class="fas fa-user-md"></i>
                    </div>
                    <div class="option-content">
                      <strong>Asignar a doctor específico</strong>
                      <small>Seleccionar un doctor en particular</small>
                    </div>
                  </div>
                </label>

                <label class="assignment-option" v-if="userRole !== 'doctor'">
                  <input type="radio" v-model="cita.asignacion_inicial" value="libre" />
                  <div class="option-card">
                    <div class="option-icon">
                      <i class="fas fa-users"></i>
                    </div>
                    <div class="option-content">
                      <strong>Asignación libre</strong>
                      <small>Cualquier doctor de la especialidad puede tomarla</small>
                    </div>
                  </div>
                </label>

                <label class="assignment-option" v-if="userRole === 'doctor'">
                  <input type="radio" v-model="cita.asignacion_inicial" value="autoasignar" />
                  <div class="option-card">
                    <div class="option-icon">
                      <i class="fas fa-user-check"></i>
                    </div>
                    <div class="option-content">
                      <strong>Autoasignar</strong>
                      <small>Asignar la cita a mí mismo</small>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Selección de doctor específico -->
            <div v-if="cita.asignacion_inicial === 'doctor'" class="form-group">
              <label class="form-label">Doctor *</label>
              <select v-model="cita.doctor_id" class="form-control" required @change="cargarHorariosDisponibles">
                <option value="">Seleccione un doctor</option>
                <option v-for="doctor in doctoresFiltrados" :key="doctor.id" :value="doctor.id">
                  Dr. {{ doctor.nombre }} {{ doctor.apellido }}
                </option>
              </select>
            </div>

            <!-- Selector de semana cuando se selecciona doctor específico -->
            <div v-if="cita.asignacion_inicial === 'doctor' && cita.doctor_id" class="form-group">
              <label class="form-label">Seleccionar horario disponible *</label>
              
              <!-- Navegación de semana -->
              <div class="week-navigation">
                <button 
                  type="button" 
                  @click="cambiarSemana(-1)" 
                  class="btn-week-nav"
                  :disabled="!puedeRetrocederSemana"
                >
                  <i class="fas fa-chevron-left"></i>
                  Semana anterior
                </button>
                
                <div class="week-info">
                  {{ formatearRangoSemana(semanaActual) }}
                </div>
                
                <button 
                  type="button" 
                  @click="cambiarSemana(1)" 
                  class="btn-week-nav"
                >
                  Semana siguiente
                  <i class="fas fa-chevron-right"></i>
                </button>
              </div>

              <!-- Vista de horarios por día de la semana -->
              <div v-if="cargandoHorarios" class="loading-horarios">
                <div class="spinner-small"></div>
                <span>Cargando horarios disponibles...</span>
              </div>
              
              <div v-else class="week-calendar">
                <div 
                  v-for="dia in diasSemana" 
                  :key="dia.fecha"
                  class="day-column"
                  :class="{ 'day-past': esFechaPasada(dia.fecha), 'day-today': esHoy(dia.fecha) }"
                >
                  <div class="day-header">
                    <div class="day-name">{{ dia.nombreDia }}</div>
                    <div class="day-date">{{ formatearFechaCorta(dia.fecha) }}</div>
                  </div>
                  
                  <div class="day-slots">
                    <div v-if="dia.horarios.length === 0" class="no-slots">
                      Sin horarios
                    </div>
                    <label 
                      v-else
                      v-for="slot in dia.horarios" 
                      :key="`${slot.horario_id}-${slot.hora}-${slot.fecha}`"
                      class="horario-slot"
                      :class="{ 'selected': cita.horario_seleccionado === `${slot.horario_id}-${slot.hora}-${slot.fecha}` }"
                    >
                      <input 
                        type="radio" 
                        :value="`${slot.horario_id}-${slot.hora}-${slot.fecha}`"
                        v-model="cita.horario_seleccionado"
                        @change="seleccionarHorario(slot)"
                      />
                      <div class="slot-info">
                        <div class="slot-time">{{ slot.hora }}</div>
                        <div class="slot-type" :style="{ backgroundColor: slot.color }">
                          {{ slot.tipo_bloque }}
                        </div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notas adicionales -->
            <div class="form-group">
              <label class="form-label">Notas adicionales</label>
              <textarea 
                v-model="cita.notas" 
                rows="3" 
                class="form-control" 
                placeholder="Instrucciones especiales, observaciones, etc."
              ></textarea>
            </div>
          </div>

          <div class="step-actions">
            <button @click="anteriorPaso" class="btn btn-outline">
              <i class="fas fa-arrow-left"></i>
              Anterior
            </button>
            <button 
              @click="siguientePaso" 
              :disabled="!puedeAvanzarPaso2" 
              class="btn btn-primary btn-large"
            >
              Revisar
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
        </div>

        <!-- Paso 3: Confirmación -->
        <div v-if="paso === 3" class="form-step">
          <div class="step-header">
            <h2>
              <i class="fas fa-check-circle"></i>
              Confirmación
            </h2>
            <p>Revise los datos antes de crear la cita</p>
          </div>

          <div class="confirmation-container">
            <!-- Resumen del paciente -->
            <div class="summary-section">
              <h3>
                <i class="fas fa-user"></i>
                Información del Paciente
              </h3>
              <div class="summary-card">
                <div v-if="pacienteSeleccionado.id">
                  <p><strong>Nombre:</strong> {{ pacienteSeleccionado.nombre }} {{ pacienteSeleccionado.apellido }}</p>
                  <p><strong>Cédula:</strong> {{ pacienteSeleccionado.cedula }}</p>
                  <p><strong>Tipo:</strong> {{ pacienteSeleccionado.tipo === 'asegurado' ? 'Asegurado' : 'Particular' }}</p>
                  <p v-if="pacienteSeleccionado.aseguradora_nombre">
                    <strong>Aseguradora:</strong> {{ pacienteSeleccionado.aseguradora_nombre }}
                  </p>
                </div>
                <div v-else>
                  <p><strong>Nombre:</strong> {{ nuevoPaciente.nombre }} {{ nuevoPaciente.apellido }}</p>
                  <p><strong>Cédula:</strong> {{ nuevoPaciente.cedula }}</p>
                  <p><strong>Tipo:</strong> {{ nuevoPaciente.tipo === 'asegurado' ? 'Asegurado' : 'Particular' }}</p>
                  <p v-if="nuevoPaciente.tipo === 'asegurado' && titularSeleccionado.aseguradora_nombre">
                    <strong>Aseguradora:</strong> {{ titularSeleccionado.aseguradora_nombre }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Resumen de la cita -->
            <div class="summary-section">
              <h3>
                <i class="fas fa-calendar-plus"></i>
                Información de la Cita
              </h3>
              <div class="summary-card">
                <p><strong>Especialidad:</strong> {{ getNombreEspecialidad(cita.especialidad_id) }}</p>
                <p><strong>Categoría:</strong> {{ getNombreTipoBloque(cita.tipo_bloque_id) }}</p>
                <p><strong>Descripción:</strong> {{ cita.descripcion }}</p>
                <p><strong>Tipo de asignación:</strong> {{ getTextoAsignacion() }}</p>
                <p v-if="cita.asignacion_inicial === 'doctor'">
                  <strong>Doctor asignado:</strong> Dr. {{ getNombreDoctor(cita.doctor_id) }}
                </p>
                <p v-if="cita.notas"><strong>Notas:</strong> {{ cita.notas }}</p>
                <p v-if="pacienteSeleccionado.tipo === 'asegurado'">
                  <strong>Forma de pago:</strong> 
                  <span v-if="getSeguroSeleccionado()">
                    {{ getSeguroSeleccionado().aseguradora_nombre }} - Póliza: {{ getSeguroSeleccionado().numero_poliza }}
                  </span>
                  <span v-else>Pago Particular</span>
                </p>
              </div>
            </div>
          </div>

          <div v-if="errorCreacion" class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            {{ errorCreacion }}
          </div>

          <div class="step-actions">
            <button @click="anteriorPaso" class="btn btn-outline" :disabled="procesandoCita">
              <i class="fas fa-arrow-left"></i>
              Anterior
            </button>
            <button 
              @click="crearCita" 
              :disabled="procesandoCita" 
              class="btn btn-success btn-large"
            >
              <i class="fas fa-spinner fa-spin" v-if="procesandoCita"></i>
              <i class="fas fa-check" v-else></i>
              {{ procesandoCita ? 'Creando...' : 'Crear Cita' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useAuthStore } from '../../store/auth.js';

export default {
  name: 'CrearCitaPage',
  data() {
    return {
      paso: 1,
      
      // Búsqueda de paciente
      cedulaPaciente: '',
      buscandoPaciente: false,
      errorBusqueda: '',
      pacienteEncontrado: false,
      mostrarFormularioNuevoPaciente: false,
      
      // Búsqueda de titular
      cedulaTitular: '',
      buscandoTitular: false,
      errorBusquedaTitular: '',
      
      // Datos
      especialidades: [],
      tiposBloque: [],
      doctores: [],
      segurosDelPaciente: [],
      
      // Estados
      procesandoCita: false,
      errorCreacion: '',
      cargandoSeguros: false,
      
      // Objetos de datos
      pacienteSeleccionado: {},
      titularSeleccionado: {},
      
      cita: {
        paciente_id: '',
        especialidad_id: '',
        tipo_bloque_id: '',
        descripcion: '',
        asignacion_inicial: 'ninguna',
        doctor_id: '',
        notas: '',
        paciente_seguro_id: null,
        horario_seleccionado: '',
        fecha: '',
        hora: ''
      },
      
      nuevoPaciente: {
        nombre: '',
        apellido: '',
        cedula: '',
        telefono: '',
        email: '',
        tipo: 'asegurado',
        es_titular: false,
        titular_id: null
      },
      
      // Variables para manejo de horarios por semana
      semanaActual: new Date(),
      diasSemana: [],
      cargandoHorarios: false
    };
  },
  
  computed: {
    userRole() {
      return useAuthStore().userRole;
    },
    
    doctoresFiltrados() {
      if (!this.cita.especialidad_id) return [];
      return this.doctores.filter(doctor => doctor.especialidad_id == this.cita.especialidad_id);
    },
    
    puedeAvanzarPaso1() {
      const pacienteValido = this.pacienteSeleccionado.id || 
                           (this.mostrarFormularioNuevoPaciente && 
                            this.nuevoPaciente.nombre && 
                            this.nuevoPaciente.apellido && 
                            (this.nuevoPaciente.tipo !== 'asegurado' || this.nuevoPaciente.titular_id));
      return pacienteValido;
    },
    
    puedeAvanzarPaso2() {
      const citaValida = this.cita.especialidad_id && 
                        this.cita.tipo_bloque_id &&
                        this.cita.descripcion && 
                        (this.cita.asignacion_inicial !== 'doctor' || (this.cita.doctor_id && this.cita.horario_seleccionado));
      return citaValida;
    },

    puedeRetrocederSemana() {
      const hoy = new Date();
      const inicioDeSemana = this.obtenerInicioDeSemana(this.semanaActual);
      const inicioDeEstaWeek = this.obtenerInicioDeSemana(hoy);
      return inicioDeSemana > inicioDeEstaWeek;
    }
  },
  
  mounted() {
    this.cargarDatos();
    this.$nextTick(() => {
      if (this.$refs.cedulaInput) {
        this.$refs.cedulaInput.focus();
      }
    });
  },
  
  methods: {
    async cargarDatos() {
      await Promise.all([
        this.cargarEspecialidades(),
        this.cargarTiposBloque(),
        this.cargarDoctores()
      ]);
    },
    
    async cargarEspecialidades() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/especialidades.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.especialidades = response.data;
      } catch (error) {
        console.error('Error al cargar especialidades:', error);
      }
    },
    
    async cargarTiposBloque() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/horarios/TiposBloque.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.tiposBloque = response.data.filter(tipo => tipo.activo);
      } catch (error) {
        console.error('Error al cargar tipos de bloque:', error);
      }
    },
    
    async cargarDoctores() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/listar.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        this.doctores = response.data;
      } catch (error) {
        console.error('Error al cargar doctores:', error);
      }
    },

    async cargarHorariosDisponibles() {
      if (!this.cita.doctor_id) {
        this.diasSemana = [];
        return;
      }

      this.cargandoHorarios = true;
      try {
        // Inicializar la estructura de días de la semana
        this.inicializarDiasSemana();
        
        const token = localStorage.getItem('token');
        
        // Cargar horarios para todos los días de la semana
        const promesasHorarios = this.diasSemana.map(async (dia) => {
          try {
            const response = await axios.get('/api/horarios/disponibles.php', {
              headers: { 'Authorization': `Bearer ${token}` },
              params: {
                doctor_id: this.cita.doctor_id,
                fecha: dia.fecha
              }
            });
            
            dia.horarios = response.data.slots_disponibles || [];
          } catch (error) {
            console.error(`Error al cargar horarios para ${dia.fecha}:`, error);
            dia.horarios = [];
          }
        });
        
        await Promise.all(promesasHorarios);
        
      } catch (error) {
        console.error('Error al cargar horarios disponibles:', error);
      } finally {
        this.cargandoHorarios = false;
      }
    },

    inicializarDiasSemana() {
      const inicioDeSemana = this.obtenerInicioDeSemana(this.semanaActual);
      this.diasSemana = [];
      
      for (let i = 0; i < 7; i++) {
        const fecha = new Date(inicioDeSemana);
        fecha.setDate(inicioDeSemana.getDate() + i);
        
        this.diasSemana.push({
          fecha: fecha.toISOString().split('T')[0],
          nombreDia: this.obtenerNombreDia(fecha.getDay()),
          horarios: []
        });
      }
    },

    obtenerInicioDeSemana(fecha) {
      const date = new Date(fecha);
      const day = date.getDay();
      const diff = date.getDate() - day + (day === 0 ? -6 : 1); // Lunes como primer día
      return new Date(date.setDate(diff));
    },

    obtenerNombreDia(numeroDia) {
      const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
      return dias[numeroDia];
    },

    formatearRangoSemana(fecha) {
      const inicioDeSemana = this.obtenerInicioDeSemana(fecha);
      const finDeSemana = new Date(inicioDeSemana);
      finDeSemana.setDate(inicioDeSemana.getDate() + 6);
      
      const opciones = { day: 'numeric', month: 'short' };
      const inicio = inicioDeSemana.toLocaleDateString('es-ES', opciones);
      const fin = finDeSemana.toLocaleDateString('es-ES', opciones);
      
      return `${inicio} - ${fin}`;
    },

    formatearFechaCorta(fecha) {
      const date = new Date(fecha);
      return date.getDate().toString();
    },

    esFechaPasada(fecha) {
      const hoy = new Date();
      const fechaComparar = new Date(fecha);
      hoy.setHours(0, 0, 0, 0);
      fechaComparar.setHours(0, 0, 0, 0);
      return fechaComparar < hoy;
    },

    esHoy(fecha) {
      const hoy = new Date();
      const fechaComparar = new Date(fecha);
      return hoy.toDateString() === fechaComparar.toDateString();
    },

    cambiarSemana(direccion) {
      const nuevaSemana = new Date(this.semanaActual);
      nuevaSemana.setDate(nuevaSemana.getDate() + (direccion * 7));
      this.semanaActual = nuevaSemana;
      this.cargarHorariosDisponibles();
    },

    seleccionarHorario(slot) {
      this.cita.fecha = slot.fecha;
      this.cita.hora = slot.hora;
      this.cita.horario_id = slot.horario_id;
    },
    
    async buscarPacientePorCedula() {
      if (!this.cedulaPaciente.trim()) {
        this.errorBusqueda = 'Por favor, ingrese una cédula';
        return;
      }
      
      this.buscandoPaciente = true;
      this.errorBusqueda = '';
      this.pacienteSeleccionado = {};
      this.pacienteEncontrado = false;
      this.mostrarFormularioNuevoPaciente = false;
      this.segurosDelPaciente = [];
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/pacientes/buscar.php?cedula=${this.cedulaPaciente}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && !response.data.error) {
          this.pacienteSeleccionado = response.data;
          this.cita.paciente_id = response.data.id;
          this.pacienteEncontrado = true;
          
          // Cargar seguros del paciente
          await this.cargarSegurosDelPaciente(response.data.id);
        } else {
          this.mostrarFormularioNuevoPaciente = true;
          this.nuevoPaciente.cedula = this.cedulaPaciente;
        }
      } catch (error) {
        if (error.response && error.response.status === 404) {
          this.mostrarFormularioNuevoPaciente = true;
          this.nuevoPaciente.cedula = this.cedulaPaciente;
        } else {
          this.errorBusqueda = 'Error al buscar paciente. Por favor, intente nuevamente.';
        }
      } finally {
        this.buscandoPaciente = false;
      }
    },
    
    async cargarSegurosDelPaciente(pacienteId) {
      this.cargandoSeguros = true;
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/pacientes/seguros/listar.php?paciente_id=${pacienteId}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        // Filtrar solo seguros activos
        this.segurosDelPaciente = response.data.filter(seguro => seguro.estado === 'activo');
        
        // Si tiene seguros activos, seleccionar el principal por defecto
        const seguroPrincipal = this.segurosDelPaciente.find(s => s.tipo_cobertura === 'principal');
        if (seguroPrincipal) {
          this.cita.paciente_seguro_id = seguroPrincipal.id;
        }
      } catch (error) {
        console.error('Error al cargar seguros:', error);
        this.segurosDelPaciente = [];
      } finally {
        this.cargandoSeguros = false;
      }
    },
    
    async buscarTitularPorCedula() {
      if (!this.cedulaTitular.trim()) {
        this.errorBusquedaTitular = 'Por favor, ingrese una cédula';
        return;
      }
      
      this.buscandoTitular = true;
      this.errorBusquedaTitular = '';
      this.titularSeleccionado = {};
      this.nuevoPaciente.titular_id = null;
      
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get(`/api/titulares/buscar.php?cedula=${this.cedulaTitular}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && !response.data.error) {
          this.titularSeleccionado = response.data;
          this.nuevoPaciente.titular_id = response.data.id;
        } else {
          this.errorBusquedaTitular = 'No se encontró ningún titular con esa cédula';
        }
      } catch (error) {
        this.errorBusquedaTitular = 'Error al buscar titular. Por favor, intente nuevamente.';
      } finally {
        this.buscandoTitular = false;
      }
    },
    
    siguientePaso() {
      if (this.paso < 3) {
        this.paso++;
      }
    },
    
    anteriorPaso() {
      if (this.paso > 1) {
        this.paso--;
      }
    },
    
    async crearCita() {
      this.procesandoCita = true;
      this.errorCreacion = '';
      
      try {
        const token = localStorage.getItem('token');
        
        // Si es un nuevo paciente, crearlo primero
        let pacienteId = this.cita.paciente_id;
        
        if (this.mostrarFormularioNuevoPaciente) {
          const nuevoPacienteResponse = await axios.post('/api/pacientes/crear.php', this.nuevoPaciente, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          pacienteId = nuevoPacienteResponse.data.id;
        }
        
        // Crear la cita
        const payload = {
          paciente_id: pacienteId,
          especialidad_id: this.cita.especialidad_id,
          tipo_bloque_id: this.cita.tipo_bloque_id,
          descripcion: this.cita.descripcion
        };
        
        // Agregar el seguro seleccionado si aplica
        if (this.pacienteSeleccionado.tipo === 'asegurado' && this.cita.paciente_seguro_id) {
          payload.paciente_seguro_id = this.cita.paciente_seguro_id;
        }
        
        if (this.cita.notas) {
          payload.descripcion += '\n\nNotas: ' + this.cita.notas;
        }
        
        const citaResponse = await axios.post('/api/citas/crear.php', payload, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        const citaId = citaResponse.data.id;
        
        // Manejar asignación si es necesaria
        if (this.cita.asignacion_inicial !== 'ninguna') {
          const asignacionPayload = {
            cita_id: citaId,
            especialidad_id: this.cita.especialidad_id,
            tipo_bloque_id: this.cita.tipo_bloque_id
          };
          
          if (this.cita.asignacion_inicial === 'doctor') {
            asignacionPayload.doctor_id = this.cita.doctor_id;
          } else if (this.cita.asignacion_inicial === 'libre') {
            asignacionPayload.asignacion_libre = true;
          } else if (this.cita.asignacion_inicial === 'autoasignar') {
            // Para doctores que se autoasignan
            const doctorResponse = await axios.get('/api/doctores/mi-perfil.php', {
              headers: { 'Authorization': `Bearer ${token}` }
            });
            asignacionPayload.doctor_id = doctorResponse.data.id;
          }
          
          if (this.cita.notas) {
            asignacionPayload.notas = this.cita.notas;
          }
          
          await axios.put('/api/citas/asignar.php', asignacionPayload, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
        }
        
        // Mostrar mensaje de éxito y redirigir
        this.$toast?.success('Cita creada exitosamente');
        
        // Redirigir según el rol
        if (this.userRole === 'admin' || this.userRole === 'coordinador') {
          this.$router.push(`/${this.userRole}/citas`);
        } else if (this.userRole === 'doctor') {
          this.$router.push('/doctor/dashboard');
        } else if (this.userRole === 'aseguradora') {
          this.$router.push('/aseguradora/historial');
        }
        
      } catch (error) {
        console.error('Error al crear cita:', error);
        this.errorCreacion = error.response?.data?.error || 'Error al crear la cita. Por favor, intente nuevamente.';
      } finally {
        this.procesandoCita = false;
      }
    },
    
    // Métodos auxiliares para mostrar información en el resumen
    getNombreEspecialidad(id) {
      const especialidad = this.especialidades.find(esp => esp.id == id);
      return especialidad?.nombre || '';
    },
    
    getNombreTipoBloque(id) {
      const tipo = this.tiposBloque.find(tipo => tipo.id == id);
      return tipo?.nombre || '';
    },
    
    getNombreDoctor(id) {
      const doctor = this.doctores.find(doc => doc.id == id);
      return doctor ? `${doctor.nombre} ${doctor.apellido}` : '';
    },
    
    getTextoAsignacion() {
      switch (this.cita.asignacion_inicial) {
        case 'ninguna': return 'Sin asignar';
        case 'doctor': return 'Asignado a doctor específico';
        case 'libre': return 'Asignación libre';
        case 'autoasignar': return 'Autoasignado';
        default: return '';
      }
    },
    
    formatearFecha(fecha) {
      return new Date(fecha).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    
    getSeguroSeleccionado() {
      if (this.cita.paciente_seguro_id) {
        return this.segurosDelPaciente.find(s => s.id === this.cita.paciente_seguro_id);
      }
      return null;
    }
  }
};
</script>

<style scoped>
.crear-cita-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.page-header {
  background: white;
  border-bottom: 1px solid #e9ecef;
  padding: 20px 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-info h1 {
  margin: 0 0 4px 0;
  color: #343a40;
  font-size: 28px;
  font-weight: 600;
}

.header-subtitle {
  margin: 0;
  color: #6c757d;
  font-size: 16px;
}

.page-content {
  margin: 0 auto;
  padding: 40px 20px;
}

.form-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

/* Progress Bar */
.progress-bar {
  display: flex;
  flex-direction:row;
  align-items: center;
  padding: 30px 40px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.progress-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  flex: 1;
}

.step-number {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  margin-bottom: 8px;
  transition: all 0.3s;
}

.progress-step.active .step-number {
  background: var(--primary-color);
  color: white;
}

.progress-step.completed .step-number {
  background: #28a745;
  color: white;
}

.progress-step span {
  font-size: 14px;
  color: #6c757d;
  font-weight: 500;
}

.progress-step.active span {
  color: var(--primary-color);
}

.progress-line {
  flex: 1;
  height: 2px;
  background: #e9ecef;
  margin: 0 20px;
  transition: all 0.3s;
}

.progress-line.active {
  background: #28a745;
}

/* Form Steps */
.form-step {
  padding: 40px;
}

.step-header {
  text-align: center;
  margin-bottom: 40px;
}

.step-header h2 {
  margin: 0 0 8px 0;
  color: #343a40;
  font-size: 24px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.step-header p {
  margin: 0;
  color: #6c757d;
  font-size: 16px;
}

.form-section {
  margin-bottom: 40px;
}

/* Search Container */
.search-container {
  max-width: 500px;
  margin: 0 auto;
}

.search-input-group {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.search-input {
  flex: 1;
}

.btn-search {
  padding: 12px 20px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background-color 0.2s;
  white-space: nowrap;
}

.btn-search:hover:not(:disabled) {
  background: #0056b3;
}

.btn-search:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

/* Patient Cards */
.patient-found, .titular-found {
  margin-top: 20px;
}

.success-message {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #155724;
  background: #d4edda;
  padding: 12px 16px;
  border-radius: 6px;
  margin-bottom: 16px;
  font-weight: 500;
}

.patient-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 20px;
  background: #f8f9fa;
}

.patient-card.small {
  padding: 16px;
}

.patient-header {
  display: flex;
  align-items: center;
  gap: 16px;
}

.patient-avatar {
  width: 50px;
  height: 50px;
  background: #007bff;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.patient-avatar.small {
  width: 40px;
  height: 40px;
  font-size: 16px;
}

.patient-info {
  flex: 1;
}

.patient-info h3, .patient-info h4 {
  margin: 0 0 4px 0;
  color: #343a40;
}

.patient-details {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  color: #6c757d;
  font-size: 14px;
}

.type-badge {
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 12px;
  font-weight: 500;
}

.type-badge.asegurado {
  background: #cce5ff;
  color: #0056b3;
}

.type-badge.particular {
  background: #ffe6cc;
  color: #cc7a00;
}

.patient-insurance {
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #6c757d;
  font-size: 14px;
}

/* New Patient Form */
.new-patient-form {
  margin-top: 20px;
}

.info-banner {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  background: #cce5ff;
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 24px;
}

.info-banner i {
  color: #007bff;
  margin-top: 2px;
}

.info-banner strong {
  color: #0056b3;
}

.info-banner p {
  margin: 4px 0 0 0;
  color: #0056b3;
}

.titular-search {
  margin-top: 20px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

/* Form Elements */
.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #343a40;
  font-size: 14px;
}

.form-control {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 16px;
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
  color: #6c757d;
  font-size: 12px;
}

/* Radio Groups */
.radio-group {
  display: flex;
  gap: 20px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  padding: 16px;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  transition: all 0.2s;
  flex: 1;
}

.radio-option:hover {
  border-color: #007bff;
  background: rgba(0, 123, 255, 0.02);
}

.radio-option input[type="radio"] {
  display: none;
}

.radio-custom {
  width: 20px;
  height: 20px;
  border: 2px solid #ced4da;
  border-radius: 50%;
  position: relative;
  transition: all 0.2s;
}

.radio-option input[type="radio"]:checked + .radio-custom {
  border-color: #007bff;
}

.radio-option input[type="radio"]:checked + .radio-custom::after {
  content: '';
  width: 10px;
  height: 10px;
  background: #007bff;
  border-radius: 50%;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.radio-content strong {
  display: block;
  color: #343a40;
  margin-bottom: 2px;
}

.radio-content small {
  color: #6c757d;
  font-size: 12px;
}

/* Assignment Options */
.assignment-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.assignment-option {
  cursor: pointer;
}

.assignment-option input[type="radio"] {
  display: none;
}

.option-card {
  padding: 20px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 16px;
  height:100%;
}

.assignment-option:hover .option-card {
  border-color: #007bff;
  box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.assignment-option input[type="radio"]:checked + .option-card {
  border-color: #007bff;
  background: rgba(0, 123, 255, 0.05);
}

.option-icon {
  width: 40px;
  height: 40px;
  background: #f8f9fa;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 18px;
}

.assignment-option input[type="radio"]:checked + .option-card .option-icon {
  background: #007bff;
  color: white;
}

.option-content strong {
  display: block;
  color: #343a40;
  margin-bottom: 4px;
}

.option-content small {
  color: #6c757d;
  font-size: 14px;
}

/* Insurance Selection */
.insurance-selection {
  margin-bottom: 40px;
  padding: 24px;
  background: #f8f9fa;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.insurance-selection .section-header {
  margin-bottom: 20px;
  text-align: center;
}

.insurance-selection .section-header h3 {
  margin: 0 0 8px 0;
  color: #343a40;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.insurance-selection .section-header p {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}

.insurance-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
}

.insurance-option {
  cursor: pointer;
  display: block;
}

.insurance-option input[type="radio"] {
  display: none;
}

.insurance-card {
  padding: 20px;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  background: white;
  transition: all 0.2s;
  height: 100%;
}

.insurance-option:hover .insurance-card {
  border-color: #007bff;
  box-shadow: 0 2px 10px rgba(0, 123, 255, 0.1);
}

.insurance-option.selected .insurance-card {
  border-color: #007bff;
  background: rgba(0, 123, 255, 0.05);
}

.insurance-card.particular {
  border-style: dashed;
}

.insurance-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.insurance-icon {
  width: 40px;
  height: 40px;
  background: #e3f2fd;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1976d2;
  font-size: 18px;
}

.insurance-card.particular .insurance-icon {
  background: #fff8e1;
  color: #f57c00;
}

.insurance-info {
  flex: 1;
}

.insurance-info h4 {
  margin: 0 0 4px 0;
  color: #343a40;
  font-size: 16px;
  font-weight: 600;
}

.policy-number {
  margin: 0;
  color: #6c757d;
  font-size: 13px;
  font-family: monospace;
}

.coverage-badge {
  padding: 4px 10px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.coverage-badge.principal {
  background: #007bff;
  color: white;
}

.coverage-badge.secundario {
  background: #6c757d;
  color: white;
}

.coverage-badge.complementario {
  background: #17a2b8;
  color: white;
}

.insurance-validity {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #28a745;
  font-size: 13px;
  padding-top: 8px;
  border-top: 1px solid #e9ecef;
}

/* Confirmation */
.confirmation-container {
  display: grid;
  gap: 24px;
}

.summary-section h3 {
  margin: 0 0 16px 0;
  color: #343a40;
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.summary-card {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.summary-card p {
  margin: 8px 0;
  color: #495057;
}

.summary-card strong {
  color: #343a40;
}

/* Error Messages */
.error-message {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #721c24;
  background: #f8d7da;
  padding: 12px 16px;
  border-radius: 6px;
  margin-top: 12px;
  font-size: 14px;
}

/* Step Actions */
.step-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

.btn {
  padding: 12px 20px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 16px;
}

.btn-large {
  padding: 14px 28px;
  font-size: 16px;
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

.btn-primary {
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background:  var(--primary-color);
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #1e7e34;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }

  .page-content {
    padding: 20px 16px;
  }

  .form-step {
    padding: 20px;
  }

  .progress-bar {
    padding: 20px;
    flex-direction: column;
    gap: 20px;
  }

  .progress-line {
    display: none;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .radio-group {
    flex-direction: column;
  }

  .assignment-options {
    grid-template-columns: 1fr;
  }

  .patient-header {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }

  .patient-details {
    flex-direction: column;
    gap: 8px;
  }

  .search-input-group {
    flex-direction: column;
  }

  .step-actions {
    flex-direction: column;
    gap: 12px;
  }

  .step-actions .btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .form-step {
    padding: 16px;
  }

  .step-header h2 {
    font-size: 20px;
  }

  .option-card {
    flex-direction: column;
    text-align: center;
    gap: 12px;
  }
}

/* Estilos para vista semanal de horarios */
.week-navigation {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.btn-week-nav {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-week-nav:hover:not(:disabled) {
  background-color: #0056b3;
  transform: translateY(-1px);
}

.btn-week-nav:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
  transform: none;
}

.week-info {
  font-weight: 600;
  font-size: 16px;
  color: #343a40;
}

.loading-horarios {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px 20px;
  color: #6c757d;
  font-size: 14px;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid #e9ecef;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.week-calendar {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
  background-color: #e9ecef;
  border-radius: 8px;
  overflow: hidden;
  margin-top: 10px;
}

.day-column {
  background-color: white;
  min-height: 200px;
  display: flex;
  flex-direction: column;
}

.day-column.day-past {
  background-color: #f8f9fa;
  opacity: 0.7;
}

.day-column.day-today .day-header {
  background-color: #007bff;
  color: white;
}

.day-header {
  padding: 12px 8px;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  text-align: center;
}

.day-name {
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.day-date {
  font-weight: 700;
  font-size: 16px;
  margin-top: 4px;
}

.day-slots {
  flex: 1;
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.no-slots {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #6c757d;
  font-size: 12px;
  font-style: italic;
}

.horario-slot {
  position: relative;
  cursor: pointer;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 8px 6px;
  transition: all 0.2s ease;
  background: white;
  display: block;
  margin-bottom: 4px;
}

.horario-slot:hover {
  border-color: #007bff;
  box-shadow: 0 1px 3px rgba(0, 123, 255, 0.2);
}

.horario-slot.selected {
  border-color: #007bff;
  background-color: #e7f3ff;
  box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
}

.horario-slot input[type="radio"] {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.slot-info {
  text-align: center;
}

.slot-time {
  font-weight: 600;
  font-size: 13px;
  color: #343a40;
  margin-bottom: 3px;
}

.slot-type {
  font-size: 9px;
  padding: 2px 4px;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  line-height: 1;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>