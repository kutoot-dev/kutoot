import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { A as AuthenticatedLayout } from "./AuthenticatedLayout-JzOoDiv3.js";
import { Head } from "@inertiajs/react";
import { C as CurrencySymbol } from "./CurrencySymbol-BQz5ZRGv.js";
import "./ApplicationLogo-B2tGdv66.js";
import "@headlessui/react";
import "react";
function Dashboard({ auth, user, plan, primaryCampaign, stats, recentActivity, stamps, activityLogs }) {
  return /* @__PURE__ */ jsxs(
    AuthenticatedLayout,
    {
      user: auth.user,
      header: /* @__PURE__ */ jsx("h2", { className: "text-xl font-bold leading-tight text-white flex items-center gap-2", children: "🎯 Dashboard" }),
      children: [
        /* @__PURE__ */ jsx(Head, { title: "Dashboard" }),
        /* @__PURE__ */ jsx("div", { className: "py-8", children: /* @__PURE__ */ jsxs("div", { className: "mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6", children: [
          /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-6", children: [
            /* @__PURE__ */ jsxs("div", { className: "coupon-card p-6", children: [
              /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-3 mb-4", children: [
                /* @__PURE__ */ jsx("div", { className: "w-12 h-12 rounded-full bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-xl font-bold shadow-lg", children: user.name.charAt(0).toUpperCase() }),
                /* @__PURE__ */ jsxs("div", { children: [
                  /* @__PURE__ */ jsx("h3", { className: "font-display text-lg text-gray-900", children: "Welcome back!" }),
                  /* @__PURE__ */ jsx("p", { className: "text-sm text-gray-500", children: user.email })
                ] })
              ] }),
              /* @__PURE__ */ jsxs("dl", { className: "space-y-2 text-sm", children: [
                /* @__PURE__ */ jsxs("div", { className: "flex justify-between py-1 border-b border-dashed border-lucky-100", children: [
                  /* @__PURE__ */ jsx("dt", { className: "text-gray-500", children: "👤 Name" }),
                  /* @__PURE__ */ jsx("dd", { className: "font-bold text-gray-900", children: user.name })
                ] }),
                /* @__PURE__ */ jsxs("div", { className: "flex justify-between py-1 border-b border-dashed border-lucky-100", children: [
                  /* @__PURE__ */ jsx("dt", { className: "text-gray-500", children: "📧 Email" }),
                  /* @__PURE__ */ jsx("dd", { className: "font-bold text-gray-900", children: user.email })
                ] }),
                /* @__PURE__ */ jsxs("div", { className: "flex justify-between py-1 border-b border-dashed border-lucky-100", children: [
                  /* @__PURE__ */ jsx("dt", { className: "text-gray-500", children: "📅 Member Since" }),
                  /* @__PURE__ */ jsx("dd", { className: "font-bold text-gray-900", children: user.created_at })
                ] }),
                primaryCampaign && /* @__PURE__ */ jsxs("div", { className: "flex justify-between py-1", children: [
                  /* @__PURE__ */ jsx("dt", { className: "text-gray-500", children: "🏆 Campaign" }),
                  /* @__PURE__ */ jsx("dd", { className: "font-bold text-lucky-600", children: primaryCampaign })
                ] })
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "coupon-card overflow-visible", children: [
              plan && !plan.is_default && /* @__PURE__ */ jsx("div", { className: "absolute -top-3 left-6 z-10", children: /* @__PURE__ */ jsx("span", { className: "golden-badge px-4 py-1 rounded-full text-xs", children: "⭐ ACTIVE PLAN" }) }),
              /* @__PURE__ */ jsxs("div", { className: "p-6", children: [
                /* @__PURE__ */ jsxs("h3", { className: "font-display text-lg text-gray-900 mb-4 flex items-center gap-2", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-2xl", children: "🎫" }),
                  " Plan Details"
                ] }),
                plan ? /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsxs("p", { className: "text-2xl font-display text-lucky-600 mb-3", children: [
                    plan.name,
                    plan.is_default && /* @__PURE__ */ jsx("span", { className: "ml-2 text-xs font-normal text-gray-400 font-sans", children: "(Base)" })
                  ] }),
                  /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-3 gap-3 text-sm", children: [
                    /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-lucky-50 to-lucky-100 rounded-xl p-3 text-center border border-lucky-200", children: [
                      /* @__PURE__ */ jsx("p", { className: "text-2xl font-bold text-lucky-600", children: plan.stamps_on_purchase }),
                      /* @__PURE__ */ jsx("p", { className: "text-xs text-lucky-700 font-medium", children: "Bonus Stamps" })
                    ] }),
                    /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-lucky-50 to-lucky-100 rounded-xl p-3 text-center border border-lucky-200", children: [
                      /* @__PURE__ */ jsx("p", { className: "text-2xl font-bold text-lucky-600", children: plan.stamps_per_100 }),
                      /* @__PURE__ */ jsxs("p", { className: "text-xs text-lucky-700 font-medium", children: [
                        "Stamps per ",
                        /* @__PURE__ */ jsx(CurrencySymbol, {}),
                        "100 Bill"
                      ] })
                    ] }),
                    /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-ticket-50 to-ticket-100 rounded-xl p-3 text-center border border-ticket-200", children: [
                      /* @__PURE__ */ jsx("p", { className: "text-2xl font-bold text-ticket-600", children: plan.max_discounted_bills }),
                      /* @__PURE__ */ jsx("p", { className: "text-xs text-ticket-700 font-medium", children: "Max Bills" })
                    ] })
                  ] }),
                  /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-2 gap-3 text-sm mt-3", children: [
                    /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-ticket-50 to-ticket-100 rounded-xl p-3 text-center border border-ticket-200", children: [
                      /* @__PURE__ */ jsxs("p", { className: "text-2xl font-bold text-ticket-600", children: [
                        /* @__PURE__ */ jsx(CurrencySymbol, {}),
                        plan.max_redeemable_amount.toFixed(2)
                      ] }),
                      /* @__PURE__ */ jsx("p", { className: "text-xs text-ticket-700 font-medium", children: "Max Redeem" })
                    ] }),
                    plan.duration_days && /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-prize-50 to-prize-100 rounded-xl p-3 text-center border border-prize-200", children: [
                      /* @__PURE__ */ jsx("p", { className: "text-2xl font-bold text-prize-600", children: plan.duration_days }),
                      /* @__PURE__ */ jsx("p", { className: "text-xs text-prize-700 font-medium", children: "Validity (Days)" })
                    ] })
                  ] }),
                  (plan.purchased_at || plan.expires_at) && /* @__PURE__ */ jsxs("div", { className: "mt-3 space-y-1 text-xs text-gray-500 bg-gray-50 rounded-xl p-3", children: [
                    plan.purchased_at && /* @__PURE__ */ jsxs("div", { className: "flex justify-between", children: [
                      /* @__PURE__ */ jsx("span", { children: "Purchased" }),
                      /* @__PURE__ */ jsx("span", { className: "font-bold text-gray-700", children: plan.purchased_at })
                    ] }),
                    plan.expires_at && /* @__PURE__ */ jsxs("div", { className: "flex justify-between", children: [
                      /* @__PURE__ */ jsx("span", { children: "Expires" }),
                      /* @__PURE__ */ jsx("span", { className: "font-bold text-gray-700", children: plan.expires_at })
                    ] }),
                    plan.days_remaining !== null && plan.days_remaining >= 0 && /* @__PURE__ */ jsxs("div", { className: "flex justify-between", children: [
                      /* @__PURE__ */ jsx("span", { children: "Remaining" }),
                      /* @__PURE__ */ jsxs("span", { className: `font-bold ${plan.days_remaining <= 7 ? "text-red-600" : "text-green-600"}`, children: [
                        plan.days_remaining,
                        " days"
                      ] })
                    ] })
                  ] })
                ] }) : /* @__PURE__ */ jsxs("div", { className: "text-center py-4", children: [
                  /* @__PURE__ */ jsx("span", { className: "text-4xl", children: "🎭" }),
                  /* @__PURE__ */ jsx("p", { className: "text-gray-400 mt-2", children: "No active plan" })
                ] })
              ] })
            ] })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "grid grid-cols-2 md:grid-cols-5 gap-4", children: [
            /* @__PURE__ */ jsx(StatCard, { label: "Total Stamps", value: stats.stamps_count, emoji: "🎫", color: "lucky" }),
            /* @__PURE__ */ jsx(StatCard, { label: "Coupons Used", value: stats.total_coupons_used, emoji: "🎟️", color: "green" }),
            /* @__PURE__ */ jsx(StatCard, { label: "Discount Redeemed", value: /* @__PURE__ */ jsxs(Fragment, { children: [
              /* @__PURE__ */ jsx(CurrencySymbol, {}),
              stats.total_discount_redeemed.toFixed(2)
            ] }), emoji: "💰", color: "emerald" }),
            /* @__PURE__ */ jsx(StatCard, { label: "Bills Remaining", value: stats.remaining_bills, emoji: "📋", color: "amber" }),
            /* @__PURE__ */ jsx(StatCard, { label: "Redeem Amount Left", value: /* @__PURE__ */ jsxs(Fragment, { children: [
              /* @__PURE__ */ jsx(CurrencySymbol, {}),
              stats.remaining_redeem_amount.toFixed(2)
            ] }), emoji: "🎁", color: "rose" })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "coupon-card p-6", children: [
            /* @__PURE__ */ jsxs("h3", { className: "font-display text-lg text-gray-900 mb-4 flex items-center gap-2", children: [
              /* @__PURE__ */ jsx("span", { className: "text-xl", children: "📜" }),
              " Recent Activity"
            ] }),
            recentActivity.length > 0 ? /* @__PURE__ */ jsx("div", { className: "overflow-x-auto", children: /* @__PURE__ */ jsxs("table", { className: "min-w-full text-sm", children: [
              /* @__PURE__ */ jsx("thead", { children: /* @__PURE__ */ jsxs("tr", { className: "border-b-2 border-dashed border-lucky-200 text-left text-lucky-600", children: [
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold", children: "Coupon" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold", children: "Location" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "Bill" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "Discount" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "Fee" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "GST" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "Paid" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-center", children: "Stamps" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "When" })
              ] }) }),
              /* @__PURE__ */ jsx("tbody", { className: "divide-y divide-dashed divide-lucky-100", children: recentActivity.map((a) => /* @__PURE__ */ jsxs("tr", { className: "hover:bg-lucky-50/50 transition-colors", children: [
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-gray-900 font-medium", children: a.coupon_title ?? "—" }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-gray-600", children: a.location_name ?? "—" }),
                /* @__PURE__ */ jsxs("td", { className: "py-2.5 text-right text-gray-700", children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  a.original_bill_amount.toFixed(2)
                ] }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-right font-bold text-green-600", children: a.discount_amount > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
                  "-",
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  a.discount_amount.toFixed(2)
                ] }) : "—" }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-right text-gray-500", children: a.platform_fee > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  a.platform_fee.toFixed(2)
                ] }) : "—" }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-right text-gray-500", children: a.gst_amount > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  a.gst_amount.toFixed(2)
                ] }) : "—" }),
                /* @__PURE__ */ jsxs("td", { className: "py-2.5 text-right font-bold text-lucky-700", children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  a.total_paid.toFixed(2)
                ] }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-center", children: a.stamps_earned > 0 ? /* @__PURE__ */ jsxs("span", { className: "inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-lucky-100 text-lucky-700 text-xs font-bold", children: [
                  "🎫 ",
                  a.stamps_earned
                ] }) : "—" }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-right text-gray-400 text-xs", children: a.created_at })
              ] }, a.id)) })
            ] }) }) : /* @__PURE__ */ jsxs("div", { className: "text-center py-6", children: [
              /* @__PURE__ */ jsx("span", { className: "text-3xl", children: "💤" }),
              /* @__PURE__ */ jsx("p", { className: "text-gray-400 text-sm mt-2", children: "No activity yet." })
            ] })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "coupon-card p-6", children: [
            /* @__PURE__ */ jsxs("h3", { className: "font-display text-lg text-gray-900 mb-4 flex items-center gap-2", children: [
              /* @__PURE__ */ jsx("span", { className: "text-xl", children: "🎫" }),
              " My Stamps"
            ] }),
            stamps.length > 0 ? /* @__PURE__ */ jsx("div", { className: "overflow-x-auto", children: /* @__PURE__ */ jsxs("table", { className: "min-w-full text-sm", children: [
              /* @__PURE__ */ jsx("thead", { children: /* @__PURE__ */ jsxs("tr", { className: "border-b-2 border-dashed border-lucky-200 text-left text-lucky-600", children: [
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold", children: "Code" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold", children: "Source" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold", children: "Campaign" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "Bill Amount" }),
                /* @__PURE__ */ jsx("th", { className: "pb-2 font-bold text-right", children: "Earned" })
              ] }) }),
              /* @__PURE__ */ jsx("tbody", { className: "divide-y divide-dashed divide-lucky-100", children: stamps.map((s) => /* @__PURE__ */ jsxs("tr", { className: "hover:bg-lucky-50/50 transition-colors", children: [
                /* @__PURE__ */ jsx("td", { className: "py-2.5", children: /* @__PURE__ */ jsx("span", { className: "font-mono text-xs bg-lucky-100 text-lucky-700 px-2 py-0.5 rounded", children: s.code }) }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5", children: /* @__PURE__ */ jsxs("span", { className: `inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${s.source === "Plan Purchase" ? "bg-ticket-100 text-ticket-700" : "bg-lucky-100 text-lucky-700"}`, children: [
                  s.source === "Plan Purchase" ? "⭐" : "🧾",
                  " ",
                  s.source
                ] }) }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-gray-700 font-medium", children: s.campaign_name ?? "—" }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-right text-gray-600", children: s.bill_amount > 0 ? /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  s.bill_amount.toFixed(2)
                ] }) : "—" }),
                /* @__PURE__ */ jsx("td", { className: "py-2.5 text-right text-gray-400 text-xs", children: s.created_at })
              ] }, s.id)) })
            ] }) }) : /* @__PURE__ */ jsxs("div", { className: "text-center py-6", children: [
              /* @__PURE__ */ jsx("span", { className: "text-3xl", children: "🎭" }),
              /* @__PURE__ */ jsx("p", { className: "text-gray-400 text-sm mt-2", children: "No stamps collected yet." })
            ] })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "coupon-card p-6", children: [
            /* @__PURE__ */ jsxs("h3", { className: "font-display text-lg text-gray-900 mb-4 flex items-center gap-2", children: [
              /* @__PURE__ */ jsx("span", { className: "text-xl", children: "📋" }),
              " Activity Log"
            ] }),
            activityLogs.length > 0 ? /* @__PURE__ */ jsx("ul", { className: "space-y-3", children: activityLogs.map((log) => /* @__PURE__ */ jsxs("li", { className: "flex items-start gap-3 text-sm p-2 rounded-lg hover:bg-lucky-50/50 transition-colors", children: [
              /* @__PURE__ */ jsx("span", { className: "mt-0.5 flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-lucky-100 to-lucky-200 flex items-center justify-center text-lucky-600 text-xs font-bold", children: "⚡" }),
              /* @__PURE__ */ jsxs("div", { className: "flex-1", children: [
                /* @__PURE__ */ jsxs("p", { className: "text-gray-900", children: [
                  /* @__PURE__ */ jsx("span", { className: "font-bold capitalize text-lucky-700", children: log.event ?? "action" }),
                  log.subject_type && /* @__PURE__ */ jsxs("span", { className: "text-gray-500", children: [
                    " on ",
                    log.subject_type
                  ] }),
                  " — ",
                  log.description
                ] }),
                /* @__PURE__ */ jsx("p", { className: "text-xs text-gray-400", children: log.created_at })
              ] })
            ] }, log.id)) }) : /* @__PURE__ */ jsxs("div", { className: "text-center py-6", children: [
              /* @__PURE__ */ jsx("span", { className: "text-3xl", children: "📭" }),
              /* @__PURE__ */ jsx("p", { className: "text-gray-400 text-sm mt-2", children: "No activity yet." })
            ] })
          ] })
        ] }) })
      ]
    }
  );
}
function StatCard({ label, value, emoji, color }) {
  const colorMap = {
    lucky: "from-lucky-100 to-lucky-200 text-lucky-700 border-lucky-300",
    green: "from-green-100 to-green-200 text-green-700 border-green-300",
    emerald: "from-emerald-100 to-emerald-200 text-emerald-700 border-emerald-300",
    amber: "from-amber-100 to-amber-200 text-amber-700 border-amber-300",
    rose: "from-rose-100 to-rose-200 text-rose-700 border-rose-300"
  };
  return /* @__PURE__ */ jsxs("div", { className: `rounded-2xl p-4 text-center bg-gradient-to-br border-2 border-dashed shadow-sm hover:shadow-md transition-shadow ${colorMap[color] ?? "from-gray-100 to-gray-200 text-gray-700 border-gray-300"}`, children: [
    /* @__PURE__ */ jsx("div", { className: "text-2xl mb-1", children: emoji }),
    /* @__PURE__ */ jsx("p", { className: "text-2xl font-bold", children: value }),
    /* @__PURE__ */ jsx("p", { className: "text-xs mt-1 font-medium opacity-80", children: label })
  ] });
}
export {
  Dashboard as default
};
