<template>
  <div class="gestor-horarios-container">
    <div class="header-section">
      <h1>Gestión de Horarios</h1>
      <div class="header-controls">
        <div class="doctor-selector" v-if="userRole === 'admin' || userRole === 'coordinador'">
          <label>Doctor:</label>
          <select v-model="doctorSeleccionado" @change="cargarHorarios" class="form-control">
            <option value="">Seleccione un doctor</option>
            <option v-for="doctor in doctores" :key="doctor.id" :value="doctor.id">
              {{ doctor.nombre }} {{ doctor.apellido }} - {{ doctor.especialidad_nombre }}
            </option>
          </select>
        </div>
        
        <div class="week-selector">
          <label>Semana:</label>
          <input 
            type="week" 
            v-model="semanaSeleccionada" 
            @change="cargarHorarios"
            class="form-control"
          />
        </div>
        
        <button @click="irSemanaAnterior" class="btn btn-outline">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button @click="irSemanaActual" class="btn btn-outline">Hoy</button>
        <button @click="irSemanaSiguiente" class="btn btn-outline">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner"></div>
      <p>Cargando horarios...</p>
    </div>

    <div v-else-if="!doctorSeleccionado && (userRole === 'admin' || userRole === 'coordinador')" class="empty-state">
      <i class="fas fa-user-md"></i>
      <p>Seleccione un doctor para gestionar sus horarios</p>
    </div>

    <div v-else class="horarios-grid">
      <!-- Header con días de la semana -->
      <div class="grid-header">
        <div class="time-column-header">Hora</div>
        <div v-for="dia in diasSemana" :key="dia.fecha" class="day-header">
          <div class="day-name">{{ dia.nombre }}</div>
          <div class="day-date">{{ formatearFechaCompleta(dia.fecha) }}</div>
        </div>
      </div>

      <!-- Grid de horarios -->
      <div class="grid-body">
        <div class="time-slots">
          <div 
            v-for="hora in horasDisponibles" 
            :key="hora"
            class="time-slot"
          >
            {{ hora }}
          </div>
        </div>

        <div 
          v-for="dia in diasSemana" 
          :key="dia.fecha"
          class="day-column"
          @drop="onDrop($event, dia.fecha)"
          @dragover.prevent
          @dragenter.prevent
        >
          <div 
            v-for="hora in horasDisponibles" 
            :key="`${dia.fecha}-${hora}`"
            class="time-cell"
            :data-fecha="dia.fecha"
            :data-hora="hora"
            @click="iniciarNuevoBloque(dia.fecha, hora)"
          >
            <!-- Bloques existentes -->
            <div 
              v-for="bloque in obtenerBloquesEnCelda(dia.fecha, hora)"
              :key="bloque.id"
              class="bloque-horario"
              :style="{ 
                backgroundColor: bloque.color, 
                height: `${bloque.duracion_slots * 30}px`,
                zIndex: 10
              }"
              :title="bloque.tooltip"
              draggable="true"
              @dragstart="onDragStart($event, bloque)"
              @click.stop="editarBloque(bloque)"
            >
              <div class="bloque-content">
                <div class="bloque-tipo">{{ bloque.tipo_nombre }}</div>
                <div class="bloque-tiempo">{{ bloque.hora_inicio }} - {{ bloque.hora_fin }}</div>
              </div>
              <div class="bloque-resize-handle" @mousedown="iniciarResize($event, bloque)"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal para crear/editar bloque -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>{{ editandoBloque ? 'Editar Bloque' : 'Nuevo Bloque Horario' }}</h2>
          <button @click="cerrarModal" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Tipo de Bloque:</label>
            <select v-model="bloqueForm.tipo_bloque_id" class="form-control" required>
              <option value="">Seleccione un tipo</option>
              <option 
                v-for="tipo in tiposBloque" 
                :key="tipo.id" 
                :value="tipo.id"
                :style="{ backgroundColor: tipo.color, color: 'white' }"
              >
                {{ tipo.nombre }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Fecha:</label>
            <input 
              type="date" 
              v-model="bloqueForm.fecha" 
              class="form-control"
              required
              @change="actualizarDiaSemana"
            />
            <small class="form-text">{{ obtenerNombreDia(bloqueForm.fecha) }}</small>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Hora Inicio:</label>
              <input 
                type="time" 
                v-model="bloqueForm.hora_inicio" 
                class="form-control"
                step="1800"
                required
              />
            </div>
            
            <div class="form-group">
              <label>Hora Fin:</label>
              <input 
                type="time" 
                v-model="bloqueForm.hora_fin" 
                class="form-control"
                step="1800"
                required
              />
            </div>
          </div>

          <div class="form-group">
            <label>Notas:</label>
            <textarea 
              v-model="bloqueForm.notas" 
              class="form-control"
              rows="3"
              placeholder="Notas adicionales (opcional)"
            ></textarea>
          </div>

          <div v-if="errorModal" class="alert alert-danger">
            {{ errorModal }}
          </div>

          <div class="modal-footer">
            <button type="button" @click="cerrarModal" class="btn btn-outline">
              Cancelar
            </button>
            <button 
              v-if="editandoBloque" 
              type="button" 
              @click="eliminarBloque" 
              class="btn btn-danger"
              :disabled="guardando"
            >
              Eliminar
            </button>
            <button 
              type="button" 
              @click="guardarBloque" 
              class="btn btn-primary"
              :disabled="guardando"
            >
              {{ guardando ? 'Guardando...' : (editandoBloque ? 'Actualizar' : 'Crear') }}
            </button>
          </div>
        </div>
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
      doctores: [],
      tiposBloque: [],
      horarios: [],
      doctorSeleccionado: '',
      semanaSeleccionada: '',
      loading: false,
      showModal: false,
      editandoBloque: false,
      guardando: false,
      errorModal: '',
      
      bloqueForm: {
        id: '',
        doctor_id: '',
        tipo_bloque_id: '',
        fecha: '',
        dia_semana: '',
        hora_inicio: '',
        hora_fin: '',
        notas: ''
      },
      
      diasSemana: [],
      horasDisponibles: [],
      
      // Para drag and drop
      draggedBloque: null,
      resizingBloque: null,
      resizeStartY: 0,
      resizeStartHeight: 0
    };
  },
  computed: {
    authStore() {
      return useAuthStore();
    },
    userRole() {
      return this.authStore.userRole;
    },
    currentUserId() {
      return this.authStore.user?.id;
    }
  },
  mounted() {
    this.inicializarComponente();
  },
  methods: {
    async inicializarComponente() {
      this.generarHorasDisponibles();
      await this.cargarTiposBloque();
      
      // Si es doctor, cargar solo sus horarios
      if (this.userRole === 'doctor') {
        await this.cargarDoctorActual();
      } else {
        await this.cargarDoctores();
      }
      
      this.establecerSemanaActual();
    },

    async cargarDoctorActual() {
      try {
        const token = localStorage.getItem('token');
        const response = await axios.get('/api/doctores/perfil.php', {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (response.data && response.data.id) {
          this.doctorSeleccionado = response.data.id;
          await this.cargarHorarios();
        }
      } catch (error) {
        console.error('Error al cargar doctor actual:', error);
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

    async cargarHorarios() {
      if (!this.doctorSeleccionado || !this.semanaSeleccionada) return;
      
      this.loading = true;
      try {
        const token = localStorage.getItem('token');
        const fechaInicio = this.obtenerFechaInicioSemana();
        
        console.log('Cargando horarios para:', {
          doctor_id: this.doctorSeleccionado,
          fecha_inicio: fechaInicio
        });
        
        const response = await axios.get(`/api/horarios/gestionar.php?doctor_id=${this.doctorSeleccionado}&fecha_inicio=${fechaInicio}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        console.log('Horarios recibidos del servidor:', response.data);
        
        this.horarios = response.data.map(horario => ({
          ...horario,
          color: this.obtenerColorTipo(horario.tipo_bloque_id),
          tipo_nombre: this.obtenerNombreTipo(horario.tipo_bloque_id),
          duracion_slots: this.calcularDuracionSlots(horario.hora_inicio, horario.hora_fin),
          tooltip: `${this.obtenerNombreTipo(horario.tipo_bloque_id)}\n${horario.hora_inicio} - ${horario.hora_fin}`
        }));
        
        console.log('Horarios procesados:', this.horarios);
        
        this.generarDiasSemana();
      } catch (error) {
        console.error('Error al cargar horarios:', error);
      } finally {
        this.loading = false;
      }
    },

    generarHorasDisponibles() {
      const horas = [];
      for (let h = 7; h <= 18; h++) {
        for (let m = 0; m < 60; m += 30) {
          const hora = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
          horas.push(hora);
        }
      }
      this.horasDisponibles = horas;
    },

    establecerSemanaActual() {
      const hoy = new Date();
      const año = hoy.getFullYear();
      const semana = this.obtenerNumeroSemana(hoy);
      this.semanaSeleccionada = `${año}-W${semana.toString().padStart(2, '0')}`;
      this.generarDiasSemana();
    },

    obtenerNumeroSemana(fecha) {
      const startDate = new Date(fecha.getFullYear(), 0, 1);
      const days = Math.floor((fecha - startDate) / (24 * 60 * 60 * 1000));
      return Math.ceil(days / 7);
    },

    generarDiasSemana() {
      if (!this.semanaSeleccionada) return;
      
      const fechaInicio = this.obtenerFechaInicioSemana();
      console.log('Generando días para semana:', this.semanaSeleccionada);
      console.log('Fecha inicio calculada:', fechaInicio);
      
      this.diasSemana = [];
      const nombres = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
      
      for (let i = 0; i < 7; i++) {
        const fecha = new Date(fechaInicio + 'T00:00:00'); // Agregar hora para evitar problemas de zona horaria
        fecha.setDate(fecha.getDate() + i);
        const fechaStr = fecha.toISOString().split('T')[0];
        
        this.diasSemana.push({
          nombre: nombres[i],
          fecha: fechaStr,
          diaSemana: i + 1
        });
      }
      
      console.log('Días generados:', this.diasSemana);
    },

    obtenerFechaInicioSemana() {
      if (!this.semanaSeleccionada) return '';
      
      const [año, semana] = this.semanaSeleccionada.split('-W');
      
      // Crear fecha del 4 de enero (siempre está en la semana 1)
      const cuatroEnero = new Date(año, 0, 4);
      
      // Encontrar el lunes de esa semana
      const diaCuatroEnero = cuatroEnero.getDay();
      const diasHastaLunes = diaCuatroEnero === 0 ? 6 : diaCuatroEnero - 1;
      const primerLunes = new Date(cuatroEnero);
      primerLunes.setDate(4 - diasHastaLunes);
      
      // Calcular el lunes de la semana solicitada
      const fechaObjetivo = new Date(primerLunes);
      fechaObjetivo.setDate(primerLunes.getDate() + (parseInt(semana) - 1) * 7);
      
      const resultado = fechaObjetivo.toISOString().split('T')[0];
      
      console.log('Cálculo de semana:', {
        semanaSeleccionada: this.semanaSeleccionada,
        año: año,
        semana: semana,
        cuatroEnero: cuatroEnero.toISOString().split('T')[0],
        primerLunes: primerLunes.toISOString().split('T')[0],
        fechaCalculada: resultado
      });
      
      return resultado;
    },

    irSemanaAnterior() {
      const [año, semana] = this.semanaSeleccionada.split('-W');
      let nuevaSemana = parseInt(semana) - 1;
      let nuevoAño = parseInt(año);
      
      if (nuevaSemana < 1) {
        nuevaSemana = 52;
        nuevoAño--;
      }
      
      this.semanaSeleccionada = `${nuevoAño}-W${nuevaSemana.toString().padStart(2, '0')}`;
      this.cargarHorarios();
    },

    irSemanaSiguiente() {
      const [año, semana] = this.semanaSeleccionada.split('-W');
      let nuevaSemana = parseInt(semana) + 1;
      let nuevoAño = parseInt(año);
      
      if (nuevaSemana > 52) {
        nuevaSemana = 1;
        nuevoAño++;
      }
      
      this.semanaSeleccionada = `${nuevoAño}-W${nuevaSemana.toString().padStart(2, '0')}`;
      this.cargarHorarios();
    },

    irSemanaActual() {
      this.establecerSemanaActual();
      this.cargarHorarios();
    },

    obtenerBloquesEnCelda(fecha, hora) {
      const bloques = this.horarios.filter(horario => {
        // Calcular la fecha real del bloque basada en fecha_inicio y dia_semana
        const fechaInicioSemana = new Date(horario.fecha_inicio + 'T00:00:00');
        
        // dia_semana: 1=Lunes, 2=Martes, ..., 7=Domingo
        // Ajustar para obtener la fecha correcta
        const diasAgregar = horario.dia_semana - 1;
        const fechaRealBloque = new Date(fechaInicioSemana);
        fechaRealBloque.setDate(fechaRealBloque.getDate() + diasAgregar);
        
        const fechaBloqueStr = fechaRealBloque.toISOString().split('T')[0];
        
        // Verificar que coincida la fecha calculada
        if (fechaBloqueStr !== fecha) return false;
        
        // Verificar que coincida la hora de inicio
        // Normalizar el formato de hora (quitar segundos si los tiene)
        const horaInicioBloque = horario.hora_inicio.substring(0, 5); // "07:00:00" -> "07:00"
        const horaBuscada = hora.substring(0, 5); // Por si acaso tiene segundos
        
        return horaInicioBloque === horaBuscada;
      });
      
      // Solo mostrar logs si hay bloques o si estamos buscando en una celda específica
      if (bloques.length > 0 || (fecha.includes('2025-06-17') && hora === '07:00')) {
        console.log('Buscando bloques en celda:', {
          fecha_buscada: fecha,
          hora_buscada: hora,
          horarios_disponibles: this.horarios.length,
          bloques_encontrados: bloques.length,
          detalles_horarios: this.horarios.map(h => ({
            id: h.id,
            fecha_inicio: h.fecha_inicio,
            dia_semana: h.dia_semana,
            hora_inicio: h.hora_inicio,
            fecha_calculada: (() => {
              const fi = new Date(h.fecha_inicio + 'T00:00:00');
              const fr = new Date(fi);
              fr.setDate(fr.getDate() + (h.dia_semana - 1));
              return fr.toISOString().split('T')[0];
            })()
          }))
        });
      }
      
      return bloques;
    },

    calcularDuracionSlots(horaInicio, horaFin) {
      const [hI, mI] = horaInicio.split(':').map(Number);
      const [hF, mF] = horaFin.split(':').map(Number);
      
      const minutosInicio = hI * 60 + mI;
      const minutosFin = hF * 60 + mF;
      
      return (minutosFin - minutosInicio) / 30;
    },

    obtenerColorTipo(tipoId) {
      const tipo = this.tiposBloque.find(t => t.id == tipoId);
      return tipo ? tipo.color : '#6c757d';
    },

    obtenerNombreTipo(tipoId) {
      const tipo = this.tiposBloque.find(t => t.id == tipoId);
      return tipo ? tipo.nombre : 'Desconocido';
    },

    formatearFechaCompleta(fecha) {
      const date = new Date(fecha);
      const diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      const nombreDia = diasSemana[date.getDay()];
      const fechaFormateada = date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: '2-digit' 
      });
      return `${nombreDia}, ${fechaFormateada}`;
    },

    obtenerNombreDia(fecha) {
      if (!fecha) return '';
      const date = new Date(fecha);
      const diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      return diasSemana[date.getDay()];
    },

    actualizarDiaSemana() {
      if (this.bloqueForm.fecha) {
        const fecha = new Date(this.bloqueForm.fecha);
        const dia = fecha.getDay();
        this.bloqueForm.dia_semana = dia === 0 ? 7 : dia; // Convertir domingo (0) a 7
      }
    },

    iniciarNuevoBloque(fecha, hora) {
      const dia = new Date(fecha).getDay();
      const diaSemana = dia === 0 ? 7 : dia; // Convertir domingo (0) a 7
      
      this.bloqueForm = {
        id: '',
        doctor_id: this.doctorSeleccionado,
        tipo_bloque_id: '',
        fecha: fecha,
        dia_semana: diaSemana,
        hora_inicio: hora,
        hora_fin: this.sumarMinutos(hora, 30),
        notas: ''
      };
      
      this.editandoBloque = false;
      this.errorModal = '';
      this.showModal = true;
    },

    editarBloque(bloque) {
      // Calcular la fecha real del bloque
      const fechaInicioSemana = new Date(bloque.fecha_inicio);
      const diasAgregar = bloque.dia_semana - 1;
      fechaInicioSemana.setDate(fechaInicioSemana.getDate() + diasAgregar);
      const fechaReal = fechaInicioSemana.toISOString().split('T')[0];
      
      this.bloqueForm = {
        id: bloque.id,
        doctor_id: bloque.doctor_id,
        tipo_bloque_id: bloque.tipo_bloque_id,
        fecha: fechaReal,
        dia_semana: bloque.dia_semana,
        hora_inicio: bloque.hora_inicio,
        hora_fin: bloque.hora_fin,
        notas: bloque.notas || ''
      };
      
      this.editandoBloque = true;
      this.errorModal = '';
      this.showModal = true;
    },

    sumarMinutos(hora, minutos) {
      const [h, m] = hora.split(':').map(Number);
      const totalMinutos = h * 60 + m + minutos;
      const nuevaHora = Math.floor(totalMinutos / 60);
      const nuevosMinutos = totalMinutos % 60;
      
      return `${nuevaHora.toString().padStart(2, '0')}:${nuevosMinutos.toString().padStart(2, '0')}`;
    },

    cerrarModal() {
      this.showModal = false;
      this.editandoBloque = false;
      this.bloqueForm = {
        id: '',
        doctor_id: '',
        tipo_bloque_id: '',
        fecha: '',
        dia_semana: '',
        hora_inicio: '',
        hora_fin: '',
        notas: ''
      };
      this.errorModal = '';
    },

    async guardarBloque() {
      // Validaciones
      if (!this.bloqueForm.tipo_bloque_id) {
        this.errorModal = 'Debe seleccionar un tipo de bloque';
        return;
      }
      
      if (!this.bloqueForm.fecha) {
        this.errorModal = 'Debe seleccionar una fecha';
        return;
      }
      
      if (!this.bloqueForm.hora_inicio || !this.bloqueForm.hora_fin) {
        this.errorModal = 'Debe especificar hora de inicio y fin';
        return;
      }
      
      if (this.bloqueForm.hora_inicio >= this.bloqueForm.hora_fin) {
        this.errorModal = 'La hora de inicio debe ser menor que la hora de fin';
        return;
      }

      // Asegurar que dia_semana esté actualizado
      this.actualizarDiaSemana();

      // Asegurar que doctor_id esté establecido
      if (!this.bloqueForm.doctor_id) {
        this.bloqueForm.doctor_id = this.doctorSeleccionado;
      }

      // Preparar datos completos
      const datosCompletos = {
        doctor_id: parseInt(this.bloqueForm.doctor_id),
        tipo_bloque_id: parseInt(this.bloqueForm.tipo_bloque_id),
        fecha: this.bloqueForm.fecha,
        dia_semana: parseInt(this.bloqueForm.dia_semana),
        hora_inicio: this.bloqueForm.hora_inicio,
        hora_fin: this.bloqueForm.hora_fin,
        notas: this.bloqueForm.notas || ''
      };

      // Si es edición, agregar el ID
      if (this.editandoBloque) {
        datosCompletos.id = parseInt(this.bloqueForm.id);
      }

      console.log('Datos completos a enviar:', datosCompletos);

      this.guardando = true;
      try {
        const token = localStorage.getItem('token');
        const url = '/api/horarios/gestionar.php';
        const method = this.editandoBloque ? 'put' : 'post';
        
        const response = await axios[method](url, datosCompletos, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });

        console.log('Respuesta del servidor:', response.data);

        if (response.data && response.data.success) {
          await this.cargarHorarios();
          this.cerrarModal();
        } else {
          this.errorModal = response.data?.error || 'Error al guardar el bloque';
        }
      } catch (error) {
        console.error('Error completo:', error);
        console.error('Response data:', error.response?.data);
        console.error('Response status:', error.response?.status);
        
        if (error.response?.status === 500) {
          this.errorModal = 'Error interno del servidor. Revise los logs del servidor.';
        } else if (error.response?.data?.error) {
          this.errorModal = error.response.data.error;
        } else {
          this.errorModal = 'Error al guardar el bloque horario';
        }
      } finally {
        this.guardando = false;
      }
    },

    async eliminarBloque() {
      if (!confirm('¿Está seguro de que desea eliminar este bloque horario?')) {
        return;
      }

      this.guardando = true;
      try {
        const token = localStorage.getItem('token');
        await axios.delete('/api/horarios/gestionar.php', {
          headers: { 'Authorization': `Bearer ${token}` },
          data: { id: this.bloqueForm.id }
        });

        await this.cargarHorarios();
        this.cerrarModal();
      } catch (error) {
        console.error('Error al eliminar bloque:', error);
        this.errorModal = error.response?.data?.error || 'Error al eliminar el bloque horario';
      } finally {
        this.guardando = false;
      }
    },

    // Drag and Drop
    onDragStart(event, bloque) {
      this.draggedBloque = bloque;
      event.dataTransfer.effectAllowed = 'move';
    },

    onDrop(event, nuevaFecha) {
      if (!this.draggedBloque) return;
      
      const rect = event.target.getBoundingClientRect();
      const y = event.clientY - rect.top;
      const slotHeight = 30;
      const slotIndex = Math.floor(y / slotHeight);
      
      if (slotIndex >= 0 && slotIndex < this.horasDisponibles.length) {
        const nuevaHora = this.horasDisponibles[slotIndex];
        this.moverBloque(this.draggedBloque, nuevaFecha, nuevaHora);
      }
      
      this.draggedBloque = null;
    },

    async moverBloque(bloque, nuevaFecha, nuevaHora) {
      const duracionMinutos = this.calcularDuracionMinutos(bloque.hora_inicio, bloque.hora_fin);
      const nuevaHoraFin = this.sumarMinutos(nuevaHora, duracionMinutos);
      
      const datosActualizados = {
        id: bloque.id,
        fecha: nuevaFecha,
        dia_semana: new Date(nuevaFecha).getDay() || 7,
        hora_inicio: nuevaHora,
        hora_fin: nuevaHoraFin
      };

      try {
        const token = localStorage.getItem('token');
        await axios.put('/api/horarios/gestionar.php', datosActualizados, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        await this.cargarHorarios();
      } catch (error) {
        console.error('Error al mover bloque:', error);
        alert('Error al mover el bloque horario');
      }
    },

    calcularDuracionMinutos(horaInicio, horaFin) {
      const [hI, mI] = horaInicio.split(':').map(Number);
      const [hF, mF] = horaFin.split(':').map(Number);
      
      const minutosInicio = hI * 60 + mI;
      const minutosFin = hF * 60 + mF;
      
      return minutosFin - minutosInicio;
    },

    // Resize functionality
    iniciarResize(event, bloque) {
      event.stopPropagation();
      this.resizingBloque = bloque;
      this.resizeStartY = event.clientY;
      this.resizeStartHeight = this.calcularDuracionSlots(bloque.hora_inicio, bloque.hora_fin) * 30;
      
      document.addEventListener('mousemove', this.onResize);
      document.addEventListener('mouseup', this.finalizarResize);
    },

    onResize(event) {
      if (!this.resizingBloque) return;
      
      const deltaY = event.clientY - this.resizeStartY;
      const nuevaAltura = this.resizeStartHeight + deltaY;
      const nuevosSlots = Math.max(1, Math.round(nuevaAltura / 30));
      
      const elemento = event.target.closest('.bloque-horario');
      if (elemento) {
        elemento.style.height = `${nuevosSlots * 30}px`;
      }
    },

    async finalizarResize() {
      if (!this.resizingBloque) return;
      
      const nuevaAltura = parseInt(document.querySelector('.bloque-horario').style.height);
      const nuevosSlots = Math.round(nuevaAltura / 30);
      const nuevosMinutos = nuevosSlots * 30;
      const nuevaHoraFin = this.sumarMinutos(this.resizingBloque.hora_inicio, nuevosMinutos);
      
      try {
        const token = localStorage.getItem('token');
        await axios.put('/api/horarios/gestionar.php', {
          id: this.resizingBloque.id,
          hora_fin: nuevaHoraFin
        }, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        await this.cargarHorarios();
      } catch (error) {
        console.error('Error al redimensionar bloque:', error);
        await this.cargarHorarios();
      }
      
      document.removeEventListener('mousemove', this.onResize);
      document.removeEventListener('mouseup', this.finalizarResize);
      this.resizingBloque = null;
    }
  }
};
</script>

<style scoped>
.gestor-horarios-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.header-controls {
  display: flex;
  align-items: center;
  gap: 15px;
  flex-wrap: wrap;
}

.doctor-selector, .week-selector {
  display: flex;
  flex-direction: column;
  min-width: 200px;
}

.doctor-selector label, .week-selector label {
  margin-bottom: 5px;
  font-weight: 500;
  font-size: 14px;
}

.form-control {
  padding: 8px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 14px;
}

.form-text {
  color: #6c757d;
  font-size: 12px;
  margin-top: 5px;
}

.btn {
  padding: 8px 12px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #ced4da;
  color: var(--dark-color);
}

.btn-outline:hover {
  background-color: #e9ecef;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-danger {
  background-color: var(--danger-color);
  color: white;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
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

.horarios-grid {
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.grid-header {
  display: grid;
  grid-template-columns: 80px repeat(7, 1fr);
  background-color: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}

.time-column-header {
  padding: 15px 10px;
  font-weight: 600;
  text-align: center;
  border-right: 1px solid #dee2e6;
}

.day-header {
  padding: 15px 10px;
  text-align: center;
  border-right: 1px solid #dee2e6;
}

.day-name {
  font-weight: 600;
  color: var(--dark-color);
  margin-bottom: 5px;
}

.day-date {
  font-size: 12px;
  color: var(--secondary-color);
}

.grid-body {
  display: grid;
  grid-template-columns: 80px repeat(7, 1fr);
  min-height: 600px;
}

.time-slots {
  border-right: 1px solid #dee2e6;
  background-color: #f8f9fa;
}

.time-slot {
  height: 30px;
  padding: 5px;
  border-bottom: 1px solid #e9ecef;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--secondary-color);
}

.day-column {
  border-right: 1px solid #dee2e6;
  position: relative;
}

.time-cell {
  height: 30px;
  border-bottom: 1px solid #e9ecef;
  position: relative;
  cursor: pointer;
  transition: background-color 0.2s;
}

.time-cell:hover {
  background-color: #f8f9fa;
}

.bloque-horario {
  position: absolute;
  left: 2px;
  right: 2px;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.3);
  transition: transform 0.2s;
  user-select: none;
}

.bloque-horario:hover {
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.bloque-content {
  padding: 4px 8px;
  color: white;
  font-size: 12px;
  overflow: hidden;
}

.bloque-tipo {
  font-weight: 600;
  margin-bottom: 2px;
}

.bloque-tiempo {
  font-size: 10px;
  opacity: 0.9;
}

.bloque-resize-handle {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background-color: rgba(255, 255, 255, 0.3);
  cursor: ns-resize;
}

.bloque-resize-handle:hover {
  background-color: rgba(255, 255, 255, 0.6);
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
  max-width: 500px;
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
  font-size: 18px;
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

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-row .form-group {
  flex: 1;
  margin-bottom: 0;
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
  .header-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-controls {
    justify-content: center;
  }
  
  .grid-header, .grid-body {
    grid-template-columns: 60px repeat(7, 1fr);
  }
  
  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
</style>