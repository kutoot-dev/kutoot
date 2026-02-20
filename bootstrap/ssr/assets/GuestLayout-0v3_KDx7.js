import { jsxs, jsx } from "react/jsx-runtime";
import { A as ApplicationLogo } from "./ApplicationLogo-B2tGdv66.js";
import { Link } from "@inertiajs/react";
function GuestLayout({ children }) {
  return /* @__PURE__ */ jsxs("div", { className: "flex min-h-screen flex-col items-center bg-gradient-to-br from-lucky-50 via-white to-ticket-50 confetti-bg pt-6 sm:justify-center sm:pt-0", children: [
    /* @__PURE__ */ jsx("div", { className: "absolute top-10 right-20 w-12 h-12 bg-lucky-200 rounded-full opacity-20 animate-float" }),
    /* @__PURE__ */ jsx("div", { className: "absolute bottom-20 left-20 w-8 h-8 bg-ticket-200 rounded-full opacity-20 animate-float", style: { animationDelay: "1s" } }),
    /* @__PURE__ */ jsx("div", { className: "relative z-10", children: /* @__PURE__ */ jsx(Link, { href: "/", children: /* @__PURE__ */ jsx(ApplicationLogo, {}) }) }),
    /* @__PURE__ */ jsxs("div", { className: "relative z-10 mt-4 flex items-center gap-4 text-sm", children: [
      /* @__PURE__ */ jsx(
        Link,
        {
          href: route("campaigns.index"),
          className: "text-lucky-700 hover:text-lucky-900 font-medium transition-colors",
          children: "Home"
        }
      ),
      /* @__PURE__ */ jsx("span", { className: "text-lucky-300", children: "|" }),
      /* @__PURE__ */ jsx(
        Link,
        {
          href: route("login"),
          className: "text-lucky-700 hover:text-lucky-900 font-medium transition-colors",
          children: "Login / Sign up"
        }
      )
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "relative z-10 mt-6 w-full overflow-hidden bg-lucky-50/95 backdrop-blur-sm px-8 py-6 shadow-xl border-2 border-dashed border-lucky-200 sm:max-w-md sm:rounded-2xl", children: [
      /* @__PURE__ */ jsx("div", { className: "absolute -left-3 top-1/2 w-6 h-6 bg-gradient-to-br from-lucky-50 to-ticket-50 rounded-full" }),
      /* @__PURE__ */ jsx("div", { className: "absolute -right-3 top-1/2 w-6 h-6 bg-gradient-to-br from-lucky-50 to-ticket-50 rounded-full" }),
      children
    ] }),
    /* @__PURE__ */ jsx("p", { className: "mt-6 text-sm text-gray-400 relative z-10", children: "🎟️ Your luck starts here!" })
  ] });
}
export {
  GuestLayout as G
};
