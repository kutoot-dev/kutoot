import { jsx, Fragment } from "react/jsx-runtime";
import { usePage } from "@inertiajs/react";
function CurrencySymbol() {
  const { currency } = usePage().props;
  const symbols = {
    "INR": "₹",
    "USD": "$",
    "EUR": "€",
    "GBP": "£"
  };
  return /* @__PURE__ */ jsx(Fragment, { children: symbols[currency] || currency });
}
export {
  CurrencySymbol as C
};
