import { jsx } from "react/jsx-runtime";
import axios from "axios";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
async function resolvePageComponent(path, pages) {
  for (const p of Array.isArray(path) ? path : [path]) {
    const page = pages[p];
    if (typeof page === "undefined") {
      continue;
    }
    return typeof page === "function" ? page() : page;
  }
  throw new Error(`Page not found: ${path}`);
}
const appName = "Kutoot";
createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(
    `./Pages/${name}.jsx`,
    /* @__PURE__ */ Object.assign({ "./Pages/Auth/ConfirmPassword.jsx": () => import("./assets/ConfirmPassword-BfEErBKl.js"), "./Pages/Auth/ForgotPassword.jsx": () => import("./assets/ForgotPassword-DOlXFzC0.js"), "./Pages/Auth/Login.jsx": () => import("./assets/Login-DJ8TiTYt.js"), "./Pages/Auth/OtpLogin.jsx": () => import("./assets/OtpLogin-Dflvafet.js"), "./Pages/Auth/Register.jsx": () => import("./assets/Register-IxmvZNAH.js"), "./Pages/Auth/ResetPassword.jsx": () => import("./assets/ResetPassword-D9BKdvO2.js"), "./Pages/Auth/VerifyEmail.jsx": () => import("./assets/VerifyEmail-DlQ9II4F.js"), "./Pages/Campaigns/Index.jsx": () => import("./assets/Index-DwG712wT.js"), "./Pages/Campaigns/Show.jsx": () => import("./assets/Show-BBRaFOuf.js"), "./Pages/Coupons/Index.jsx": () => import("./assets/Index-CHZpdIS7.js"), "./Pages/Dashboard.jsx": () => import("./assets/Dashboard-vAGDc5mq.js"), "./Pages/Executive/LinkQr.jsx": () => import("./assets/LinkQr-CtYoyWyd.js"), "./Pages/Profile/Edit.jsx": () => import("./assets/Edit-BTOFzmAR.js"), "./Pages/Profile/Partials/DeleteUserForm.jsx": () => import("./assets/DeleteUserForm-DbrOds_3.js"), "./Pages/Profile/Partials/UpdatePasswordForm.jsx": () => import("./assets/UpdatePasswordForm-DCuo5InE.js"), "./Pages/Profile/Partials/UpdateProfileInformationForm.jsx": () => import("./assets/UpdateProfileInformationForm-DEn0FyXF.js"), "./Pages/Subscriptions/Index.jsx": () => import("./assets/Index-VN0xjuZr.js"), "./Pages/Welcome.jsx": () => import("./assets/Welcome-p32ixQMA.js") })
  ),
  setup({ el, App, props }) {
    const root = createRoot(el);
    root.render(/* @__PURE__ */ jsx(App, { ...props }));
  },
  progress: {
    color: "#4B5563"
  }
});
