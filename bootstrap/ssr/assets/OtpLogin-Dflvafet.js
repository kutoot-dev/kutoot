import { jsxs, jsx } from "react/jsx-runtime";
import { T as TextInput, I as InputError } from "./TextInput-yXmXuxTf.js";
import { I as InputLabel } from "./InputLabel-CE_n4Upz.js";
import { P as PrimaryButton } from "./PrimaryButton-CCctEmCW.js";
import { G as GuestLayout } from "./GuestLayout-0v3_KDx7.js";
import { useForm, Head } from "@inertiajs/react";
import { useState } from "react";
import "./ApplicationLogo-B2tGdv66.js";
function OtpLogin({ status, debugOtp }) {
  const [otpSent, setOtpSent] = useState(false);
  const sendForm = useForm({
    identifier: ""
  });
  const verifyForm = useForm({
    identifier: "",
    otp: ""
  });
  const handleSendOtp = (e) => {
    e.preventDefault();
    sendForm.post(route("otp-login.send"), {
      preserveScroll: true,
      onSuccess: (page) => {
        setOtpSent(true);
        verifyForm.setData("identifier", sendForm.data.identifier);
        if (page.props.debugOtp) {
          verifyForm.setData((prev) => ({
            ...prev,
            identifier: sendForm.data.identifier,
            otp: page.props.debugOtp
          }));
        }
      }
    });
  };
  const handleVerifyOtp = (e) => {
    e.preventDefault();
    verifyForm.post(route("otp-login.verify"), {
      onFinish: () => verifyForm.reset("otp")
    });
  };
  const handleResendOtp = () => {
    sendForm.post(route("otp-login.send"), {
      preserveScroll: true,
      onSuccess: (page) => {
        if (page.props.debugOtp) {
          verifyForm.setData("otp", page.props.debugOtp);
        }
      }
    });
  };
  const handleChangeIdentifier = () => {
    setOtpSent(false);
    verifyForm.reset();
    sendForm.reset();
  };
  return /* @__PURE__ */ jsxs(GuestLayout, { children: [
    /* @__PURE__ */ jsx(Head, { title: "OTP Login" }),
    status && /* @__PURE__ */ jsx("div", { className: "mb-4 text-sm font-medium text-green-600", children: status }),
    !otpSent ? /* @__PURE__ */ jsxs("form", { onSubmit: handleSendOtp, children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-4 text-center", children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-semibold text-lucky-700", children: "Login with OTP" }),
        /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-gray-500", children: "Enter your email or mobile number to receive a one-time password." })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx(
          InputLabel,
          {
            htmlFor: "identifier",
            value: "Email or Mobile"
          }
        ),
        /* @__PURE__ */ jsx(
          TextInput,
          {
            id: "identifier",
            type: "text",
            name: "identifier",
            value: sendForm.data.identifier,
            className: "mt-1 block w-full",
            isFocused: true,
            placeholder: "email@example.com or 9876543210",
            onChange: (e) => sendForm.setData("identifier", e.target.value)
          }
        ),
        /* @__PURE__ */ jsx(
          InputError,
          {
            message: sendForm.errors.identifier,
            className: "mt-2"
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4 flex items-center justify-between", children: [
        /* @__PURE__ */ jsx("div", { className: "flex flex-col gap-1" }),
        /* @__PURE__ */ jsx(PrimaryButton, { disabled: sendForm.processing, children: "Send OTP" })
      ] })
    ] }) : /* @__PURE__ */ jsxs("form", { onSubmit: handleVerifyOtp, children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-4 text-center", children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-semibold text-lucky-700", children: "Enter OTP" }),
        /* @__PURE__ */ jsxs("p", { className: "mt-1 text-sm text-gray-500", children: [
          "We sent a 6-digit code to",
          " ",
          /* @__PURE__ */ jsx("span", { className: "font-medium text-lucky-600", children: sendForm.data.identifier })
        ] })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx(InputLabel, { htmlFor: "otp", value: "One-Time Password" }),
        /* @__PURE__ */ jsx(
          TextInput,
          {
            id: "otp",
            type: "text",
            name: "otp",
            value: verifyForm.data.otp,
            className: "mt-1 block w-full text-center text-2xl tracking-[0.5em]",
            isFocused: true,
            maxLength: 6,
            placeholder: "000000",
            autoComplete: "one-time-code",
            onChange: (e) => verifyForm.setData(
              "otp",
              e.target.value.replace(/\D/g, "")
            )
          }
        ),
        /* @__PURE__ */ jsx(
          InputError,
          {
            message: verifyForm.errors.otp,
            className: "mt-2"
          }
        ),
        /* @__PURE__ */ jsx(
          InputError,
          {
            message: verifyForm.errors.identifier,
            className: "mt-2"
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4 flex items-center justify-between", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              onClick: handleResendOtp,
              disabled: sendForm.processing,
              className: "text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none",
              children: "Resend OTP"
            }
          ),
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              onClick: handleChangeIdentifier,
              className: "text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none",
              children: "Change email/mobile"
            }
          )
        ] }),
        /* @__PURE__ */ jsx(PrimaryButton, { disabled: verifyForm.processing, children: "Verify & Login" })
      ] })
    ] })
  ] });
}
export {
  OtpLogin as default
};
