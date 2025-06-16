<template>
  <div>
    <!-- Botón simple que redirige a la nueva página -->
    <router-link to="/crear-cita" class="btn-crear">
      <i class="fas fa-plus-circle"></i> Nueva Cita
    </router-link>
  </div>
</template>

<script>
<template>
  <div>
    <!-- Botón simple que redirige a la nueva página -->
    <router-link to="/crear-cita" class="btn-crear">
      <i class="fas fa-plus-circle"></i> Nueva Cita
    </router-link>
  </div>
</template>

<script>
export default {
  name: 'CrearCita'
};
</script>

<style scoped>
.btn-crear {
  background-color: #28a745;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  font-size: 14px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-crear:hover {
  background-color: #218838;
  transform: translateY(-1px);
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
  text-decoration: none;
  color: white;
}

.btn-crear:active {
  transform: translateY(0);
}
</style>