import { jsxs, jsx } from "react/jsx-runtime";
import { A as AuthenticatedLayout } from "./AuthenticatedLayout-JzOoDiv3.js";
import { Head, Link, router } from "@inertiajs/react";
import { C as CurrencySymbol } from "./CurrencySymbol-BQz5ZRGv.js";
import "./ApplicationLogo-B2tGdv66.js";
import "@headlessui/react";
import "react";
function Show({ auth, campaign, bountyMeter, collectedCommission, issuedStamps }) {
  const progressPercentage = Math.min(Math.round(bountyMeter * 100), 100);
  const isLoggedIn = !!auth.user;
  return /* @__PURE__ */ jsxs(
    AuthenticatedLayout,
    {
      user: auth.user,
      header: /* @__PURE__ */ jsx("h2", { className: "text-xl font-bold leading-tight text-white flex items-center gap-2", children: "🏆 Campaign Details" }),
      children: [
        /* @__PURE__ */ jsx(Head, { title: campaign.reward_name }),
        /* @__PURE__ */ jsx("div", { className: "py-8", children: /* @__PURE__ */ jsx("div", { className: "max-w-7xl mx-auto sm:px-6 lg:px-8", children: /* @__PURE__ */ jsx("div", { className: "coupon-card overflow-hidden", children: /* @__PURE__ */ jsxs("div", { className: "md:flex", children: [
          /* @__PURE__ */ jsx("div", { className: "md:flex-shrink-0", children: /* @__PURE__ */ jsx(
            "img",
            {
              className: "h-48 w-full object-cover md:w-48",
              src: campaign.creator?.merchant?.logo || `https://placehold.co/400x400?text=${encodeURIComponent(campaign.reward_name)}`,
              alt: campaign.reward_name
            }
          ) }),
          /* @__PURE__ */ jsxs("div", { className: "p-8 w-full", children: [
            /* @__PURE__ */ jsx("div", { className: "uppercase tracking-wide text-sm text-lucky-600 font-bold", children: campaign.category?.name }),
            /* @__PURE__ */ jsx("h1", { className: "block mt-1 text-lg leading-tight font-display text-gray-900", children: campaign.reward_name }),
            /* @__PURE__ */ jsx("p", { className: "mt-2 text-gray-500", children: campaign.description || "Complete the requirements to unlock this reward!" }),
            /* @__PURE__ */ jsxs("div", { className: "mt-6", children: [
              /* @__PURE__ */ jsxs("h3", { className: "text-lg font-display text-gray-900 flex items-center gap-2", children: [
                /* @__PURE__ */ jsx("span", { children: "📊" }),
                " Progress"
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "relative pt-1", children: [
                /* @__PURE__ */ jsxs("div", { className: "flex mb-2 items-center justify-between", children: [
                  /* @__PURE__ */ jsx("div", { children: /* @__PURE__ */ jsx("span", { className: "text-xs font-bold inline-block py-1 px-3 uppercase rounded-full text-lucky-700 bg-lucky-100 border border-lucky-200", children: "Bounty Meter" }) }),
                  /* @__PURE__ */ jsx("div", { className: "text-right", children: /* @__PURE__ */ jsxs("span", { className: "text-xs font-bold inline-block text-lucky-600", children: [
                    progressPercentage,
                    "%"
                  ] }) })
                ] }),
                /* @__PURE__ */ jsx("div", { className: "overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-lucky-100 border border-lucky-200", children: /* @__PURE__ */ jsx(
                  "div",
                  {
                    style: { width: `${progressPercentage}%` },
                    className: "shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center lucky-gradient transition-all duration-500 ease-out rounded-full"
                  }
                ) })
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-2 gap-4 mt-4 text-sm", children: [
                /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-lucky-50 to-lucky-100 p-4 rounded-xl border border-dashed border-lucky-200", children: [
                  /* @__PURE__ */ jsx("span", { className: "block font-display text-lucky-700", children: "💰 Review Spend" }),
                  /* @__PURE__ */ jsxs("span", { className: "block text-gray-700", children: [
                    "Collected: ",
                    /* @__PURE__ */ jsxs("span", { className: "font-bold text-lucky-600", children: [
                      /* @__PURE__ */ jsx(CurrencySymbol, {}),
                      parseFloat(collectedCommission).toFixed(2)
                    ] })
                  ] }),
                  /* @__PURE__ */ jsxs("span", { className: "block text-xs text-gray-400", children: [
                    "Target: ",
                    /* @__PURE__ */ jsx(CurrencySymbol, {}),
                    parseFloat(campaign.reward_cost_target).toFixed(2)
                  ] })
                ] }),
                /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-ticket-50 to-ticket-100 p-4 rounded-xl border border-dashed border-ticket-200", children: [
                  /* @__PURE__ */ jsx("span", { className: "block font-display text-ticket-700", children: "🎫 Stamps" }),
                  /* @__PURE__ */ jsxs("span", { className: "block text-gray-700", children: [
                    "Collected: ",
                    /* @__PURE__ */ jsx("span", { className: "font-bold text-ticket-600", children: issuedStamps })
                  ] }),
                  /* @__PURE__ */ jsxs("span", { className: "block text-xs text-gray-400", children: [
                    "Target: ",
                    campaign.stamp_target
                  ] })
                ] })
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "mt-6 flex flex-wrap gap-3", children: [
              /* @__PURE__ */ jsxs(
                "button",
                {
                  disabled: progressPercentage < 100,
                  className: `inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold shadow-md transition-all ${progressPercentage >= 100 ? "lucky-gradient text-white hover:shadow-lg transform hover:-translate-y-0.5 animate-pulse-glow" : "bg-gray-200 text-gray-500 cursor-not-allowed"}`,
                  children: [
                    /* @__PURE__ */ jsx("span", { children: progressPercentage >= 100 ? "🎁" : "⏳" }),
                    progressPercentage >= 100 ? "Claim Reward" : "In Progress"
                  ]
                }
              ),
              !isLoggedIn ? /* @__PURE__ */ jsx(
                Link,
                {
                  href: route("login"),
                  className: "inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold border-2 border-lucky-300 text-lucky-700 bg-white hover:bg-lucky-50 transition-colors",
                  children: "🔑 Login to Set Primary"
                }
              ) : auth.user.primary_campaign_id !== campaign.id ? /* @__PURE__ */ jsx(
                "button",
                {
                  onClick: () => {
                    if (confirm("Set this as your primary campaign for future stamps?")) {
                      router.patch(route("profile.update-primary-campaign"), {
                        primary_campaign_id: campaign.id
                      });
                    }
                  },
                  className: "inline-flex items-center gap-2 rounded-full px-6 py-2.5 text-sm font-bold border-2 border-lucky-300 text-lucky-700 bg-white hover:bg-lucky-50 transition-colors",
                  children: "🎯 Set as Primary"
                }
              ) : /* @__PURE__ */ jsx("span", { className: "inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold golden-badge", children: "⭐ Primary Campaign" })
            ] })
          ] })
        ] }) }) }) })
      ]
    }
  );
}
export {
  Show as default
};
