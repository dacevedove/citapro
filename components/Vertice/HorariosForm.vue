<template>
  <div class="horarios-container">
    <h2>Disponibilidad de horarios</h2>
    
    <div class="row mb-4">
      <div class="col-md-5">
        <label>Doctor:</label>
        <select 
          class="form-select"
          v-model="selectedDoctorId"
          @change="loadHorariosByDoctor"
        >
          <option value="">-- Seleccione un doctor --</option>
          <option v-for="doctor in doctoresActivos" :key="doctor.id" :value="doctor.id">
            Dr. {{ doctor.nombre }} {{ doctor.apellido }}
          </option>
        </select>
      </div>
      
      <div class="col-md-7">
        <label>Semana:</label>
        <select class="form-select" v-model="currentWeek" @change="loadHorariosByWeek">
          <option v-for="(week, index) in availableWeeks" :key="index" :value="week.value">
            {{ week.label }}
          </option>
        </select>
      </div>
    </div>
    
    <!-- Calendario semanal -->
    <div class="weekly-calendar" v-if="selectedDoctorId">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th></th>
            <th v-for="time in timeSlots" :key="time">{{ formatDisplayTime(time) }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="day in weekDays" :key="day.value">
            <th>{{ day.label }}</th>
            <td 
              v-for="time in timeSlots" 
              :key="`${day.value}-${time}`" 
              :class="{ 'disponible': isSlotAvailable(day.value, time) }"
              @click="toggleSlot(day.value, time)"
              class="slot"
            ></td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div class="legend mt-3" v-if="selectedDoctorId">
      <div class="d-flex align-items-center gap-2">
        <div class="color-box disponible"></div>
        <span>Turno disponible</span>
      </div>
      <div class="d-flex align-items-center gap-2 ms-3">
        <div class="color-box"></div>
        <span>Turno no disponible</span>
      </div>
    </div>
    
    <div v-if="!selectedDoctorId" class="alert alert-info mt-3">
      Seleccione un doctor para ver y configurar sus horarios
    </div>
    
    <!-- Botón para copiar semana anterior -->
    <div class="mt-4" v-if="selectedDoctorId && currentWeekIndex > 0">
      <button 
        class="btn btn-secondary" 
        @click="copiarSemanaAnterior"
        :disabled="isLoading"
      >
        <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Repetir semana anterior
      </button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'HorariosForm',
  data() {
    return {
      doctoresActivos: [],
      selectedDoctorId: '',
      isLoading: false,
      timeSlots: [
        '08:00:00', '09:00:00', '10:00:00', '11:00:00', '12:00:00', 
        '13:00:00', '14:00:00', '15:00:00', '16:00:00', '17:00:00'
      ],
      weekDays: [
        { value: 1, label: 'Lunes' },
        { value: 2, label: 'Martes' },
        { value: 3, label: 'Miércoles' },
        { value: 4, label: 'Jueves' },
        { value: 5, label: 'Viernes' },
        { value: 6, label: 'Sábado' },
        { value: 7, label: 'Domingo' }
      ],
      currentWeek: '',
      availableWeeks: [],
      horarios: []
    };
  },
  computed: {
    currentWeekIndex() {
      return this.availableWeeks.findIndex(week => week.value === this.currentWeek);
    },
    weekDates() {
      if (!this.currentWeek) return [];
      
      const [startDate, endDate] = this.currentWeek.split('_');
      const start = new Date(startDate);
      const dates = [];
      
      for (let i = 0; i < 7; i++) {
        const date = new Date(start);
        date.setDate(start.getDate() + i);
        dates.push({
          day: i + 1, // 1 = lunes, 7 = domingo
          date: date.toISOString().split('T')[0]
        });
      }
      
      return dates;
    },
    currentWeekDates() {
      if (!this.currentWeek) return { start: null, end: null };
      
      const [start, end] = this.currentWeek.split('_');
      return { start, end };
    }
  },
  created() {
    this.generateWeeks();
    this.loadDoctoresActivos();
    
    // Si hay un doctor_id en la URL, seleccionarlo
    const doctorId = this.$route.query.doctorId;
    if (doctorId) {
      this.selectedDoctorId = doctorId;
    }
  },
  methods: {
    generateWeeks() {
      const weeks = [];
      const today = new Date();
      
      // Ajustar today al lunes de esta semana
      const currentDay = today.getDay();
      const diff = today.getDate() - currentDay + (currentDay === 0 ? -6 : 1);
      today.setDate(diff);
      
      // Generar semanas
      for (let i = 0; i < 12; i++) {
        const startDate = new Date(today);
        startDate.setDate(startDate.getDate() + (i * 7));
        
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);
        
        weeks.push({
          value: `${this.formatDate(startDate)}_${this.formatDate(endDate)}`,
          label: `Lunes ${this.formatDateShort(startDate)} a Domingo ${this.formatDateShort(endDate)}`
        });
      }
      
      this.availableWeeks = weeks;
      this.currentWeek = weeks[0].value;
    },
    formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    formatDateShort(date) {
      const day = date.getDate();
      const month = date.toLocaleString('es-ES', { month: 'long' });
      return `${day} de ${month}`;
    },
    loadDoctoresActivos() {
      this.isLoading = true;
      axios.get('/api/vertice/listar_doctores.php')
        .then(response => {
          this.doctoresActivos = response.data.data.filter(d => d.activo == 1);
          
          if (this.selectedDoctorId) {
            this.loadHorariosByDoctor();
          }
        })
        .catch(error => {
          console.error('Error cargando doctores:', error);
          alert('Error al cargar los doctores activos');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    loadHorariosByDoctor() {
      if (!this.selectedDoctorId) return;
      this.loadHorariosByWeek();
    },
    loadHorariosByWeek() {
      if (!this.selectedDoctorId || !this.currentWeek) return;
      this.isLoading = true;
      
      const [startDate, endDate] = this.currentWeek.split('_');
      
      // Enviar semana seleccionada al backend
      axios.get(`/api/vertice/listar_horarios.php?doctor_id=${this.selectedDoctorId}&fecha_inicio=${startDate}&fecha_fin=${endDate}`)
        .then(response => {
          console.log('Respuesta del servidor:', response.data);
          this.horarios = response.data.data || [];
        })
        .catch(error => {
          console.error('Error cargando horarios:', error);
          alert('Error al cargar los horarios');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    isSlotAvailable(dayOfWeek, timeSlot) {
      return this.horarios.some(horario => {
        return horario.dia_semana == dayOfWeek && 
               timeSlot >= horario.hora_inicio && 
               timeSlot < horario.hora_fin;
      });
    },
    toggleSlot(dayOfWeek, timeSlot) {
      if (this.isLoading) return;
      
      const available = this.isSlotAvailable(dayOfWeek, timeSlot);
      
      if (available) {
        this.deleteSlot(dayOfWeek, timeSlot);
      } else {
        this.createSlot(dayOfWeek, timeSlot);
      }
    },
    createSlot(dayOfWeek, timeSlot) {
      this.isLoading = true;
      
      // Calcular hora fin (siguiente slot)
      const timeIndex = this.timeSlots.indexOf(timeSlot);
      const horaFin = timeIndex < this.timeSlots.length - 1 
        ? this.timeSlots[timeIndex + 1] 
        : '18:00:00';
      
      const { start, end } = this.currentWeekDates;
      
      const horarioData = {
        doctor_id: this.selectedDoctorId,
        semana_inicio: start,
        semana_fin: end,
        dia_semana: dayOfWeek,
        hora_inicio: timeSlot,
        hora_fin: horaFin
      };
      
      // Para semanas futuras, calculamos la fecha específica
      if (this.currentWeekIndex > 0) {
        const fechaEspecifica = this.weekDates.find(d => d.day === dayOfWeek)?.date;
        if (fechaEspecifica) {
          horarioData.fecha = fechaEspecifica;
          delete horarioData.dia_semana; // Si usamos fecha, no usamos día
        }
      }
      
      axios.post('/api/vertice/crear_horario.php', horarioData)
        .then(() => {
          this.loadHorariosByWeek();
        })
        .catch(error => {
          console.error('Error creando horario:', error);
          alert(error.response?.data?.error || 'Error al crear el horario');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    deleteSlot(dayOfWeek, timeSlot) {
      // Encontrar el horario a eliminar
      const horarioToDelete = this.horarios.find(h => 
        h.dia_semana == dayOfWeek && 
        timeSlot >= h.hora_inicio && 
        timeSlot < h.hora_fin
      );
      
      if (horarioToDelete) {
        this.isLoading = true;
        
        axios.delete(`/api/vertice/eliminar_horario.php?id=${horarioToDelete.id}`)
          .then(() => {
            this.loadHorariosByWeek();
          })
          .catch(error => {
            console.error('Error eliminando horario:', error);
            alert('Error al eliminar el horario');
          })
          .finally(() => {
            this.isLoading = false;
          });
      }
    },
    copiarSemanaAnterior() {
      if (this.isLoading || this.currentWeekIndex <= 0) return;
      this.isLoading = true;
      
      const [currentStart, currentEnd] = this.currentWeek.split('_');
      const previousWeek = this.availableWeeks[this.currentWeekIndex - 1].value;
      const [prevStart, prevEnd] = previousWeek.split('_');
      
      axios.post('/api/vertice/copiar_semana.php', {
        doctor_id: this.selectedDoctorId,
        semana_origen_inicio: prevStart,
        semana_origen_fin: prevEnd,
        semana_destino_inicio: currentStart,
        semana_destino_fin: currentEnd
      })
        .then(() => {
          alert('Horarios copiados exitosamente');
          this.loadHorariosByWeek();
        })
        .catch(error => {
          console.error('Error copiando horarios:', error);
          alert('Error al copiar los horarios de la semana anterior');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    formatDisplayTime(time) {
      if (!time) return '';
      const [hours, minutes] = time.split(':');
      const hour = parseInt(hours);
      const ampm = hour >= 12 ? 'pm' : 'am';
      const hour12 = hour % 12 || 12;
      return `${hour12}:${minutes}${ampm}`;
    }
  }
};
</script>

<style scoped>
.horarios-container {
  padding: 20px;
}

.weekly-calendar {
  margin-top: 20px;
}

.weekly-calendar th {
  text-align: center;
  vertical-align: middle;
  padding: 10px;
  background-color: #f8f9fa;
}

.slot {
  height: 50px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.slot.disponible {
  background-color: #72c274;
}

.legend {
  display: flex;
}

.color-box {
  width: 20px;
  height: 20px;
  border: 1px solid #dee2e6;
}

.color-box.disponible {
  background-color: #72c274;
}
</style>