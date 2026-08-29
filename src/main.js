import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import { createApp } from 'vue';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/authStore';
import './style.css';

const app = createApp(App);

const pinia = createPinia();
pinia.use(piniaPluginPersistedstate);
app.use(pinia);

const authStore = useAuthStore();
authStore.restoreSession();

app.use(Toast);
app.use(router);

if (import.meta.env.MODE === 'development') {
  app.config.devtools = true;
}

app.mount('#app');
