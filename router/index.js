import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../store/auth';

// Componentes de autenticación
import Login from '../components/Auth/Login.vue';

// Componentes compartidos
import Dashboard from '../components/Shared/Dashboard.vue';
import Perfil from '../components/Shared/Perfil.vue';

// Componentes de administrador
import AsignarCitas from '../components/Admin/AsignarCitas.vue';
import Doctores from '../components/Admin/Doctores.vue';
import Aseguradoras from '../components/Admin/Aseguradoras.vue';
import Pacientes from '../components/Admin/Pacientes.vue';
import Usuarios from '../components/Admin/Usuarios.vue';

// Componentes de gestión de horarios
import GestorHorarios from '../components/Horarios/GestorHorarios.vue';
import TiposBloqueHorario from '../components/Horarios/TiposBloqueHorario.vue';
import EspecialidadesCrud from '../components/Doctores/EspecialidadesCrud.vue';

// Componentes de doctores
import DoctorDashboard from '../components/Doctor/DoctorDashboard.vue';
import AceptarCitas from '../components/Doctor/AceptarCitas.vue';
import CitasDisponibles from '../components/Doctor/CitasDisponibles.vue';
import HistorialDoctor from '../components/Doctor/HistorialDoctor.vue';

// Componentes de aseguradoras
import AseguradoraDashboard from '../components/Aseguradora/AseguradoraDashboard.vue';
import SolicitarCita from '../components/Aseguradora/SolicitarCita.vue';
import Titulares from '../components/Aseguradora/Titulares.vue';
import PacientesAseguradora from '../components/Aseguradora/PacientesAseguradora.vue';
import HistorialAseguradora from '../components/Aseguradora/HistorialAseguradora.vue';

// Componentes de coordinador
import CoordinadorDashboard from '../components/Coordinador/CoordinadorDashboard.vue';
import CoordinadorCitas from '../components/Coordinador/CoordinadorCitas.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    redirect: '/dashboard'
  },

  // Rutas compartidas
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/perfil',
    name: 'Perfil',
    component: Perfil,
    meta: { requiresAuth: true }
  },

  // Rutas de administrador
  {
    path: '/admin',
    redirect: '/admin/dashboard',
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: Dashboard,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/citas',
    name: 'AdminCitas',
    component: AsignarCitas,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/doctores',
    name: 'AdminDoctores',
    component: Doctores,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/horarios',
    name: 'AdminHorarios',
    component: GestorHorarios,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/especialidades',
    name: 'AdminEspecialidades',
    component: EspecialidadesCrud,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/tipos-bloque',
    name: 'AdminTiposBloque',
    component: TiposBloqueHorario,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/aseguradoras',
    name: 'AdminAseguradoras',
    component: Aseguradoras,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/pacientes',
    name: 'AdminPacientes',
    component: Pacientes,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/admin/usuarios',
    name: 'AdminUsuarios',
    component: Usuarios,
    meta: { requiresAuth: true, roles: ['admin'] }
  },

  // Rutas de coordinador
  {
    path: '/coordinador',
    redirect: '/coordinador/dashboard',
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },
  {
    path: '/coordinador/dashboard',
    name: 'CoordinadorDashboard',
    component: CoordinadorDashboard,
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },
  {
    path: '/coordinador/citas',
    name: 'CoordinadorCitas',
    component: CoordinadorCitas,
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },
  {
    path: '/coordinador/doctores',
    name: 'CoordinadorDoctores',
    component: Doctores,
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },
  {
    path: '/coordinador/horarios',
    name: 'CoordinadorHorarios',
    component: GestorHorarios,
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },
  {
    path: '/coordinador/especialidades',
    name: 'CoordinadorEspecialidades',
    component: EspecialidadesCrud,
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },
  {
    path: '/coordinador/tipos-bloque',
    name: 'CoordinadorTiposBloque',
    component: TiposBloqueHorario,
    meta: { requiresAuth: true, roles: ['coordinador'] }
  },

  // Rutas de doctores
  {
    path: '/doctor',
    redirect: '/doctor/dashboard',
    meta: { requiresAuth: true, roles: ['doctor'] }
  },
  {
    path: '/doctor/dashboard',
    name: 'DoctorDashboard',
    component: DoctorDashboard,
    meta: { requiresAuth: true, roles: ['doctor'] }
  },
  {
    path: '/doctor/horarios',
    name: 'DoctorHorarios',
    component: GestorHorarios,
    meta: { requiresAuth: true, roles: ['doctor'] }
  },
  {
    path: '/doctor/aceptar-citas',
    name: 'DoctorAceptarCitas',
    component: AceptarCitas,
    meta: { requiresAuth: true, roles: ['doctor'] }
  },
  {
    path: '/doctor/disponibles',
    name: 'DoctorDisponibles',
    component: CitasDisponibles,
    meta: { requiresAuth: true, roles: ['doctor'] }
  },
  {
    path: '/doctor/historial',
    name: 'DoctorHistorial',
    component: HistorialDoctor,
    meta: { requiresAuth: true, roles: ['doctor'] }
  },

  // Rutas de aseguradoras
  {
    path: '/aseguradora',
    redirect: '/aseguradora/dashboard',
    meta: { requiresAuth: true, roles: ['aseguradora'] }
  },
  {
    path: '/aseguradora/dashboard',
    name: 'AseguradoraDashboard',
    component: AseguradoraDashboard,
    meta: { requiresAuth: true, roles: ['aseguradora'] }
  },
  {
    path: '/aseguradora/solicitar-cita',
    name: 'AseguradoraSolicitarCita',
    component: SolicitarCita,
    meta: { requiresAuth: true, roles: ['aseguradora'] }
  },
  {
    path: '/aseguradora/titulares',
    name: 'AseguradoraTitulares',
    component: Titulares,
    meta: { requiresAuth: true, roles: ['aseguradora'] }
  },
  {
    path: '/aseguradora/pacientes',
    name: 'AseguradoraPacientes',
    component: PacientesAseguradora,
    meta: { requiresAuth: true, roles: ['aseguradora'] }
  },
  {
    path: '/aseguradora/historial',
    name: 'AseguradoaHistorial',
    component: HistorialAseguradora,
    meta: { requiresAuth: true, roles: ['aseguradora'] }
  },

  // Ruta 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../components/Shared/NotFound.vue'),
    meta: { requiresAuth: false }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Guard de navegación
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  // Verificar si la ruta requiere autenticación
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login');
    return;
  }
  
  // Verificar roles si está especificado
  if (to.meta.roles && to.meta.roles.length > 0) {
    if (!authStore.isAuthenticated) {
      next('/login');
      return;
    }
    
    if (!to.meta.roles.includes(authStore.userRole)) {
      // Redirigir al dashboard apropiado según el rol
      const userRole = authStore.userRole;
      switch (userRole) {
        case 'admin':
          next('/admin/dashboard');
          break;
        case 'coordinador':
          next('/coordinador/dashboard');
          break;
        case 'doctor':
          next('/doctor/dashboard');
          break;
        case 'aseguradora':
          next('/aseguradora/dashboard');
          break;
        default:
          next('/dashboard');
          break;
      }
      return;
    }
  }
  
  // Si el usuario está autenticado y trata de acceder al login, redirigir al dashboard
  if (to.path === '/login' && authStore.isAuthenticated) {
    const userRole = authStore.userRole;
    switch (userRole) {
      case 'admin':
        next('/admin/dashboard');
        break;
      case 'coordinador':
        next('/coordinador/dashboard');
        break;
      case 'doctor':
        next('/doctor/dashboard');
        break;
      case 'aseguradora':
        next('/aseguradora/dashboard');
        break;
      default:
        next('/dashboard');
        break;
    }
    return;
  }
  
  next();
});

export default router;