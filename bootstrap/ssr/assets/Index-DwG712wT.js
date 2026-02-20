import { jsxs, jsx } from "react/jsx-runtime";
import { A as AuthenticatedLayout } from "./AuthenticatedLayout-JzOoDiv3.js";
import { Head, Link } from "@inertiajs/react";
import "./ApplicationLogo-B2tGdv66.js";
import "@headlessui/react";
import "react";
function BountyMeter({ percentage = 0, size = "md" }) {
  const clamped = Math.min(Math.max(percentage, 0), 100);
  const sizeClasses = {
    sm: { bar: "h-2", text: "text-xs", wrapper: "" },
    md: { bar: "h-4", text: "text-xs", wrapper: "" },
    lg: { bar: "h-5", text: "text-sm", wrapper: "" }
  };
  const s = sizeClasses[size] || sizeClasses.md;
  const meterColor = clamped >= 80 ? "from-green-400 to-emerald-500" : clamped >= 50 ? "from-lucky-400 to-lucky-600" : clamped >= 25 ? "from-yellow-400 to-amber-500" : "from-orange-400 to-red-400";
  return /* @__PURE__ */ jsxs("div", { className: s.wrapper, children: [
    /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between mb-1", children: [
      /* @__PURE__ */ jsx("span", { className: `font-bold text-lucky-700 ${s.text}`, children: "🔥 Bounty" }),
      /* @__PURE__ */ jsxs("span", { className: `font-bold text-lucky-600 ${s.text}`, children: [
        clamped,
        "%"
      ] })
    ] }),
    /* @__PURE__ */ jsx("div", { className: `overflow-hidden ${s.bar} rounded-full bg-lucky-100 border border-lucky-200`, children: /* @__PURE__ */ jsx(
      "div",
      {
        style: { width: `${clamped}%` },
        className: `h-full rounded-full bg-gradient-to-r ${meterColor} transition-all duration-700 ease-out`
      }
    ) })
  ] });
}
function Index({ auth, campaigns }) {
  return /* @__PURE__ */ jsxs(
    AuthenticatedLayout,
    {
      user: auth.user,
      header: /* @__PURE__ */ jsx("h2", { className: "text-xl font-bold leading-tight text-white flex items-center gap-2", children: "🏆 Campaigns" }),
      children: [
        /* @__PURE__ */ jsx(Head, { title: "Campaigns" }),
        /* @__PURE__ */ jsx("div", { className: "py-8", children: /* @__PURE__ */ jsx("div", { className: "max-w-7xl mx-auto sm:px-6 lg:px-8", children: /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6", children: campaigns.data.length > 0 ? campaigns.data.map((campaign) => /* @__PURE__ */ jsx(
          Link,
          {
            href: route("campaigns.show", campaign.id),
            className: "block group",
            children: /* @__PURE__ */ jsxs("div", { className: "coupon-card overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1", children: [
              /* @__PURE__ */ jsxs("div", { className: "h-48 bg-gradient-to-br from-lucky-100 to-ticket-100 w-full relative overflow-hidden", children: [
                /* @__PURE__ */ jsx(
                  "img",
                  {
                    src: campaign.creator?.merchant?.logo || `https://placehold.co/600x400?text=${encodeURIComponent(campaign.reward_name)}`,
                    alt: campaign.reward_name,
                    className: "w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                  }
                ),
                /* @__PURE__ */ jsx("div", { className: "absolute top-3 right-3 golden-badge px-3 py-1 rounded-full text-xs shadow-md", children: campaign.category?.name })
              ] }),
              /* @__PURE__ */ jsx("div", { className: "flex justify-center gap-2 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent", children: [...Array(10)].map((_, i) => /* @__PURE__ */ jsx("div", { className: "w-2 h-2 rounded-full bg-lucky-200" }, i)) }),
              /* @__PURE__ */ jsxs("div", { className: "p-5", children: [
                /* @__PURE__ */ jsx("div", { className: "flex items-center justify-between mb-2", children: /* @__PURE__ */ jsx("span", { className: "text-xs font-bold text-lucky-600 uppercase tracking-wider", children: campaign.creator?.merchant?.name || "Kutoot Exclusive" }) }),
                /* @__PURE__ */ jsx("h3", { className: "font-display text-lg text-gray-900 mb-2 truncate", children: campaign.reward_name }),
                /* @__PURE__ */ jsx("p", { className: "text-sm text-gray-500 mb-4 line-clamp-2", children: "Collect stamps to unlock this reward." }),
                /* @__PURE__ */ jsx("div", { className: "mb-4", children: /* @__PURE__ */ jsx(BountyMeter, { percentage: campaign.bounty_percentage, size: "sm" }) }),
                /* @__PURE__ */ jsxs("div", { className: "flex items-center justify-between", children: [
                  /* @__PURE__ */ jsxs("div", { className: "flex items-center text-sm text-lucky-600 font-bold", children: [
                    /* @__PURE__ */ jsx("span", { className: "text-lg mr-1", children: "🎫" }),
                    /* @__PURE__ */ jsxs("span", { children: [
                      "Target: ",
                      campaign.stamp_target,
                      " Stamps"
                    ] })
                  ] }),
                  /* @__PURE__ */ jsx("span", { className: "text-lucky-500 group-hover:translate-x-1 transition-transform", children: "→" })
                ] })
              ] })
            ] })
          },
          campaign.id
        )) : /* @__PURE__ */ jsxs("div", { className: "col-span-full text-center py-16", children: [
          /* @__PURE__ */ jsx("span", { className: "text-5xl mb-4 block", children: "🎭" }),
          /* @__PURE__ */ jsx("h3", { className: "font-display text-lg text-gray-900", children: "No campaigns available" }),
          /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-gray-500", children: "Check back later for new rewards." })
        ] }) }) }) })
      ]
    }
  );
}
export {
  Index as default
};
