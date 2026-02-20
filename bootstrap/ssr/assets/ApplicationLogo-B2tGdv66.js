import { jsx } from "react/jsx-runtime";
function ApplicationLogo({ className = "", ...props }) {
  return /* @__PURE__ */ jsx(
    "img",
    {
      src: "/images/kutoot-name-logo.svg",
      alt: "Kutoot",
      className: `h-10 w-auto ${className}`,
      ...props
    }
  );
}
export {
  ApplicationLogo as A
};
