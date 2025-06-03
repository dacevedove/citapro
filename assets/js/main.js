import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from '../../router';
import axios from 'axios';

// Configuración global de Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Obtener el token CSRF del meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

// Interceptor para manejo de errores
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Redirigir al login si hay un error 401 (no autorizado)
            localStorage.removeItem('token');
            router.push('/login');
        }
        return Promise.reject(error);
    }
);

// Crear la aplicación Vue
const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Montar la aplicación
app.mount('#app');