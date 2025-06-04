<template>
  <div class="doctores-container">
    <h2>Doctores</h2>
    
    <!-- Tabla de Doctores -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Especialidad</th>
            <th>Activo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="doctor in doctores" :key="doctor.id">
            <td>{{ doctor.id }}</td>
            <td>{{ doctor.nombre }} {{ doctor.apellido }}</td>
            <td>{{ doctor.especialidad }}</td>
            <td>
              <div class="form-check form-switch">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  :id="'activo-' + doctor.id" 
                  v-model="doctor.activo" 
                  @change="toggleActivo(doctor)"
                >
              </div>
            </td>
            <td>
              <button 
                class="btn btn-sm btn-primary" 
                @click="verHorarios(doctor)"
                :disabled="!doctor.activo"
              >
                Ver Horarios
              </button>
            </td>
          </tr>
          <tr v-if="doctores.length === 0">
            <td colspan="5" class="text-center">No hay doctores registrados</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DoctoresTable',
  data() {
    return {
      doctores: [],
      alert: {
        show: false,
        type: 'success',
        message: ''
      }
    };
  },
  created() {
    this.loadDoctores();
  },
  methods: {
    loadDoctores() {
      axios.get('/api/vertice/listar_doctores.php')
        .then(response => {
          // Asegurarse de que los valores booleanos se manejen correctamente
          this.doctores = response.data.data.map(doctor => {
            // Convertir el valor de activo a booleano
            doctor.activo = doctor.activo === '1' || doctor.activo === 1 || doctor.activo === true;
            return doctor;
          });
          console.log('Doctores cargados:', this.doctores);
        })
        .catch(error => {
          console.error('Error cargando doctores:', error);
          this.showAlert('danger', 'Error al cargar los doctores');
        });
    },
    toggleActivo(doctor) {
      // Convertir el valor booleano a 1 o 0 para la API
      const activoValue = doctor.activo ? 1 : 0;
      
      axios.put('/api/vertice/actualizar_doctor.php', {
        doctor_id: doctor.id,
        activo: activoValue
      })
        .then(response => {
          this.showAlert('success', `Doctor ${doctor.activo ? 'activado' : 'desactivado'} exitosamente`);
        })
        .catch(error => {
          console.error('Error actualizando doctor:', error);
          this.showAlert('danger', 'Error al actualizar el estado del doctor');
          // Revertir cambio en caso de error
          doctor.activo = !doctor.activo;
        });
    },
    verHorarios(doctor) {
      this.$router.push({ 
        name: 'vertice-horarios', 
        query: { doctorId: doctor.id } 
      });
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
    }
  }
};
</script>

<style scoped>
.doctores-container {
  padding: 20px;
}

.form-check-input {
  cursor: pointer;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.alert {
  margin-bottom: 20px;
}
</style>