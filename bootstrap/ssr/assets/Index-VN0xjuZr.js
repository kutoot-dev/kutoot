import { jsxs, jsx } from "react/jsx-runtime";
import { A as AuthenticatedLayout } from "./AuthenticatedLayout-JzOoDiv3.js";
import { usePage, Head, Link, router } from "@inertiajs/react";
import { C as CurrencySymbol } from "./CurrencySymbol-BQz5ZRGv.js";
import { useState, useEffect } from "react";
import "./ApplicationLogo-B2tGdv66.js";
import "@headlessui/react";
function Index({ auth, plans, currentSubscription, primaryCampaignId, availableCampaigns, isLoggedIn }) {
  const currentPlanIndex = plans.findIndex((p) => p.id === currentSubscription?.plan_id);
  const { flash } = usePage().props;
  const [showCampaignModal, setShowCampaignModal] = useState(false);
  const [selectedCampaign, setSelectedCampaign] = useState(null);
  useEffect(() => {
    if (flash?.needsCampaignSelection) {
      setShowCampaignModal(true);
    }
  }, [flash?.needsCampaignSelection]);
  const handleUpgrade = (planId) => {
    if (confirm("Are you sure you want to upgrade to this plan?")) {
      router.post(route("subscriptions.upgrade"), { plan_id: planId });
    }
  };
  const handleCampaignSelect = () => {
    if (!selectedCampaign) return;
    router.post(route("subscriptions.setPrimaryCampaign"), { campaign_id: selectedCampaign }, {
      onSuccess: () => setShowCampaignModal(false)
    });
  };
  const tierColors = [
    { card: "border-lucky-300", bg: "from-lucky-50 to-lucky-100", accent: "text-lucky-600", badge: "bg-lucky-100 text-lucky-700 border-lucky-200", icon: "🎫" },
    { card: "border-ticket-300", bg: "from-ticket-50 to-ticket-100", accent: "text-ticket-600", badge: "bg-ticket-100 text-ticket-700 border-ticket-200", icon: "⭐" },
    { card: "border-yellow-400", bg: "from-yellow-50 to-amber-100", accent: "text-amber-600", badge: "bg-yellow-100 text-amber-700 border-yellow-300", icon: "👑" }
  ];
  return /* @__PURE__ */ jsxs(
    AuthenticatedLayout,
    {
      user: auth.user,
      header: /* @__PURE__ */ jsx("h2", { className: "text-xl font-bold leading-tight text-white flex items-center gap-2", children: "⭐ Subscription Plans" }),
      children: [
        /* @__PURE__ */ jsx(Head, { title: "Subscriptions" }),
        /* @__PURE__ */ jsx("div", { className: "py-8", children: /* @__PURE__ */ jsx("div", { className: "max-w-7xl mx-auto sm:px-6 lg:px-8", children: /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 md:grid-cols-3 gap-6", children: plans.map((plan, index) => {
          const colors = tierColors[index % tierColors.length];
          const isCurrent = currentSubscription?.plan_id === plan.id;
          return /* @__PURE__ */ jsxs("div", { className: `coupon-card overflow-visible transition-all duration-300 transform hover:-translate-y-2 hover:shadow-xl ${isCurrent ? colors.card : ""}`, children: [
            isCurrent && /* @__PURE__ */ jsx("div", { className: "absolute -top-3 left-6 z-10", children: /* @__PURE__ */ jsx("span", { className: "golden-badge px-4 py-1 rounded-full text-xs", children: "⭐ CURRENT PLAN" }) }),
            /* @__PURE__ */ jsxs("div", { className: `p-6 bg-gradient-to-br ${colors.bg} rounded-t-2xl`, children: [
              /* @__PURE__ */ jsx("div", { className: "text-center mb-4", children: /* @__PURE__ */ jsx("span", { className: "text-4xl", children: colors.icon }) }),
              /* @__PURE__ */ jsx("h3", { className: "font-display text-2xl text-gray-900 text-center mb-1", children: plan.name }),
              /* @__PURE__ */ jsx("p", { className: "text-center mb-4", children: plan.price > 0 ? /* @__PURE__ */ jsxs("span", { className: `text-3xl font-bold ${colors.accent}`, children: [
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                plan.price.toFixed(2)
              ] }) : /* @__PURE__ */ jsx("span", { className: "text-lg font-medium text-gray-400", children: "Free" }) }),
              /* @__PURE__ */ jsxs("div", { className: "flex gap-3 mb-4", children: [
                /* @__PURE__ */ jsxs("div", { className: "flex-1 bg-white/80 backdrop-blur-sm rounded-xl p-3 text-center border border-dashed border-lucky-200", children: [
                  /* @__PURE__ */ jsx("p", { className: `text-2xl font-bold ${colors.accent}`, children: plan.stamps_on_purchase }),
                  /* @__PURE__ */ jsx("p", { className: "text-xs text-gray-500 font-medium", children: "🎫 Stamps / Buy" })
                ] }),
                /* @__PURE__ */ jsxs("div", { className: "flex-1 bg-white/80 backdrop-blur-sm rounded-xl p-3 text-center border border-dashed border-lucky-200", children: [
                  /* @__PURE__ */ jsx("p", { className: `text-2xl font-bold ${colors.accent}`, children: plan.stamps_per_100 }),
                  /* @__PURE__ */ jsxs("p", { className: "text-xs text-gray-500 font-medium", children: [
                    "Stamps per ",
                    /* @__PURE__ */ jsx(CurrencySymbol, {}),
                    "100 Bill"
                  ] })
                ] })
              ] })
            ] }),
            /* @__PURE__ */ jsx("div", { className: "flex justify-center gap-2 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent", children: [...Array(10)].map((_, i) => /* @__PURE__ */ jsx("div", { className: "w-2 h-2 rounded-full bg-lucky-200" }, i)) }),
            /* @__PURE__ */ jsxs("div", { className: "p-6", children: [
              /* @__PURE__ */ jsxs("ul", { className: "text-sm text-gray-600 space-y-3 mb-6", children: [
                /* @__PURE__ */ jsxs("li", { className: "flex justify-between py-1.5 border-b border-dashed border-lucky-100", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "🎟️ Max Discounted Bills" }),
                  /* @__PURE__ */ jsx("span", { className: "font-bold text-gray-900", children: plan.max_discounted_bills })
                ] }),
                /* @__PURE__ */ jsxs("li", { className: "flex justify-between py-1.5 border-b border-dashed border-lucky-100", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "💰 Max Redeemable" }),
                  /* @__PURE__ */ jsxs("span", { className: "font-bold text-gray-900", children: [
                    /* @__PURE__ */ jsx(CurrencySymbol, {}),
                    parseFloat(plan.max_redeemable_amount).toFixed(2)
                  ] })
                ] }),
                /* @__PURE__ */ jsxs("li", { className: "flex justify-between py-1.5", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "⏳ Validity" }),
                  /* @__PURE__ */ jsx("span", { className: "font-bold text-gray-900", children: plan.duration_days ? `${plan.duration_days} days` : "∞" })
                ] })
              ] }),
              isCurrent ? /* @__PURE__ */ jsxs("div", { children: [
                /* @__PURE__ */ jsx("button", { disabled: true, className: "w-full golden-badge py-2.5 px-4 rounded-full cursor-not-allowed text-sm", children: "⭐ Current Plan" }),
                currentSubscription.expires_at && /* @__PURE__ */ jsxs("p", { className: "text-center text-xs text-gray-400 mt-2 bg-gray-50 rounded-full py-1", children: [
                  "⏳ Expires: ",
                  /* @__PURE__ */ jsx("span", { className: "font-bold", children: currentSubscription.expires_at })
                ] })
              ] }) : plan.is_default ? /* @__PURE__ */ jsx("p", { className: "w-full text-center text-xs text-gray-400 py-2.5 bg-gray-50 rounded-full", children: "Auto-assigned on registration" }) : !isLoggedIn ? /* @__PURE__ */ jsx(
                Link,
                {
                  href: route("login"),
                  className: "w-full block text-center lucky-gradient text-white font-bold py-2.5 px-4 rounded-full transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm",
                  children: "🔑 Login to Upgrade"
                }
              ) : plans.indexOf(plan) > currentPlanIndex ? /* @__PURE__ */ jsx(
                "button",
                {
                  onClick: () => handleUpgrade(plan.id),
                  className: "w-full lucky-gradient text-white font-bold py-2.5 px-4 rounded-full transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5 text-sm",
                  children: "🚀 Upgrade"
                }
              ) : /* @__PURE__ */ jsx("p", { className: "w-full text-center text-xs text-gray-400 py-2.5 bg-gray-50 rounded-full", children: "Lower tier" })
            ] })
          ] }, plan.id);
        }) }) }) }),
        showCampaignModal && availableCampaigns.length > 0 && /* @__PURE__ */ jsx("div", { className: "fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4", children: /* @__PURE__ */ jsxs("div", { className: "coupon-card w-full max-w-md p-6 animate-in fade-in", children: [
          /* @__PURE__ */ jsxs("h3", { className: "font-display text-xl text-gray-900 mb-2 flex items-center gap-2", children: [
            /* @__PURE__ */ jsx("span", { className: "text-2xl", children: "🎯" }),
            " Select Your Campaign"
          ] }),
          /* @__PURE__ */ jsx("p", { className: "text-sm text-gray-500 mb-5", children: "Choose the campaign you'd like to collect stamps for:" }),
          /* @__PURE__ */ jsx("div", { className: "space-y-2 mb-6 max-h-60 overflow-y-auto", children: availableCampaigns.map((campaign) => /* @__PURE__ */ jsxs(
            "label",
            {
              className: `flex items-center gap-3 p-3 rounded-xl border-2 border-dashed cursor-pointer transition-all ${selectedCampaign === campaign.id ? "border-lucky-400 bg-lucky-50" : "border-gray-200 hover:border-lucky-200 hover:bg-lucky-50/30"}`,
              children: [
                /* @__PURE__ */ jsx(
                  "input",
                  {
                    type: "radio",
                    name: "campaign",
                    value: campaign.id,
                    checked: selectedCampaign === campaign.id,
                    onChange: () => setSelectedCampaign(campaign.id),
                    className: "text-lucky-500 focus:ring-lucky-400"
                  }
                ),
                /* @__PURE__ */ jsx("span", { className: "font-medium text-gray-900", children: campaign.reward_name })
              ]
            },
            campaign.id
          )) }),
          /* @__PURE__ */ jsx(
            "button",
            {
              onClick: handleCampaignSelect,
              disabled: !selectedCampaign,
              className: `w-full font-bold py-2.5 px-4 rounded-full transition-all text-sm ${selectedCampaign ? "lucky-gradient text-white shadow-md hover:shadow-lg transform hover:-translate-y-0.5" : "bg-gray-200 text-gray-400 cursor-not-allowed"}`,
              children: "✅ Confirm Campaign"
            }
          )
        ] }) })
      ]
    }
  );
}
export {
  Index as default
};
