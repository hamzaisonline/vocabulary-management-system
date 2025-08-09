import { createPinia } from "pinia";
import { createApp } from "vue";
import Toast from "vue-toastification";
import App from "./App.vue";
import router from "./router";
import "./style.css";
// Import the CSS or use your own!
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import "vue-toastification/dist/index.css";

const app = createApp(App);

if (import.meta.env.MODE === "development") {
  app.config.devtools = true; // Ensure Vue Devtools is enabled
}

const pinia = createPinia();

// Use the plugin
pinia.use(piniaPluginPersistedstate);

app.use(Toast);
app.use(router);
app.use(pinia);
app.mount("#app");
