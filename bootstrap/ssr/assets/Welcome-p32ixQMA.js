import { jsxs, Fragment, jsx } from "react/jsx-runtime";
import { Head, Link } from "@inertiajs/react";
import { A as ApplicationLogo } from "./ApplicationLogo-B2tGdv66.js";
function Welcome({ auth }) {
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx(Head, { title: "Welcome to Kutoot" }),
    /* @__PURE__ */ jsxs("div", { className: "min-h-screen bg-gradient-to-br from-lucky-50 via-white to-ticket-50 confetti-bg overflow-hidden", children: [
      /* @__PURE__ */ jsx("div", { className: "absolute top-10 left-10 w-16 h-16 bg-lucky-200 rounded-full opacity-30 animate-float" }),
      /* @__PURE__ */ jsx("div", { className: "absolute top-32 right-20 w-10 h-10 bg-ticket-200 rounded-full opacity-30 animate-float", style: { animationDelay: "0.5s" } }),
      /* @__PURE__ */ jsx("div", { className: "absolute bottom-20 left-1/4 w-12 h-12 bg-yellow-200 rounded-full opacity-30 animate-float", style: { animationDelay: "1s" } }),
      /* @__PURE__ */ jsx("div", { className: "absolute top-1/2 right-10 w-8 h-8 bg-green-200 rounded-full opacity-30 animate-float", style: { animationDelay: "1.5s" } }),
      /* @__PURE__ */ jsxs("nav", { className: "relative z-10 flex items-center justify-between px-6 py-4 max-w-7xl mx-auto", children: [
        /* @__PURE__ */ jsx(ApplicationLogo, {}),
        /* @__PURE__ */ jsx("div", { className: "flex items-center gap-3", children: auth.user ? /* @__PURE__ */ jsx(
          Link,
          {
            href: route("dashboard"),
            className: "rounded-full px-5 py-2.5 font-bold text-sm text-white lucky-gradient shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all",
            children: "Dashboard"
          }
        ) : /* @__PURE__ */ jsx(Fragment, { children: /* @__PURE__ */ jsx(
          Link,
          {
            href: route("login"),
            className: "rounded-full px-5 py-2.5 font-bold text-sm text-lucky-700 border-2 border-lucky-300 hover:bg-lucky-50 transition-colors",
            children: "Log in / Sign up"
          }
        ) }) })
      ] }),
      /* @__PURE__ */ jsxs("main", { className: "relative z-10 flex flex-col items-center justify-center px-6 pt-12 pb-24", children: [
        /* @__PURE__ */ jsxs("div", { className: "relative mb-8", children: [
          /* @__PURE__ */ jsx("div", { className: "w-32 h-32 starburst opacity-20 animate-spin-slow" }),
          /* @__PURE__ */ jsx("div", { className: "absolute inset-0 flex items-center justify-center", children: /* @__PURE__ */ jsx("div", { className: "w-20 h-20 bg-white rounded-full shadow-xl flex items-center justify-center animate-pulse-glow", children: /* @__PURE__ */ jsx("span", { className: "text-3xl", children: "🎟️" }) }) })
        ] }),
        /* @__PURE__ */ jsx("h1", { className: "font-display text-5xl md:text-7xl text-center bg-gradient-to-r from-lucky-600 via-ticket-500 to-lucky-600 bg-clip-text text-transparent mb-4", children: "Win Big with Kutoot!" }),
        /* @__PURE__ */ jsx("p", { className: "text-lg md:text-xl text-gray-600 text-center max-w-2xl mb-10", children: "Collect stamps, scratch coupons, and unlock exclusive rewards. Every purchase is a chance to win!" }),
        /* @__PURE__ */ jsxs("div", { className: "flex flex-wrap justify-center gap-4 mb-16", children: [
          !auth.user && /* @__PURE__ */ jsxs(
            Link,
            {
              href: route("login"),
              className: "group relative inline-flex items-center gap-2 rounded-full px-8 py-4 font-bold text-lg text-white lucky-gradient shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all",
              children: [
                /* @__PURE__ */ jsx("span", { className: "text-2xl group-hover:animate-bounce", children: "🎰" }),
                "Start Winning",
                /* @__PURE__ */ jsx("span", { className: "absolute -top-2 -right-2 golden-badge text-xs px-2 py-0.5 rounded-full", children: "FREE" })
              ]
            }
          ),
          /* @__PURE__ */ jsxs(
            Link,
            {
              href: auth.user ? route("campaigns.index") : route("login"),
              className: "inline-flex items-center gap-2 rounded-full px-8 py-4 font-bold text-lg text-lucky-700 bg-white border-2 border-lucky-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all",
              children: [
                /* @__PURE__ */ jsx("span", { className: "text-2xl", children: "🏆" }),
                "View Campaigns"
              ]
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl w-full", children: [
          /* @__PURE__ */ jsx(
            FeatureTicket,
            {
              emoji: "🎫",
              title: "Collect Stamps",
              description: "Every purchase earns you stamps. Stack them up for bigger rewards!",
              color: "lucky"
            }
          ),
          /* @__PURE__ */ jsx(
            FeatureTicket,
            {
              emoji: "🎁",
              title: "Scratch & Win",
              description: "Unlock coupons and scratch to reveal discounts, cashback, and prizes!",
              color: "ticket"
            }
          ),
          /* @__PURE__ */ jsx(
            FeatureTicket,
            {
              emoji: "🏅",
              title: "Claim Rewards",
              description: "Redeem your stamps for exclusive rewards and premium perks.",
              color: "prize"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "mt-16 flex flex-wrap items-center justify-center gap-6 text-sm text-gray-500", children: [
          /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
            /* @__PURE__ */ jsx("span", { className: "text-green-500 text-lg", children: "✓" }),
            "Trusted by merchants"
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
            /* @__PURE__ */ jsx("span", { className: "text-green-500 text-lg", children: "✓" }),
            "Secure payments"
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-2", children: [
            /* @__PURE__ */ jsx("span", { className: "text-green-500 text-lg", children: "✓" }),
            "Instant rewards"
          ] })
        ] })
      ] }),
      /* @__PURE__ */ jsx("footer", { className: "relative z-10 text-center py-8 text-sm text-gray-400 border-t border-lucky-100", children: /* @__PURE__ */ jsxs("p", { children: [
        "© ",
        (/* @__PURE__ */ new Date()).getFullYear(),
        " Kutoot. Scratch, Win, Repeat! 🎉"
      ] }) })
    ] })
  ] });
}
function FeatureTicket({ emoji, title, description, color }) {
  const borderColors = {
    lucky: "border-lucky-300 hover:border-lucky-400",
    ticket: "border-ticket-300 hover:border-ticket-400",
    prize: "border-prize-300 hover:border-prize-400"
  };
  const bgColors = {
    lucky: "from-lucky-50 to-lucky-100/50",
    ticket: "from-ticket-50 to-ticket-100/50",
    prize: "from-prize-50 to-prize-100/50"
  };
  const textColors = {
    lucky: "text-lucky-700",
    ticket: "text-ticket-700",
    prize: "text-prize-700"
  };
  return /* @__PURE__ */ jsxs("div", { className: `coupon-card group hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2 ${borderColors[color]}`, children: [
    /* @__PURE__ */ jsxs("div", { className: `bg-gradient-to-b ${bgColors[color]} p-8 text-center`, children: [
      /* @__PURE__ */ jsx("div", { className: "text-5xl mb-4 group-hover:animate-bounce", children: emoji }),
      /* @__PURE__ */ jsx("h3", { className: `font-display text-xl mb-2 ${textColors[color]}`, children: title }),
      /* @__PURE__ */ jsx("p", { className: "text-gray-600 text-sm", children: description })
    ] }),
    /* @__PURE__ */ jsx("div", { className: "flex justify-center gap-2 py-2 bg-gradient-to-r from-transparent via-gray-100 to-transparent", children: [...Array(8)].map((_, i) => /* @__PURE__ */ jsx("div", { className: "w-2 h-2 rounded-full bg-gray-200" }, i)) })
  ] });
}
export {
  Welcome as default
};
