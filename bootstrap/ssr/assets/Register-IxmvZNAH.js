import { jsxs, jsx } from "react/jsx-runtime";
import { T as TextInput, I as InputError } from "./TextInput-yXmXuxTf.js";
import { I as InputLabel } from "./InputLabel-CE_n4Upz.js";
import { P as PrimaryButton } from "./PrimaryButton-CCctEmCW.js";
import { G as GuestLayout } from "./GuestLayout-0v3_KDx7.js";
import { useForm, Head, Link } from "@inertiajs/react";
import { useState, useEffect } from "react";
import "./ApplicationLogo-B2tGdv66.js";
function Register({ status, debugOtp }) {
  const [otpSent, setOtpSent] = useState(false);
  const registerForm = useForm({
    name: "",
    email: "",
    mobile: ""
  });
  const verifyForm = useForm({
    otp: ""
  });
  useEffect(() => {
    if (debugOtp) {
      verifyForm.setData("otp", debugOtp);
      setOtpSent(true);
    }
  }, [debugOtp]);
  const handleSendOtp = (e) => {
    e.preventDefault();
    registerForm.post(route("register.send-otp"), {
      preserveScroll: true,
      onSuccess: (page) => {
        setOtpSent(true);
        if (page.props.debugOtp) {
          verifyForm.setData("otp", page.props.debugOtp);
        }
      }
    });
  };
  const handleVerify = (e) => {
    e.preventDefault();
    verifyForm.post(route("register.verify"), {
      onFinish: () => verifyForm.reset("otp")
    });
  };
  const handleResendOtp = () => {
    registerForm.post(route("register.send-otp"), {
      preserveScroll: true,
      onSuccess: (page) => {
        if (page.props.debugOtp) {
          verifyForm.setData("otp", page.props.debugOtp);
        }
      }
    });
  };
  const handleBack = () => {
    setOtpSent(false);
    verifyForm.reset();
  };
  return /* @__PURE__ */ jsxs(GuestLayout, { children: [
    /* @__PURE__ */ jsx(Head, { title: "Register" }),
    status && /* @__PURE__ */ jsx("div", { className: "mb-4 text-sm font-medium text-green-600", children: status }),
    !otpSent ? /* @__PURE__ */ jsxs("form", { onSubmit: handleSendOtp, children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-4 text-center", children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-semibold text-lucky-700", children: "Create Account" }),
        /* @__PURE__ */ jsx("p", { className: "mt-1 text-sm text-gray-500", children: "We'll verify your mobile number with OTP." })
      ] }),
      /* @__PURE__ */ jsxs("div", { children: [
        /* @__PURE__ */ jsx(InputLabel, { htmlFor: "name", value: "Name" }),
        /* @__PURE__ */ jsx(
          TextInput,
          {
            id: "name",
            name: "name",
            value: registerForm.data.name,
            className: "mt-1 block w-full",
            autoComplete: "name",
            isFocused: true,
            onChange: (e) => registerForm.setData("name", e.target.value)
          }
        ),
        /* @__PURE__ */ jsx(
          InputError,
          {
            message: registerForm.errors.name,
            className: "mt-2"
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4", children: [
        /* @__PURE__ */ jsx(InputLabel, { htmlFor: "email", value: "Email" }),
        /* @__PURE__ */ jsx(
          TextInput,
          {
            id: "email",
            type: "email",
            name: "email",
            value: registerForm.data.email,
            className: "mt-1 block w-full",
            autoComplete: "username",
            onChange: (e) => registerForm.setData("email", e.target.value)
          }
        ),
        /* @__PURE__ */ jsx(
          InputError,
          {
            message: registerForm.errors.email,
            className: "mt-2"
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4", children: [
        /* @__PURE__ */ jsx(InputLabel, { htmlFor: "mobile", value: "Mobile Number" }),
        /* @__PURE__ */ jsx(
          TextInput,
          {
            id: "mobile",
            type: "tel",
            name: "mobile",
            value: registerForm.data.mobile,
            className: "mt-1 block w-full",
            autoComplete: "tel",
            placeholder: "9876543210",
            onChange: (e) => registerForm.setData("mobile", e.target.value)
          }
        ),
        /* @__PURE__ */ jsx(
          InputError,
          {
            message: registerForm.errors.mobile,
            className: "mt-2"
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4 flex items-center justify-between", children: [
        /* @__PURE__ */ jsx(
          Link,
          {
            href: route("login"),
            className: "rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-lucky-500 focus:ring-offset-2",
            children: "Already registered?"
          }
        ),
        /* @__PURE__ */ jsx(PrimaryButton, { disabled: registerForm.processing, children: "Send OTP" })
      ] })
    ] }) : /* @__PURE__ */ jsxs("form", { onSubmit: handleVerify, children: [
      /* @__PURE__ */ jsxs("div", { className: "mb-4 text-center", children: [
        /* @__PURE__ */ jsx("h2", { className: "text-lg font-semibold text-lucky-700", children: "Verify Mobile" }),
        /* @__PURE__ */ jsxs("p", { className: "mt-1 text-sm text-gray-500", children: [
          "Enter the 6-digit code sent to",
          " ",
          /* @__PURE__ */ jsx("span", { className: "font-medium text-lucky-600", children: registerForm.data.mobile })
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
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "mt-4 flex items-center justify-between", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex flex-col gap-1", children: [
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              onClick: handleResendOtp,
              disabled: registerForm.processing,
              className: "text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none",
              children: "Resend OTP"
            }
          ),
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              onClick: handleBack,
              className: "text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none",
              children: "Edit details"
            }
          )
        ] }),
        /* @__PURE__ */ jsx(PrimaryButton, { disabled: verifyForm.processing, children: "Verify & Register" })
      ] })
    ] })
  ] });
}
export {
  Register as default
};
