import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@store/auth';

// Vistas - ajustar las rutas según tu estructura de carpetas
import Login from '@components/Auth/Login.vue';
import AdminDashboard from '@components/Admin/Dashboard.vue';
import AsignarCitas from '@components/Admin/AsignarCitas.vue';
import DoctorDashboard from '@components/Doctor/Dashboard.vue';
import AceptarCitas from '@components/Doctor/AceptarCitas.vue';
import AseguradoraDashboard from '@components/Aseguradora/Dashboard.vue';
import SolicitarCita from '@components/Aseguradora/SolicitarCita.vue';
import GestionTitulares from '@components/Aseguradora/GestionTitulares.vue';
import GestionPacientes from '@components/Aseguradora/GestionPacientes.vue';
import PacienteDashboard from '@components/Paciente/Dashboard.vue';
import PacienteSolicitarCita from '@components/Paciente/SolicitarCita.vue';
import Doctores from '@components/Admin/Doctores.vue';
import Aseguradoras from '@components/Admin/Aseguradoras.vue';
import Pacientes from '@components/Admin/Pacientes.vue';
import Especialidades from '@components/Admin/Especialidades.vue';
import Perfil from '@components/Shared/Perfil.vue';

const routes = [
  // Rutas Temporales para Vertice

// Rutas Temporales para Vertice
{
  path: '/vertice',
  component: () => import('@components/Vertice/Layout.vue'),
  meta: { requiresAuth: true, role: 'vertice' },
  children: [
    {
      path: 'solicitudes',
      name: 'vertice-solicitudes',
      component: () => import('@components/Vertice/SolicitudesTable.vue'),
      meta: { title: 'Solicitudes' }
    },
    {
      path: 'asignacion/:solicitudId',
      name: 'vertice-asignacion',
      component: () => import('@components/Vertice/AsignacionForm.vue'),
      meta: { title: 'Asignación de Cita' },
      props: true
    },
    {
      path: 'doctores',
      name: 'vertice-doctores',
      component: () => import('@components/Vertice/DoctoresTable.vue'),
      meta: { title: 'Doctores' }
    },
    {
      path: 'horarios',
      name: 'vertice-horarios',
      component: () => import('@components/Vertice/HorariosForm.vue'),
      meta: { title: 'Horarios' }
    }
  ]
},

  // Fin Rutas Temporales Vertica
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/perfil',
    name: 'Perfil',
    component: Perfil,
    meta: { requiresAuth: true }
  },
  // Rutas para admin
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/citas',
    name: 'AsignarCitas',
    component: AsignarCitas,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/doctores',
    name: 'Doctores',
    component: Doctores,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/aseguradoras',
    name: 'Aseguradoras',
    component: Aseguradoras,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/pacientes',
    name: 'Pacientes',
    component: Pacientes,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/especialidades',
    name: 'Especialidades',
    component: Especialidades,
    meta: {
      requiresAuth: true, role: 'admin' }
  },
  // Rutas para doctor
  {
    path: '/doctor/dashboard',
    name: 'DoctorDashboard',
    component: DoctorDashboard,
    meta: { requiresAuth: true, role: 'doctor' }
  },
  {
    path: '/doctor/aceptar-citas',
    name: 'AceptarCitas',
    component: AceptarCitas,
    meta: { requiresAuth: true, role: 'doctor' }
  },
  // Rutas para aseguradora
  {
    path: '/aseguradora/dashboard',
    name: 'AseguradoraDashboard',
    component: AseguradoraDashboard,
    meta: { requiresAuth: true, role: 'aseguradora' }
  },
  {
    path: '/aseguradora/solicitar-cita',
    name: 'SolicitarCita',
    component: SolicitarCita,
    meta: { requiresAuth: true, role: 'aseguradora' }
  },
  {
    path: '/aseguradora/titulares',
    name: 'GestionTitulares',
    component: GestionTitulares,
    meta: { requiresAuth: true, role: 'aseguradora' }
  },
  {
    path: '/aseguradora/pacientes',
    name: 'GestionPacientes',
    component: GestionPacientes,
    meta: { requiresAuth: true, role: 'aseguradora' }
  },
  // Rutas para paciente
  {
    path: '/paciente/dashboard',
    name: 'PacienteDashboard',
    component: PacienteDashboard,
    meta: { requiresAuth: true, role: 'paciente' }
  },
  {
    path: '/paciente/solicitar-cita',
    name: 'PacienteSolicitarCita',
    component: PacienteSolicitarCita,
    meta: { requiresAuth: true, role: 'paciente' }
  },
  // Ruta para error 404
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
];

const router = createRouter({
  history: createWebHistory('/'),
  routes
});

// Guard de navegación mejorado
router.beforeEach(async (to, from, next) => {
  // Intentar obtener el store de autenticación
  const authStore = useAuthStore();
  
  // Verificar si la ruta requiere autenticación
  if (to.meta.requiresAuth) {
    // Verificar si hay token y usuario en el store
    if (authStore.token) {
      // Si no hay usuario en el store pero hay token, validar el token
      if (!authStore.user) {
        try {
          // Intentar validar el token
          const isValid = await authStore.validateToken();
          
          if (!isValid) {
            // Si el token no es válido, redirigir al login
            return next('/login');
          }
        } catch (error) {
          console.error('Error validando token:', error);
          return next('/login');
        }
      }
      
      // Verificar si la ruta requiere un rol específico
      if (to.meta.role && authStore.userRole !== to.meta.role) {
        // Redirigir según el rol del usuario si está intentando acceder a una ruta no permitida
        return authStore.redirectBasedOnRole();
      }
      
      // Permitir acceso a la ruta
      return next();
    } else {
      // No hay token, redirigir al login
      return next('/login');
    }
  }
  
  // Si intenta acceder a login estando autenticado, redirigir según rol
  if (to.path === '/login' && authStore.isAuthenticated) {
    return authStore.redirectBasedOnRole();
  }
  
  // Si la ruta no requiere autenticación, permitir acceso
  return next();
});

export default router;