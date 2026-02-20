import { jsxs, jsx } from "react/jsx-runtime";
import { A as AuthenticatedLayout } from "./AuthenticatedLayout-JzOoDiv3.js";
import { useForm, Head } from "@inertiajs/react";
import { T as TextInput, I as InputError } from "./TextInput-yXmXuxTf.js";
import { I as InputLabel } from "./InputLabel-CE_n4Upz.js";
import { P as PrimaryButton } from "./PrimaryButton-CCctEmCW.js";
import { Transition } from "@headlessui/react";
import "./ApplicationLogo-B2tGdv66.js";
import "react";
function LinkQr({ auth, locations }) {
  const { data, setData, post, processing, errors, recentlySuccessful, reset } = useForm({
    unique_code: "",
    merchant_location_id: ""
  });
  const submit = (e) => {
    e.preventDefault();
    post(route("executive.qr.link"), {
      onSuccess: () => reset("unique_code")
    });
  };
  return /* @__PURE__ */ jsxs(
    AuthenticatedLayout,
    {
      user: auth.user,
      header: /* @__PURE__ */ jsx("h2", { className: "font-semibold text-xl text-gray-800 leading-tight", children: "Link QR Sticker" }),
      children: [
        /* @__PURE__ */ jsx(Head, { title: "Link QR Sticker" }),
        /* @__PURE__ */ jsx("div", { className: "py-12", children: /* @__PURE__ */ jsx("div", { className: "max-w-7xl mx-auto sm:px-6 lg:px-8", children: /* @__PURE__ */ jsx("div", { className: "bg-white overflow-hidden shadow-sm sm:rounded-lg", children: /* @__PURE__ */ jsxs("div", { className: "p-6 text-gray-900", children: [
          /* @__PURE__ */ jsxs("header", { children: [
            /* @__PURE__ */ jsx("h2", { className: "text-lg font-medium text-gray-900", children: "Link a Physical QR Code" }),
            /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-gray-600", children: "Enter the code printed on the Kutoot sticker and select the merchant location to link it." })
          ] }),
          /* @__PURE__ */ jsxs("form", { onSubmit: submit, className: "mt-6 space-y-6 max-w-xl", children: [
            /* @__PURE__ */ jsxs("div", { children: [
              /* @__PURE__ */ jsx(InputLabel, { htmlFor: "unique_code", value: "Sticker Code (e.g. KUT-0001)" }),
              /* @__PURE__ */ jsx(
                TextInput,
                {
                  id: "unique_code",
                  className: "mt-1 block w-full uppercase",
                  value: data.unique_code,
                  onChange: (e) => setData("unique_code", e.target.value),
                  required: true,
                  autoFocus: true,
                  placeholder: "KUT-XXXX"
                }
              ),
              /* @__PURE__ */ jsx(InputError, { className: "mt-2", message: errors.unique_code })
            ] }),
            /* @__PURE__ */ jsxs("div", { children: [
              /* @__PURE__ */ jsx(InputLabel, { htmlFor: "merchant_location_id", value: "Merchant Location" }),
              /* @__PURE__ */ jsxs(
                "select",
                {
                  id: "merchant_location_id",
                  className: "mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm",
                  value: data.merchant_location_id,
                  onChange: (e) => setData("merchant_location_id", e.target.value),
                  required: true,
                  children: [
                    /* @__PURE__ */ jsx("option", { value: "", children: "Select a location" }),
                    locations.map((loc) => /* @__PURE__ */ jsx("option", { value: loc.id, children: loc.name }, loc.id))
                  ]
                }
              ),
              /* @__PURE__ */ jsx(InputError, { className: "mt-2", message: errors.merchant_location_id })
            ] }),
            /* @__PURE__ */ jsxs("div", { className: "flex items-center gap-4", children: [
              /* @__PURE__ */ jsx(PrimaryButton, { disabled: processing, children: "Link QR Code" }),
              /* @__PURE__ */ jsx(
                Transition,
                {
                  show: recentlySuccessful,
                  enter: "transition ease-in-out",
                  enterFrom: "opacity-0",
                  leave: "transition ease-in-out",
                  leaveTo: "opacity-0",
                  children: /* @__PURE__ */ jsx("p", { className: "text-sm text-gray-600", children: "Linked successfully." })
                }
              )
            ] })
          ] })
        ] }) }) }) })
      ]
    }
  );
}
export {
  LinkQr as default
};
