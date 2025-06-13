<template>
  <div class="gestor-horarios-container">
    <!-- Header -->
    <div class="header-section">
      <h1>Gestión de Horarios</h1>
      <div class="header-controls">
        <!-- Selector de Doctor (solo para admin/coordinador) -->
        <div class="control-group" v-if="userRole !== 'doctor'">
          <label>Doctor:</label>
          <select 
            v-model="filtros.doctorId" 
            @change="onDoctorChange"
            class="form-control"
          >
            <option value="">Seleccione un doctor</option>
            <option 
              v-for="doctor in doctores" 
              :key="doctor.id" 
              :value="doctor.id"
            >
              {{ doctor.nombre }} {{ doctor.apellido }} - {{ doctor.especialidad_nombre }}
            </option>
          </select>
        </div>

        <!-- Navegación de semanas -->
        <div class="semana-navegacion">
          <button @click="navegarSemana(-1)" class="btn btn-outline">
            <i class="fas fa-chevron-left"></i>
          </button>
          <div class="semana-info">
            <span class="semana-titulo">{{ formatoSemanaActual }}</span>
            <span class="fecha-rango">{{ rangoFechas }}</span>
          </div>
          <button @click="navegarSemana(1)" class="btn btn-outline">
            <i class="fas fa-chevron-right"></i>
          </button>
          <button @click="irSemanaActual" class="btn btn-primary">Hoy</button>
        </div>
      </div>
    </div>

    <!-- Estados de carga y error -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando horarios...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      <i class="fas fa-exclamation-triangle"></i>
      {{ error }}
      <button @click="recargar" class="btn btn-sm btn-outline">Reintentar</button>
    </div>

    <div v-else-if="!filtros.doctorId && userRole !== 'doctor'" class="empty-state">
      <i class="fas fa-user-md"></i>
      <h3>Seleccione un doctor</h3>
      <p>Elija un doctor para gestionar sus horarios semanales</p>
    </div>

    <!-- Grid principal de horarios -->
    <div v-else class="calendario-container">
      <!-- Grid de horarios -->
      <div class="calendario-grid">
        <!-- Header del calendario -->
        <div class="calendario-header">
          <div class="hora-header">Hora</div>
          <div 
            v-for="dia in diasSemana" 
            :key="dia.fecha"
            class="dia-header"
            :class="{ 'dia-hoy': dia.esHoy }"
          >
            <div class="dia-nombre">{{ dia.nombre }}</div>
            <div class="dia-fecha">{{ dia.fechaFormateada }}</div>
            <div class="dia-estadisticas">
              {{ dia.totalBloques }} bloque{{ dia.totalBloques !== 1 ? 's' : '' }}
            </div>
          </div>
        </div>

        <!-- Cuerpo del calendario -->
        <div class="calendario-cuerpo">
          <!-- Columna de horas -->
          <div class="horas-columna">
            <div 
              v-for="franja in franjas" 
              :key="franja.hora"
              class="franja-hora"
              :style="{ height: franja.altura + 'px' }"
            >
              {{ franja.horaFormateada }}
            </div>
          </div>

          <!-- Columnas de días -->
          <div 
            v-for="dia in diasSemana" 
            :key="dia.fecha"
            class="dia-columna"
            :class="{ 'dia-hoy': dia.esHoy }"
          >
            <!-- Grid de franjas para este día -->
            <div class="franjas-contenedor">
              <div 
                v-for="franja in franjas" 
                :key="`${dia.fecha}-${franja.hora}`"
                class="franja-celda"
                :class="{ 
                  'franja-ocupada': esFranjaOcupada(dia.diaSemana, franja.hora),
                  'franja-disponible': !esFranjaOcupada(dia.diaSemana, franja.hora)
                }"
                :style="{ height: franja.altura + 'px' }"
                @click.stop="!esFranjaOcupada(dia.diaSemana, franja.hora) && crearNuevoBloque(dia.fecha, franja.hora)"
                :title="obtenerTituloFranja(dia, franja)"
              >
                <!-- Línea de separación cada hora -->
                <div v-if="franja.esInicioHora" class="linea-hora"></div>
              </div>
            </div>

            <!-- Bloques de horario para este día -->
            <div class="bloques-contenedor">
              <div
                v-for="bloque in obtenerBloquesDelDia(dia.diaSemana)"
                :key="bloque.id"
                class="bloque-horario"
                :style="calcularEstiloBloque(bloque)"
                @click="editarBloque(bloque)"
                :title="bloque.tooltip"
              >
                <div class="bloque-contenido">
                  <div class="bloque-tipo">{{ bloque.tipo_nombre }}</div>
                  <div class="bloque-tiempo">
                    {{ formatearHora(bloque.hora_inicio) }} - {{ formatearHora(bloque.hora_fin) }}
                  </div>
                  <div v-if="bloque.notas" class="bloque-notas">
                    {{ bloque.notas.substring(0, 30) }}{{ bloque.notas.length > 30 ? '...' : '' }}
                  </div>
                </div>
                <div class="bloque-acciones">
                  <button 
                    @click.stop="editarBloque(bloque)"
                    class="btn-icono"
                    title="Editar"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    @click.stop="eliminarBloque(bloque)"
                    class="btn-icono btn-peligro"
                    title="Eliminar"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar bloque -->
    <div v-if="modal.mostrar" class="modal-overlay" @click="cerrarModal">
      <div class="modal-container" @click.stop>
        <div class="modal-header">
          <h2>
            <i class="fas fa-clock"></i>
            {{ modal.esEdicion ? 'Editar Bloque Horario' : 'Nuevo Bloque Horario' }}
          </h2>
          <button @click="cerrarModal" class="btn-cerrar">&times;</button>
        </div>
        
        <form @submit.prevent="guardarBloque" class="modal-cuerpo">
          <!-- Tipo de bloque -->
          <div class="form-grupo">
            <label class="form-etiqueta">
              <i class="fas fa-tag"></i>
              Tipo de Bloque *
            </label>
            <select 
              v-model="formulario.tipo_bloque_id" 
              class="form-control"
              required
            >
              <option value="">Seleccione un tipo</option>
              <option 
                v-for="tipo in tiposBloque" 
                :key="tipo.id" 
                :value="tipo.id"
              >
                {{ tipo.nombre }}
              </option>
            </select>
            <div class="vista-previa-color" v-if="colorSeleccionado">
              <div 
                class="muestra-color" 
                :style="{ backgroundColor: colorSeleccionado }"
              ></div>
              <span>{{ nombreTipoSeleccionado }}</span>
            </div>
          </div>

          <!-- Fecha -->
          <div class="form-grupo">
            <label class="form-etiqueta">
              <i class="fas fa-calendar"></i>
              Fecha *
            </label>
            <input 
              type="date" 
              v-model="formulario.fecha" 
              class="form-control"
              :min="fechaMinima"
              :max="fechaMaxima"
              required
            />
            <small class="form-ayuda">
              {{ obtenerNombreDia(formulario.fecha) }}
            </small>
          </div>

          <!-- Horarios -->
          <div class="form-fila">
            <div class="form-grupo">
              <label class="form-etiqueta">
                <i class="fas fa-play"></i>
                Hora Inicio *
              </label>
              <select 
                v-model="formulario.hora_inicio" 
                class="form-control"
                required
              >
                <option value="">Seleccione</option>
                <option 
                  v-for="hora in horasDisponibles" 
                  :key="hora.valor" 
                  :value="hora.valor"
                >
                  {{ hora.texto }}
                </option>
              </select>
            </div>
            
            <div class="form-grupo">
              <label class="form-etiqueta">
                <i class="fas fa-stop"></i>
                Hora Fin *
              </label>
              <select 
                v-model="formulario.hora_fin" 
                class="form-control"
                required
              >
                <option value="">Seleccione</option>
                <option 
                  v-for="hora in horasFinDisponibles" 
                  :key="hora.valor" 
                  :value="hora.valor"
                  :disabled="hora.deshabilitada"
                >
                  {{ hora.texto }}
                </option>
              </select>
            </div>
          </div>

          <!-- Duración calculada -->
          <div v-if="duracionCalculada" class="duracion-info">
            <i class="fas fa-clock"></i>
            Duración: {{ duracionCalculada }}
          </div>

          <!-- Notas -->
          <div class="form-grupo">
            <label class="form-etiqueta">
              <i class="fas fa-sticky-note"></i>
              Notas
            </label>
            <textarea 
              v-model="formulario.notas" 
              class="form-control"
              rows="3"
              placeholder="Notas adicionales (opcional)"
              maxlength="500"
            ></textarea>
            <small class="form-ayuda">
              {{ formulario.notas ? formulario.notas.length : 0 }}/500 caracteres
            </small>
          </div>

          <!-- Error del modal -->
          <div v-if="modal.error" class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            {{ modal.error }}
          </div>

          <!-- Botones del modal -->
          <div class="modal-footer">
            <button type="button" @click="cerrarModal" class="btn btn-outline">
              <i class="fas fa-times"></i>
              Cancelar
            </button>
            <button 
              type="submit" 
              class="btn btn-primary"
              :disabled="modal.guardando || !formularioValido"
            >
              <i class="fas fa-spinner fa-spin" v-if="modal.guardando"></i>
              <i class="fas fa-save" v-else></i>
              {{ modal.guardando ? 'Guardando...' : (modal.esEdicion ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useAuthStore } from '../../store/auth';

export default {
  name: 'GestorHorarios',
  data() {
    return {
      // Estado principal
      loading: false,
      error: '',
      
      // Datos
      doctores: [],
      tiposBloque: [],
      horarios: [],
      
      // Filtros y navegación
      filtros: {
        doctorId: '',
      },
      semanaActual: new Date(),
      
      // Modal
      modal: {
        mostrar: false,
        esEdicion: false,
        guardando: false,
        error: ''
      },
      
      // Formulario
      formulario: {
        id: null,
        doctor_id: '',
        tipo_bloque_id: '',
        fecha: '',
        hora_inicio: '',
        hora_fin: '',
        notas: ''
      },
      
      // Configuración del calendario
      horaInicio: 7, // 7:00 AM
      horaFin: 19,   // 7:00 PM
      intervaloMinutos: 30,
      alturaCelda: 40
    };
  },
  
  computed: {
    authStore() {
      return useAuthStore();
    },
    
    userRole() {
      return this.authStore.userRole;
    },
    
    // Información de la semana actual
    inicioSemana() {
      const fecha = new Date(this.semanaActual);
      const dia = fecha.getDay(); // 0 = domingo, 1 = lunes, etc.
      
      // Calcular cuántos días retroceder para llegar al lunes
      let diasAtras;
      if (dia === 0) {
        // Si es domingo, retroceder 6 días
        diasAtras = 6;
      } else {
        // Para cualquier otro día, retroceder (dia - 1) días
        diasAtras = dia - 1;
      }
      
      // CREAR UNA NUEVA FECHA PARA NO MUTAR LA ORIGINAL
      const inicioSemana = new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate() - diasAtras);
      
      const resultado = inicioSemana.toISOString().split('T')[0];
      
      console.log('🔍 Cálculo inicioSemana computed FIXED:', {
        semana_actual: this.semanaActual,
        fecha_trabajo: fecha,
        dia_semana_js: dia,
        dias_atras: diasAtras,
        fecha_menos_dias: fecha.getDate() - diasAtras,
        inicio_calculado: resultado,
        fecha_objeto_final: inicioSemana
      });
      
      return new Date(resultado + 'T00:00:00');
    },
    
    formatoSemanaActual() {
      const año = this.inicioSemana.getFullYear();
      const semana = this.obtenerNumeroSemana(this.inicioSemana);
      return `Semana ${semana}, ${año}`;
    },
    
    rangoFechas() {
      const inicio = this.inicioSemana;
      const fin = new Date(inicio);
      fin.setDate(fin.getDate() + 6);
      
      const formatoCorto = { day: 'numeric', month: 'short' };
      return `${inicio.toLocaleDateString('es-ES', formatoCorto)} - ${fin.toLocaleDateString('es-ES', formatoCorto)}`;
    },
    
    // Días de la semana
    diasSemana() {
      const dias = [];
      const nombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
      const hoy = new Date().toDateString();
      
      for (let i = 0; i < 7; i++) {
        const fecha = new Date(this.inicioSemana);
        fecha.setDate(fecha.getDate() + i);
        
        const bloquesDia = this.obtenerBloquesDelDia(i + 1);
        
        dias.push({
          nombre: nombres[i],
          nombreCorto: nombres[i].substring(0, 3),
          fecha: fecha.toISOString().split('T')[0],
          fechaFormateada: fecha.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }),
          diaSemana: i + 1,
          esHoy: fecha.toDateString() === hoy,
          totalBloques: bloquesDia.length
        });
      }
      
      return dias;
    },
    
    // Franjas horarias
    franjas() {
      const franjas = [];
      const totalMinutos = (this.horaFin - this.horaInicio) * 60;
      const totalFranjas = totalMinutos / this.intervaloMinutos;
      
      for (let i = 0; i < totalFranjas; i++) {
        const minutosTotales = this.horaInicio * 60 + (i * this.intervaloMinutos);
        const horas = Math.floor(minutosTotales / 60);
        const minutos = minutosTotales % 60;
        
        const hora = `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;
        const esInicioHora = minutos === 0;
        
        franjas.push({
          hora,
          horaFormateada: this.formatearHora(hora),
          altura: this.alturaCelda,
          esInicioHora
        });
      }
      
      return franjas;
    },
    
    // Horas disponibles para el selector
    horasDisponibles() {
      return this.franjas.map(franja => ({
        valor: franja.hora,
        texto: franja.horaFormateada
      }));
    },
    
    // Horas de fin disponibles (después de la hora de inicio)
    horasFinDisponibles() {
      if (!this.formulario.hora_inicio) return [];
      
      const inicioMinutos = this.convertirHoraAMinutos(this.formulario.hora_inicio);
      
      return this.horasDisponibles.map(hora => ({
        ...hora,
        deshabilitada: this.convertirHoraAMinutos(hora.valor) <= inicioMinutos
      }));
    },
    
    // Duración calculada del bloque
    duracionCalculada() {
      if (!this.formulario.hora_inicio || !this.formulario.hora_fin) return '';
      
      const inicioMinutos = this.convertirHoraAMinutos(this.formulario.hora_inicio);
      const finMinutos = this.convertirHoraAMinutos(this.formulario.hora_fin);
      const duracion = finMinutos - inicioMinutos;
      
      if (duracion <= 0) return '';
      
      const horas = Math.floor(duracion / 60);
      const minutos = duracion % 60;
      
      let texto = '';
      if (horas > 0) texto += `${horas}h `;
      if (minutos > 0) texto += `${minutos}m`;
      
      return texto.trim();
    },
    
    // Color del tipo seleccionado
    colorSeleccionado() {
      if (!this.formulario.tipo_bloque_id) return '';
      const tipo = this.tiposBloque.find(t => t.id == this.formulario.tipo_bloque_id);
      return tipo?.color || '';
    },
    
    // Nombre del tipo seleccionado
    nombreTipoSeleccionado() {
      if (!this.formulario.tipo_bloque_id) return '';
      const tipo = this.tiposBloque.find(t => t.id == this.formulario.tipo_bloque_id);
      return tipo?.nombre || '';
    },
    
    // Fechas límite para el selector
    fechaMinima() {
      return this.diasSemana[0]?.fecha || '';
    },
    
    fechaMaxima() {
      return this.diasSemana[6]?.fecha || '';
    },
    
    // Validación del formulario
    formularioValido() {
      return this.formulario.tipo_bloque_id && 
             this.formulario.fecha && 
             this.formulario.hora_inicio && 
             this.formulario.hora_fin &&
             this.convertirHoraAMinutos(this.formulario.hora_fin) > this.convertirHoraAMinutos(this.formulario.hora_inicio);
    }
  },
  
  async mounted() {
    await this.inicializar();
  },
  
  methods: {
    // Inicialización
    async inicializar() {
      try {
        this.loading = true;
        this.error = '';
        
        await Promise.all([
          this.cargarTiposBloque(),
          this.userRole !== 'doctor' ? this.cargarDoctores() : this.configurarDoctorActual()
        ]);
        
        if (this.filtros.doctorId) {
          await this.cargarHorarios();
        }
      } catch (error) {
        console.error('Error en inicialización:', error);
        this.error = 'Error al cargar la información inicial';
      } finally {
        this.loading = false;
      }
    },
    
    // Carga de datos
    async cargarDoctores() {
      const token = localStorage.getItem('token');
      const response = await axios.get('/api/doctores/listar.php', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      this.doctores = response.data;
    },
    
    async configurarDoctorActual() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/perfil.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data?.id) {
          this.filtros.doctorId = response.data.id;
        }
      } catch (error) {
        console.error('Error al obtener doctor actual:', error);
      }
    },
    
    async cargarTiposBloque() {
      const token = localStorage.getItem('token');
      const response = await axios.get('/api/horarios/TiposBloque.php', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      this.tiposBloque = response.data.filter(tipo => tipo.activo);
    },
    
    async cargarHorarios() {
      if (!this.filtros.doctorId) return;
      
      try {
        this.loading = true;
        this.error = '';
        
        const token = localStorage.getItem('token');
        const fechaInicio = this.inicioSemana.toISOString().split('T')[0];
        
        console.log('🔍 Cargando horarios con detalles:', {
          doctor_id: this.filtros.doctorId,
          fecha_inicio_enviada: fechaInicio,
          semana_actual: this.semanaActual,
          inicio_semana_objeto: this.inicioSemana,
          inicio_semana_string: fechaInicio
        });
        
        // USAR EL PARÁMETRO CORRECTO QUE ESPERA EL PHP
        const response = await axios.get('/api/horarios/gestionar.php', {
          headers: { 'Authorization': `Bearer ${token}` },
          params: {
            doctor_id: this.filtros.doctorId,
            fecha_inicio: fechaInicio
          }
        });
        
        console.log('🔍 Respuesta del servidor completa:', {
          status: response.status,
          data: response.data,
          data_length: response.data?.length || 0
        });
        
        this.horarios = response.data.map(horario => {
          // Limpiar el formato de hora si viene con segundos
          const horaInicio = horario.hora_inicio.includes(':') ? horario.hora_inicio.substring(0, 5) : horario.hora_inicio;
          const horaFin = horario.hora_fin.includes(':') ? horario.hora_fin.substring(0, 5) : horario.hora_fin;
          
          const horarioProcesado = {
            ...horario,
            hora_inicio: horaInicio,
            hora_fin: horaFin,
            tooltip: `${this.obtenerNombreTipo(horario.tipo_bloque_id)}\n${this.formatearHora(horaInicio)} - ${this.formatearHora(horaFin)}${horario.notas ? '\n' + horario.notas : ''}`
          };
          
          console.log('🔍 Horario procesado:', horarioProcesado);
          return horarioProcesado;
        });
        
        console.log('🔍 Total horarios procesados:', this.horarios.length);
        
      } catch (error) {
        console.error('❌ Error al cargar horarios:', error);
        this.error = 'Error al cargar los horarios';
      } finally {
        this.loading = false;
      }
    },
    
    // Eventos
    async onDoctorChange() {
      if (this.filtros.doctorId) {
        await this.cargarHorarios();
      } else {
        this.horarios = [];
      }
    },
    
    navegarSemana(direccion) {
      const nuevaFecha = new Date(this.semanaActual);
      nuevaFecha.setDate(nuevaFecha.getDate() + (direccion * 7));
      this.semanaActual = nuevaFecha;
      
      if (this.filtros.doctorId) {
        this.cargarHorarios();
      }
    },
    
    irSemanaActual() {
      this.semanaActual = new Date();
      if (this.filtros.doctorId) {
        this.cargarHorarios();
      }
    },
    
    recargar() {
      this.inicializar();
    },
    
    // Gestión de bloques
    obtenerBloquesDelDia(diaSemana) {
      const bloques = this.horarios.filter(horario => {
        console.log('Comparando bloque:', {
          bloque_dia_semana: horario.dia_semana,
          buscando_dia: diaSemana,
          coincide: horario.dia_semana === diaSemana,
          horario_completo: horario
        });
        
        return horario.dia_semana === diaSemana;
      });
      
      console.log(`Bloques encontrados para día ${diaSemana}:`, bloques);
      return bloques;
    },
    
    calcularEstiloBloque(bloque) {
      console.log('Calculando estilo para bloque:', bloque);
      
      const inicioMinutos = this.convertirHoraAMinutos(bloque.hora_inicio);
      const finMinutos = this.convertirHoraAMinutos(bloque.hora_fin);
      const duracionMinutos = finMinutos - inicioMinutos;
      
      // Calcular posición desde el inicio del calendario
      const inicioCalendarioMinutos = this.horaInicio * 60;
      const posicionMinutos = inicioMinutos - inicioCalendarioMinutos;
      
      const top = (posicionMinutos / this.intervaloMinutos) * this.alturaCelda;
      const height = (duracionMinutos / this.intervaloMinutos) * this.alturaCelda;
      
      const color = this.obtenerColorTipo(bloque.tipo_bloque_id);
      
      const estilo = {
        top: `${top}px`,
        height: `${height}px`,
        backgroundColor: color,
        borderColor: this.ajustarColor(color, -20)
      };
      
      console.log('Estilo calculado:', {
        bloque: bloque.id,
        inicioMinutos,
        finMinutos,
        duracionMinutos,
        posicionMinutos,
        top,
        height,
        color,
        estilo
      });
      
      return estilo;
    },
    
    crearNuevoBloque(fecha, hora) {
      console.log('🎯 Creando nuevo bloque:', { fecha, hora });
      
      // Verificar si ya existe un bloque en esta posición
      const diaSemana = this.diasSemana.find(d => d.fecha === fecha)?.diaSemana;
      if (!diaSemana) {
        console.error('No se pudo determinar el día de la semana');
        return;
      }
      
      // Esta validación ya no es necesaria porque las celdas ocupadas no son clickeables
      // pero la mantenemos por seguridad
      const bloquesExistentes = this.obtenerBloquesDelDia(diaSemana);
      const horaInicioMinutos = this.convertirHoraAMinutos(hora);
      
      const hayConflicto = bloquesExistentes.some(bloque => {
        const bloqueInicio = this.convertirHoraAMinutos(bloque.hora_inicio);
        const bloqueFin = this.convertirHoraAMinutos(bloque.hora_fin);
        return horaInicioMinutos >= bloqueInicio && horaInicioMinutos < bloqueFin;
      });
      
      if (hayConflicto) {
        console.log('⚠️ Conflicto detectado, bloque no debería ser clickeable');
        return;
      }
      
      // Calcular hora de fin sugerida (1 hora por defecto)
      const horaFinSugerida = this.sumarMinutos(hora, 60);
      
      // Validar que la hora de fin no exceda el horario del calendario
      const horaFinMaxima = `${this.horaFin}:00`;
      const horaFinFinal = this.convertirHoraAMinutos(horaFinSugerida) > this.convertirHoraAMinutos(horaFinMaxima) 
        ? horaFinMaxima 
        : horaFinSugerida;
      
      console.log('✅ Configurando formulario para nuevo bloque');
      
      this.formulario = {
        id: null,
        doctor_id: this.filtros.doctorId,
        tipo_bloque_id: '',
        fecha: fecha,
        hora_inicio: hora,
        hora_fin: horaFinFinal,
        notas: ''
      };
      
      this.modal = {
        mostrar: true,
        esEdicion: false,
        guardando: false,
        error: ''
      };
    },

    // Nueva función para verificar si una franja está ocupada
    esFranjaOcupada(diaSemana, hora) {
      const bloquesDelDia = this.obtenerBloquesDelDia(diaSemana);
      const horaMinutos = this.convertirHoraAMinutos(hora);
      
      return bloquesDelDia.some(bloque => {
        const bloqueInicio = this.convertirHoraAMinutos(bloque.hora_inicio);
        const bloqueFin = this.convertirHoraAMinutos(bloque.hora_fin);
        return horaMinutos >= bloqueInicio && horaMinutos < bloqueFin;
      });
    },

    // Nueva función para obtener el título apropiado para cada franja
    obtenerTituloFranja(dia, franja) {
      if (this.esFranjaOcupada(dia.diaSemana, franja.hora)) {
        // Encontrar el bloque que ocupa esta franja
        const bloquesDelDia = this.obtenerBloquesDelDia(dia.diaSemana);
        const horaMinutos = this.convertirHoraAMinutos(franja.hora);
        
        const bloque = bloquesDelDia.find(b => {
          const inicio = this.convertirHoraAMinutos(b.hora_inicio);
          const fin = this.convertirHoraAMinutos(b.hora_fin);
          return horaMinutos >= inicio && horaMinutos < fin;
        });
        
        if (bloque) {
          return `Ocupado por: ${this.obtenerNombreTipo(bloque.tipo_bloque_id)} (${this.formatearHora(bloque.hora_inicio)} - ${this.formatearHora(bloque.hora_fin)})`;
        }
        return 'Franja ocupada';
      }
      
      return `Crear bloque para ${dia.nombre} a las ${franja.horaFormateada}`;
    },
    
    editarBloque(bloque) {
      // Calcular la fecha real del bloque
      const fechaReal = this.calcularFechaRealBloque(bloque);
      
      this.formulario = {
        id: bloque.id,
        doctor_id: bloque.doctor_id,
        tipo_bloque_id: bloque.tipo_bloque_id,
        fecha: fechaReal,
        hora_inicio: bloque.hora_inicio,
        hora_fin: bloque.hora_fin,
        notas: bloque.notas || ''
      };
      
      this.modal = {
        mostrar: true,
        esEdicion: true,
        guardando: false,
        error: ''
      };
    },
    
    async eliminarBloque(bloque) {
      if (!confirm('¿Está seguro de que desea eliminar este bloque horario?')) {
        return;
      }
      
      try {
        const token = localStorage.getItem('token');
        await axios.delete('/api/horarios/gestionar.php', {
          headers: { 'Authorization': `Bearer ${token}` },
          data: { id: bloque.id }
        });
        
        await this.cargarHorarios();
      } catch (error) {
        console.error('Error al eliminar bloque:', error);
        alert('Error al eliminar el bloque horario');
      }
    },
    
    // Modal
    cerrarModal() {
      this.modal.mostrar = false;
      this.formulario = {
        id: null,
        doctor_id: this.filtros.doctorId,
        tipo_bloque_id: '',
        fecha: '',
        hora_inicio: '',
        hora_fin: '',
        notas: ''
      };
    },
    
    async guardarBloque() {
      if (!this.formularioValido) return;
      
      try {
        this.modal.guardando = true;
        this.modal.error = '';
        
        // Calcular día de la semana
        const fecha = new Date(this.formulario.fecha + 'T00:00:00'); // Evitar problemas de timezone
        const diaSemana = fecha.getDay() === 0 ? 7 : fecha.getDay(); // Domingo = 7
        
        // IMPORTANTE: Calcular la fecha de inicio de semana basada en la fecha seleccionada
        // Esto debe coincidir con lo que enviamos en cargarHorarios()
        const fechaInicieSemana = this.calcularInicioSemanaDesdefecha(this.formulario.fecha);
        
        const datos = {
          doctor_id: parseInt(this.formulario.doctor_id),
          tipo_bloque_id: parseInt(this.formulario.tipo_bloque_id),
          fecha: fechaInicieSemana, // Usar fecha de inicio de semana, no la fecha seleccionada
          dia_semana: diaSemana,
          hora_inicio: this.formulario.hora_inicio,
          hora_fin: this.formulario.hora_fin,
          notas: this.formulario.notas || ''
        };
        
        if (this.modal.esEdicion) {
          datos.id = this.formulario.id;
        }
        
        console.log('Enviando datos al servidor:', {
          ...datos,
          fecha_seleccionada_original: this.formulario.fecha,
          fecha_inicio_semana_calculada: fechaInicieSemana,
          dia_semana_calculado: diaSemana
        });
        
        const token = localStorage.getItem('token');
        const metodo = this.modal.esEdicion ? 'put' : 'post';
        
        const response = await axios[metodo]('/api/horarios/gestionar.php', datos, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        console.log('Respuesta del servidor:', response.data);
        
        if (response.data?.success) {
          console.log('Guardado exitoso, recargando horarios...');
          await this.cargarHorarios();
          this.cerrarModal();
        } else {
          this.modal.error = response.data?.error || 'Error al guardar el bloque';
        }
        
      } catch (error) {
        console.error('Error al guardar bloque:', error);
        this.modal.error = error.response?.data?.error || 'Error al guardar el bloque horario';
      } finally {
        this.modal.guardando = false;
      }
    },
    
    // Utilidades
    obtenerNumeroSemana(fecha) {
      const inicioAño = new Date(fecha.getFullYear(), 0, 1);
      const dias = Math.floor((fecha - inicioAño) / (24 * 60 * 60 * 1000));
      return Math.ceil((dias + inicioAño.getDay() + 1) / 7);
    },
    
    // Función para calcular inicio de semana desde cualquier fecha
    calcularInicioSemanaDesdefecha(fecha) {
      const fechaObj = new Date(fecha + 'T00:00:00');
      const diaSemana = fechaObj.getDay(); // 0 = domingo, 1 = lunes, etc.
      
      // Calcular cuántos días retroceder para llegar al lunes
      let diasAtras;
      if (diaSemana === 0) {
        // Si es domingo, retroceder 6 días
        diasAtras = 6;
      } else {
        // Para cualquier otro día, retroceder (diaSemana - 1) días
        diasAtras = diaSemana - 1;
      }
      
      // CREAR NUEVA FECHA PARA NO MUTAR
      const inicioSemana = new Date(fechaObj.getFullYear(), fechaObj.getMonth(), fechaObj.getDate() - diasAtras);
      
      const resultado = inicioSemana.toISOString().split('T')[0];
      
      console.log('🔍 Cálculo inicio semana FIXED:', {
        fecha_original: fecha,
        fecha_objeto: fechaObj,
        dia_semana_js: diaSemana,
        dias_atras: diasAtras,
        fecha_menos_dias: fechaObj.getDate() - diasAtras,
        fecha_inicio_calculada: resultado,
        inicio_semana_objeto: inicioSemana
      });
      
      return resultado;
    },
    
    calcularFechaRealBloque(bloque) {
      const fechaInicio = new Date(bloque.fecha_inicio);
      const diasAgregar = bloque.dia_semana - 1;
      const fechaReal = new Date(fechaInicio);
      fechaReal.setDate(fechaReal.getDate() + diasAgregar);
      return fechaReal.toISOString().split('T')[0];
    },
    
    convertirHoraAMinutos(hora) {
      const [horas, minutos] = hora.split(':').map(Number);
      return horas * 60 + minutos;
    },
    
    sumarMinutos(hora, minutosAgregar) {
      const minutosActuales = this.convertirHoraAMinutos(hora);
      const nuevosMinutos = minutosActuales + minutosAgregar;
      const horas = Math.floor(nuevosMinutos / 60);
      const minutos = nuevosMinutos % 60;
      return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`;
    },
    
    formatearHora(hora) {
      const [h, m] = hora.split(':');
      const horas = parseInt(h);
      const minutos = parseInt(m);
      
      if (minutos === 0) {
        return `${horas}:00`;
      }
      return `${horas}:${minutos.toString().padStart(2, '0')}`;
    },
    
    obtenerNombreDia(fecha) {
      if (!fecha) return '';
      const date = new Date(fecha);
      const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      return dias[date.getDay()];
    },
    
    obtenerColorTipo(tipoId) {
      const tipo = this.tiposBloque.find(t => t.id == tipoId);
      return tipo?.color || '#6c757d';
    },
    
    obtenerNombreTipo(tipoId) {
      const tipo = this.tiposBloque.find(t => t.id == tipoId);
      return tipo?.nombre || 'Desconocido';
    },
    
    ajustarColor(color, porcentaje) {
      const hex = color.replace('#', '');
      const num = parseInt(hex, 16);
      const amt = Math.round(2.55 * porcentaje);
      const R = (num >> 16) + amt;
      const G = (num >> 8 & 0x00FF) + amt;
      const B = (num & 0x0000FF) + amt;
      return `#${(0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255))
        .toString(16).slice(1)}`;
    }
  }
};
</script>

<style scoped>
/* Variables CSS */
:root {
  --primary-color: #007bff;
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
  --info-color: #17a2b8;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --secondary-color: #6c757d;
  --border-color: #dee2e6;
  --hover-color: #e9ecef;
}

/* Layout principal */
.gestor-horarios-container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 24px;
  gap: 20px;
  flex-wrap: wrap;
}

.header-section h1 {
  margin: 0;
  color: var(--dark-color);
  font-size: 28px;
  font-weight: 600;
}

.header-controls {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.control-grupo {
  display: flex;
  flex-direction: column;
  min-width: 220px;
}

.control-grupo label {
  margin-bottom: 6px;
  font-weight: 500;
  color: var(--dark-color);
  font-size: 14px;
}

/* Navegación de semana */
.semana-navegacion {
  display: flex;
  align-items: center;
  gap: 12px;
  background: white;
  padding: 12px 16px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.semana-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 160px;
}

.semana-titulo {
  font-weight: 600;
  color: var(--dark-color);
  font-size: 14px;
}

.fecha-rango {
  font-size: 12px;
  color: var(--secondary-color);
  margin-top: 2px;
}

/* Estados */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border-color);
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.empty-state i {
  font-size: 48px;
  color: var(--secondary-color);
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: var(--dark-color);
}

.empty-state p {
  margin: 0;
  color: var(--secondary-color);
}

/* Calendario */
.calendario-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.calendario-grid {
  display: flex;
  flex-direction: column;
}

.calendario-header {
  display: grid;
  grid-template-columns: 80px repeat(7, 1fr);
  background: var(--light-color);
  border-bottom: 2px solid var(--border-color);
}

.hora-header {
  padding: 16px 8px;
  font-weight: 600;
  text-align: center;
  color: var(--secondary-color);
  font-size: 12px;
  border-right: 1px solid var(--border-color);
}

.dia-header {
  padding: 16px 12px;
  text-align: center;
  border-right: 1px solid var(--border-color);
  transition: background-color 0.2s;
}

.dia-header.dia-hoy {
  background-color: rgba(0, 123, 255, 0.1);
  border-color: var(--primary-color);
}

.dia-nombre {
  font-weight: 600;
  color: var(--dark-color);
  font-size: 14px;
  margin-bottom: 4px;
}

.dia-fecha {
  font-size: 12px;
  color: var(--secondary-color);
  margin-bottom: 4px;
}

.dia-estadisticas {
  font-size: 11px;
  color: var(--info-color);
  font-weight: 500;
}

/* Cuerpo del calendario */
.calendario-cuerpo {
  display: grid;
  grid-template-columns: 80px repeat(7, 1fr);
  min-height: 500px;
  position: relative;
}

.horas-columna {
  background: var(--light-color);
  border-right: 2px solid var(--border-color);
}

.franja-hora {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  color: var(--secondary-color);
  font-weight: 500;
  border-bottom: 1px solid rgba(222, 226, 230, 0.5);
}

.dia-columna {
  position: relative;
  border-right: 1px solid var(--border-color);
}

.dia-columna.dia-hoy {
  background-color: rgba(0, 123, 255, 0.02);
}

.franjas-contenedor {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  pointer-events: auto;
}

.franja-celda {
  border-bottom: 1px solid rgba(222, 226, 230, 0.5);
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
  z-index: 2;
}

.franja-celda.franja-disponible:hover {
  background-color: rgba(0, 123, 255, 0.08);
}

.franja-celda.franja-ocupada {
  background-color: rgba(108, 117, 125, 0.1);
  cursor: not-allowed;
  background-image: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 2px,
    rgba(108, 117, 125, 0.1) 2px,
    rgba(108, 117, 125, 0.1) 4px
  );
}

.franja-celda.franja-ocupada:hover {
  background-color: rgba(108, 117, 125, 0.15);
}

.linea-hora {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background-color: var(--border-color);
}

.bloques-contenedor {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 10;
  padding: 0 4px;
  pointer-events: none;
}

/* Bloques de horario */
.bloque-horario {
  position: absolute;
  left: 4px;
  right: 4px;
  border-radius: 6px;
  border: 1px solid;
  cursor: pointer;
  transition: all 0.2s;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  pointer-events: auto;
}

.bloque-horario:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  z-index: 20;
}

.bloque-contenido {
  padding: 6px 8px;
  color: white;
  font-size: 12px;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.bloque-tipo {
  font-weight: 600;
  margin-bottom: 2px;
  line-height: 1.2;
}

.bloque-tiempo {
  font-size: 10px;
  opacity: 0.9;
  margin-bottom: 2px;
}

.bloque-notas {
  font-size: 9px;
  opacity: 0.8;
  line-height: 1.1;
}

.bloque-acciones {
  position: absolute;
  top: 4px;
  right: 4px;
  display: flex;
  gap: 2px;
  opacity: 0;
  transition: opacity 0.2s;
}

.bloque-horario:hover .bloque-acciones {
  opacity: 1;
}

.btn-icono {
  width: 18px;
  height: 18px;
  border: none;
  border-radius: 3px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  transition: background-color 0.2s;
}

.btn-icono:hover {
  background: rgba(255, 255, 255, 0.3);
}

.btn-icono.btn-peligro:hover {
  background: rgba(220, 53, 69, 0.8);
}

/* Controles de formulario */
.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border-color);
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-control:disabled {
  background-color: var(--light-color);
  color: var(--secondary-color);
}

/* Botones */
.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #0056b3;
  transform: translateY(-1px);
}

.btn-outline {
  background-color: transparent;
  border: 1px solid var(--border-color);
  color: var(--dark-color);
}

.btn-outline:hover:not(:disabled) {
  background-color: var(--hover-color);
  border-color: var(--secondary-color);
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Alertas */
.alert {
  padding: 12px 16px;
  border-radius: 6px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert-danger {
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

/* Modal */
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
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border-color);
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--dark-color);
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-cerrar {
  width: 32px;
  height: 32px;
  border: none;
  background: none;
  font-size: 20px;
  cursor: pointer;
  color: var(--secondary-color);
  border-radius: 50%;
  transition: background-color 0.2s;
}

.btn-cerrar:hover {
  background-color: var(--hover-color);
}

.modal-cuerpo {
  padding: 24px;
  max-height: 60vh;
  overflow-y: auto;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

/* Formulario */
.form-grupo {
  margin-bottom: 20px;
}

.form-etiqueta {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--dark-color);
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.form-fila {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 20px;
}

.form-ayuda {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: var(--secondary-color);
}

.vista-previa-color {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 8px 12px;
  background: var(--light-color);
  border-radius: 6px;
}

.muestra-color {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.duracion-info {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(0, 123, 255, 0.1);
  border-radius: 6px;
  margin-bottom: 20px;
  color: var(--primary-color);
  font-weight: 500;
  font-size: 14px;
}

/* Responsive */
@media (max-width: 1200px) {
  .calendario-header,
  .calendario-cuerpo {
    grid-template-columns: 60px repeat(7, 1fr);
  }
  
  .hora-header {
    padding: 12px 4px;
  }
  
  .dia-header {
    padding: 12px 6px;
  }
}

@media (max-width: 768px) {
  .gestor-horarios-container {
    padding: 16px;
  }
  
  .header-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-controls {
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .control-grupo {
    min-width: 180px;
  }
  
  .semana-navegacion {
    justify-content: center;
  }
  
  .form-fila {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .modal-container {
    margin: 10px;
    max-width: none;
  }
  
  .calendario-header,
  .calendario-cuerpo {
    grid-template-columns: 50px repeat(7, minmax(0, 1fr));
  }
  
  .dia-nombre {
    font-size: 12px;
  }
  
  .dia-fecha {
    font-size: 10px;
  }
  
  .bloque-contenido {
    font-size: 10px;
    padding: 4px 6px;
  }
  
  .bloque-tipo {
    font-size: 10px;
  }
  
  .bloque-tiempo {
    font-size: 8px;
  }
}

@media (max-width: 480px) {
  .calendario-header,
  .calendario-cuerpo {
    grid-template-columns: 40px repeat(7, minmax(0, 1fr));
  }
  
  .dia-estadisticas {
    display: none;
  }
  
  .bloque-acciones {
    display: none;
  }
}
</style>