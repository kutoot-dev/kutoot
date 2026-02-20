import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { A as AuthenticatedLayout } from "./AuthenticatedLayout-JzOoDiv3.js";
import { usePage, useForm, Head, Link, router } from "@inertiajs/react";
import { useState, useEffect } from "react";
import { I as InputError, T as TextInput } from "./TextInput-yXmXuxTf.js";
import { I as InputLabel } from "./InputLabel-CE_n4Upz.js";
import { P as PrimaryButton } from "./PrimaryButton-CCctEmCW.js";
import { M as Modal, S as SecondaryButton } from "./SecondaryButton-D1m0KP7f.js";
import { C as CurrencySymbol } from "./CurrencySymbol-BQz5ZRGv.js";
import "./ApplicationLogo-B2tGdv66.js";
import "@headlessui/react";
function Index({ auth, coupons, locations, planName, stampsPerHundred, primaryCampaign, availableCampaigns, remainingRedeemAmount, maxRedeemableAmount }) {
  const { platform_fee, gst_rate, platform_fee_type, appDebug } = usePage().props;
  const [confirmingRedemption, setConfirmingRedemption] = useState(false);
  const [selectedCoupon, setSelectedCoupon] = useState(null);
  const { data, setData, post, processing, errors, reset } = useForm({
    merchant_location_id: "",
    amount: "",
    campaign_id: primaryCampaign?.id || ""
  });
  const selectedLocationName = locations.find((l) => String(l.id) === String(data.merchant_location_id))?.name;
  useEffect(() => {
    if (appDebug) return;
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    document.body.appendChild(script);
    return () => {
      document.body.removeChild(script);
    };
  }, []);
  const confirmRedemption = (coupon) => {
    setSelectedCoupon(coupon);
    setConfirmingRedemption(true);
    if (coupon.merchant_location_id) {
      setData("merchant_location_id", coupon.merchant_location_id);
    } else {
      setData("merchant_location_id", "");
    }
  };
  const closeModal = () => {
    setConfirmingRedemption(false);
    setSelectedCoupon(null);
    reset();
  };
  const initiatePayment = async (e) => {
    e.preventDefault();
    const couponId = selectedCoupon?.id;
    const formData = { ...data };
    if (!couponId) return;
    if (appDebug) {
      router.post(route("coupons.redeem", couponId), formData, {
        onSuccess: () => closeModal(),
        onError: (errs) => console.error("Redeem errors:", errs)
      });
      return;
    }
    try {
      const response = await fetch(route("coupons.redeem", couponId), {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
        },
        body: JSON.stringify(formData)
      });
      const result = await response.json();
      if (response.ok) {
        closeModal();
        handleRazorpayPayment(result.order, result.transaction_id);
      } else {
        alert(result.error || "Something went wrong");
      }
    } catch (error) {
      console.error("Payment initiation failed", error);
      alert("Payment initiation failed. Please try again.");
    }
  };
  const handleRazorpayPayment = (order, transactionId) => {
    const options = {
      key: order.key,
      amount: order.amount,
      currency: order.currency,
      name: "Kutoot",
      description: `Payment for ${selectedCoupon.title}`,
      order_id: order.id,
      handler: function(response) {
        router.post(route("coupons.verify-payment", transactionId), {
          razorpay_payment_id: response.razorpay_payment_id,
          razorpay_order_id: response.razorpay_order_id,
          razorpay_signature: response.razorpay_signature
        });
      },
      prefill: {
        name: auth.user.name,
        email: auth.user.email
      },
      theme: {
        color: "#f08c10"
      }
    };
    const rzp = new window.Razorpay(options);
    rzp.open();
  };
  const calculateBreakdown = () => {
    const billAmount = parseFloat(data.amount) || 0;
    let discount = 0;
    if (selectedCoupon) {
      if (selectedCoupon.discount_type === "percentage") {
        discount = billAmount * parseFloat(selectedCoupon.discount_value) / 100;
      } else {
        discount = parseFloat(selectedCoupon.discount_value) || 0;
      }
      if (selectedCoupon.max_discount_amount) {
        discount = Math.min(discount, parseFloat(selectedCoupon.max_discount_amount));
      }
    }
    const finalBill = Math.max(0, billAmount - discount);
    const fee = parseFloat(platform_fee);
    const feeAmount = platform_fee_type === "percentage" ? billAmount * fee / 100 : fee;
    const gst = feeAmount * gst_rate / 100;
    const total = finalBill + feeAmount + gst;
    const estimatedStamps = Math.floor(billAmount / 100) * stampsPerHundred;
    return { billAmount, discount, finalBill, feeAmount, gst, total, estimatedStamps };
  };
  const breakdown = calculateBreakdown();
  return /* @__PURE__ */ jsxs(
    AuthenticatedLayout,
    {
      user: auth.user,
      header: /* @__PURE__ */ jsxs("h2", { className: "text-xl font-bold leading-tight text-white flex items-center gap-2", children: [
        "🎫 My Coupons (",
        planName,
        ")"
      ] }),
      children: [
        /* @__PURE__ */ jsx(Head, { title: "Coupons" }),
        /* @__PURE__ */ jsx("div", { className: "py-8", children: /* @__PURE__ */ jsx("div", { className: "max-w-7xl mx-auto sm:px-6 lg:px-8", children: /* @__PURE__ */ jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6", children: coupons.data.length > 0 ? coupons.data.map((coupon) => /* @__PURE__ */ jsxs("div", { className: "coupon-card overflow-hidden flex flex-col hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1", children: [
          /* @__PURE__ */ jsxs("div", { className: "p-6 flex-grow", children: [
            /* @__PURE__ */ jsx("span", { className: "inline-block px-3 py-1 text-xs font-bold text-lucky-700 bg-lucky-100 rounded-full mb-3 border border-lucky-200", children: coupon.merchant_location ? coupon.merchant_location.branch_name : "🌐 Global Coupon" }),
            /* @__PURE__ */ jsx("h3", { className: "font-display text-lg text-gray-900 mb-1", children: coupon.title }),
            /* @__PURE__ */ jsx("p", { className: "text-gray-500 text-sm mb-4", children: coupon.description }),
            /* @__PURE__ */ jsxs("div", { className: "bg-gradient-to-br from-lucky-50 to-ticket-50 p-4 rounded-xl text-sm text-gray-700 border border-dashed border-lucky-200", children: [
              /* @__PURE__ */ jsxs("div", { className: "flex justify-between mb-2 pb-2 border-b border-dashed border-lucky-100", children: [
                /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "Code:" }),
                /* @__PURE__ */ jsx("span", { className: "font-mono font-bold text-lucky-700 bg-lucky-100 px-2 py-0.5 rounded", children: coupon.code })
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "flex justify-between", children: [
                /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "Value:" }),
                /* @__PURE__ */ jsx("span", { className: "font-bold text-ticket-600", children: coupon.discount_type === "percentage" ? `${coupon.discount_value}% Off` : /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  coupon.discount_value,
                  " Off"
                ] }) })
              ] }),
              coupon.max_discount_amount && /* @__PURE__ */ jsxs("div", { className: "flex justify-between mt-2 pt-2 border-t border-dashed border-lucky-100", children: [
                /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "Max Savings:" }),
                /* @__PURE__ */ jsxs("span", { className: "font-bold text-green-600", children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  parseFloat(coupon.max_discount_amount).toFixed(2)
                ] })
              ] }),
              !coupon.max_discount_amount && coupon.discount_type === "fixed" && /* @__PURE__ */ jsxs("div", { className: "flex justify-between mt-2 pt-2 border-t border-dashed border-lucky-100", children: [
                /* @__PURE__ */ jsx("span", { className: "text-gray-500", children: "Max Savings:" }),
                /* @__PURE__ */ jsxs("span", { className: "font-bold text-green-600", children: [
                  /* @__PURE__ */ jsx(CurrencySymbol, {}),
                  parseFloat(coupon.discount_value).toFixed(2)
                ] })
              ] })
            ] })
          ] }),
          auth.user && remainingRedeemAmount !== void 0 && /* @__PURE__ */ jsx("div", { className: "px-6 pb-2", children: /* @__PURE__ */ jsxs("div", { className: "text-xs text-gray-500 flex justify-between items-center", children: [
            /* @__PURE__ */ jsx("span", { children: "Remaining Balance:" }),
            /* @__PURE__ */ jsxs("span", { className: `font-bold ${remainingRedeemAmount > 0 ? "text-green-600" : "text-red-500"}`, children: [
              /* @__PURE__ */ jsx(CurrencySymbol, {}),
              parseFloat(remainingRedeemAmount).toFixed(2),
              " / ",
              /* @__PURE__ */ jsx(CurrencySymbol, {}),
              parseFloat(maxRedeemableAmount).toFixed(2)
            ] })
          ] }) }),
          /* @__PURE__ */ jsx("div", { className: "flex justify-center gap-2 py-1.5 bg-gradient-to-r from-transparent via-lucky-50 to-transparent", children: [...Array(10)].map((_, i) => /* @__PURE__ */ jsx("div", { className: "w-2 h-2 rounded-full bg-lucky-200" }, i)) }),
          /* @__PURE__ */ jsx("div", { className: "bg-gradient-to-br from-lucky-50 to-ticket-50 px-6 py-4", children: auth.user ? /* @__PURE__ */ jsx(
            PrimaryButton,
            {
              className: "w-full justify-center",
              onClick: () => confirmRedemption(coupon),
              children: "🎟️ Redeem Now"
            }
          ) : /* @__PURE__ */ jsx(
            Link,
            {
              href: route("login"),
              className: "inline-flex items-center w-full justify-center gap-2 px-5 py-2.5 lucky-gradient border border-transparent rounded-full font-bold text-xs text-white uppercase tracking-widest hover:shadow-lg transition-all",
              children: "🔑 Login to Redeem"
            }
          ) })
        ] }, coupon.id)) : /* @__PURE__ */ jsxs("div", { className: "col-span-full text-center py-16", children: [
          /* @__PURE__ */ jsx("span", { className: "text-5xl mb-4 block", children: "🎭" }),
          /* @__PURE__ */ jsx("h3", { className: "font-display text-lg text-gray-900", children: "No coupons available" }),
          /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-gray-500", children: "Upgrade your plan to unlock more rewards." })
        ] }) }) }) }),
        /* @__PURE__ */ jsx(Modal, { show: confirmingRedemption, onClose: closeModal, children: /* @__PURE__ */ jsxs("form", { onSubmit: initiatePayment, className: "p-6", children: [
          /* @__PURE__ */ jsxs("h2", { className: "font-display text-lg text-gray-900 flex items-center gap-2", children: [
            /* @__PURE__ */ jsx("span", { children: "🎟️" }),
            " Redeem: ",
            selectedCoupon?.title,
            selectedLocationName && /* @__PURE__ */ jsxs("span", { className: "block text-sm font-normal text-lucky-600 mt-1", children: [
              "at ",
              selectedLocationName
            ] })
          ] }),
          /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-gray-600", children: "To redeem this coupon, please select the store location and enter the bill amount." }),
          /* @__PURE__ */ jsxs("div", { className: "mt-6", children: [
            /* @__PURE__ */ jsx(InputLabel, { htmlFor: "merchant_location_id", value: "Store Location" }),
            /* @__PURE__ */ jsxs(
              "select",
              {
                id: "merchant_location_id",
                name: "merchant_location_id",
                value: data.merchant_location_id,
                onChange: (e) => setData("merchant_location_id", e.target.value),
                className: "mt-1 block w-full border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-lg shadow-sm",
                required: true,
                children: [
                  /* @__PURE__ */ jsx("option", { value: "", children: "Select a location" }),
                  locations.map((loc) => /* @__PURE__ */ jsx("option", { value: loc.id, children: loc.name }, loc.id))
                ]
              }
            ),
            /* @__PURE__ */ jsx(InputError, { message: errors.merchant_location_id, className: "mt-2" })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "mt-6", children: [
            /* @__PURE__ */ jsx(InputLabel, { htmlFor: "amount", value: /* @__PURE__ */ jsxs("span", { children: [
              "Bill Amount (",
              /* @__PURE__ */ jsx(CurrencySymbol, {}),
              ")"
            ] }) }),
            /* @__PURE__ */ jsx(
              TextInput,
              {
                id: "amount",
                type: "number",
                step: "0.01",
                name: "amount",
                value: data.amount,
                onChange: (e) => setData("amount", e.target.value),
                className: "mt-1 block w-full",
                placeholder: "0.00",
                required: true
              }
            ),
            /* @__PURE__ */ jsx(InputError, { message: errors.amount, className: "mt-2" })
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "mt-6 bg-gradient-to-br from-lucky-50 to-ticket-50 p-4 rounded-xl border-2 border-dashed border-lucky-200", children: [
            /* @__PURE__ */ jsxs("div", { className: "flex justify-between text-sm text-gray-700 mb-1", children: [
              /* @__PURE__ */ jsx("span", { children: "Total Bill:" }),
              /* @__PURE__ */ jsxs("span", { className: "font-bold", children: [
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                breakdown.billAmount.toFixed(2)
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "flex justify-between text-sm text-gray-700 mb-1", children: [
              /* @__PURE__ */ jsx("span", { children: "Discount Applied:" }),
              /* @__PURE__ */ jsxs("span", { className: "font-bold text-green-600", children: [
                "- ",
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                breakdown.discount.toFixed(2)
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "flex justify-between text-sm font-bold text-lucky-700 mb-1 pt-1 border-t border-dashed border-lucky-200", children: [
              /* @__PURE__ */ jsx("span", { children: "Bill after Discount:" }),
              /* @__PURE__ */ jsxs("span", { children: [
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                breakdown.finalBill.toFixed(2)
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "flex justify-between text-sm text-gray-700 mb-1", children: [
              /* @__PURE__ */ jsx("span", { children: "Platform Fee:" }),
              /* @__PURE__ */ jsxs("span", { children: [
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                breakdown.feeAmount.toFixed(2)
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "flex justify-between text-sm text-gray-700 mb-2", children: [
              /* @__PURE__ */ jsxs("span", { children: [
                "GST (",
                gst_rate,
                "%):"
              ] }),
              /* @__PURE__ */ jsxs("span", { children: [
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                breakdown.gst.toFixed(2)
              ] })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "flex justify-between font-bold text-lucky-800 pt-2 border-t-2 border-dashed border-lucky-300", children: [
              /* @__PURE__ */ jsx("span", { children: "💰 Total to Pay:" }),
              /* @__PURE__ */ jsxs("span", { children: [
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                breakdown.total.toFixed(2)
              ] })
            ] })
          ] }),
          breakdown.estimatedStamps > 0 && /* @__PURE__ */ jsxs("div", { className: "mt-4 bg-green-50 p-3 rounded-lg border border-green-200 flex items-center gap-3", children: [
            /* @__PURE__ */ jsx("div", { className: "flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center", children: /* @__PURE__ */ jsx("span", { className: "text-green-700 font-bold text-lg", children: breakdown.estimatedStamps }) }),
            /* @__PURE__ */ jsxs("div", { children: [
              /* @__PURE__ */ jsxs("p", { className: "text-sm font-semibold text-green-800", children: [
                "You'll earn ",
                breakdown.estimatedStamps,
                " stamp",
                breakdown.estimatedStamps !== 1 ? "s" : ""
              ] }),
              /* @__PURE__ */ jsxs("p", { className: "text-xs text-green-600", children: [
                stampsPerHundred,
                " stamp",
                stampsPerHundred !== 1 ? "s" : "",
                " per ",
                /* @__PURE__ */ jsx(CurrencySymbol, {}),
                "100 bill"
              ] })
            ] })
          ] }),
          primaryCampaign ? /* @__PURE__ */ jsx("div", { className: "mt-4 bg-amber-50 p-3 rounded-lg border border-amber-200", children: /* @__PURE__ */ jsxs("p", { className: "text-sm text-amber-800", children: [
            /* @__PURE__ */ jsx("span", { className: "font-semibold", children: "Stamps will be added to:" }),
            " ",
            primaryCampaign.reward_name
          ] }) }) : availableCampaigns?.length > 0 ? /* @__PURE__ */ jsxs("div", { className: "mt-4", children: [
            /* @__PURE__ */ jsx(InputLabel, { htmlFor: "campaign_id", value: "Select Campaign for Stamps" }),
            /* @__PURE__ */ jsxs(
              "select",
              {
                id: "campaign_id",
                name: "campaign_id",
                value: data.campaign_id,
                onChange: (e) => setData("campaign_id", e.target.value),
                className: "mt-1 block w-full border-lucky-200 focus:border-lucky-500 focus:ring-lucky-500 rounded-lg shadow-sm",
                required: true,
                children: [
                  /* @__PURE__ */ jsx("option", { value: "", children: "Choose a campaign" }),
                  availableCampaigns.map((c) => /* @__PURE__ */ jsx("option", { value: c.id, children: c.reward_name }, c.id))
                ]
              }
            ),
            /* @__PURE__ */ jsx("p", { className: "mt-1 text-xs text-gray-500", children: "No primary campaign set. Select which campaign should receive your stamps." }),
            /* @__PURE__ */ jsx(InputError, { message: errors.campaign_id, className: "mt-1" })
          ] }) : null,
          /* @__PURE__ */ jsxs("div", { className: "mt-6 flex justify-end", children: [
            /* @__PURE__ */ jsx(SecondaryButton, { onClick: closeModal, children: "Cancel" }),
            /* @__PURE__ */ jsx(PrimaryButton, { className: "ms-3", disabled: processing, children: appDebug ? "🐛 Debug Redeem (Free)" : /* @__PURE__ */ jsxs(Fragment, { children: [
              "Pay ",
              /* @__PURE__ */ jsx(CurrencySymbol, {}),
              breakdown.total.toFixed(2),
              " & Redeem"
            ] }) })
          ] })
        ] }) })
      ]
    }
  );
}
export {
  Index as default
};
