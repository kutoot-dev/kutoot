import { jsx } from "react/jsx-runtime";
function PrimaryButton({
  className = "",
  disabled,
  children,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    "button",
    {
      ...props,
      className: `inline-flex items-center rounded-full border border-transparent bg-gradient-to-r from-lucky-500 to-lucky-600 px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-white shadow-md transition duration-150 ease-in-out hover:from-lucky-600 hover:to-ticket-500 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-lucky-400 focus:ring-offset-2 active:from-lucky-700 active:to-ticket-600 transform hover:-translate-y-0.5 ${disabled && "opacity-25 hover:translate-y-0"} ` + className,
      disabled,
      children
    }
  );
}
export {
  PrimaryButton as P
};
