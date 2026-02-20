import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { A as ApplicationLogo } from "./ApplicationLogo-B2tGdv66.js";
import { Transition } from "@headlessui/react";
import { Link, usePage } from "@inertiajs/react";
import { useState, createContext, useContext } from "react";
const DropDownContext = createContext();
const Dropdown = ({ children }) => {
  const [open, setOpen] = useState(false);
  const toggleOpen = () => {
    setOpen((previousState) => !previousState);
  };
  return /* @__PURE__ */ jsx(DropDownContext.Provider, { value: { open, setOpen, toggleOpen }, children: /* @__PURE__ */ jsx("div", { className: "relative", children }) });
};
const Trigger = ({ children }) => {
  const { open, setOpen, toggleOpen } = useContext(DropDownContext);
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx("div", { onClick: toggleOpen, children }),
    open && /* @__PURE__ */ jsx(
      "div",
      {
        className: "fixed inset-0 z-40",
        onClick: () => setOpen(false)
      }
    )
  ] });
};
const Content = ({
  align = "right",
  width = "48",
  contentClasses = "py-1 bg-white",
  children
}) => {
  const { open, setOpen } = useContext(DropDownContext);
  let alignmentClasses = "origin-top";
  if (align === "left") {
    alignmentClasses = "ltr:origin-top-left rtl:origin-top-right start-0";
  } else if (align === "right") {
    alignmentClasses = "ltr:origin-top-right rtl:origin-top-left end-0";
  }
  let widthClasses = "";
  if (width === "48") {
    widthClasses = "w-48";
  }
  return /* @__PURE__ */ jsx(Fragment, { children: /* @__PURE__ */ jsx(
    Transition,
    {
      show: open,
      enter: "transition ease-out duration-200",
      enterFrom: "opacity-0 scale-95",
      enterTo: "opacity-100 scale-100",
      leave: "transition ease-in duration-75",
      leaveFrom: "opacity-100 scale-100",
      leaveTo: "opacity-0 scale-95",
      children: /* @__PURE__ */ jsx(
        "div",
        {
          className: `absolute z-50 mt-2 rounded-md shadow-lg ${alignmentClasses} ${widthClasses}`,
          onClick: () => setOpen(false),
          children: /* @__PURE__ */ jsx(
            "div",
            {
              className: `rounded-md ring-1 ring-black ring-opacity-5 ` + contentClasses,
              children
            }
          )
        }
      )
    }
  ) });
};
const DropdownLink = ({ className = "", children, ...props }) => {
  return /* @__PURE__ */ jsx(
    Link,
    {
      ...props,
      className: "block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none " + className,
      children
    }
  );
};
Dropdown.Trigger = Trigger;
Dropdown.Content = Content;
Dropdown.Link = DropdownLink;
function NavLink({
  active = false,
  className = "",
  children,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Link,
    {
      ...props,
      className: "inline-flex items-center border-b-3 px-1 pt-1 text-sm font-bold leading-5 transition duration-150 ease-in-out focus:outline-none " + (active ? "border-lucky-500 text-lucky-700 focus:border-lucky-700" : "border-transparent text-gray-500 hover:border-lucky-300 hover:text-lucky-600 focus:border-lucky-300 focus:text-lucky-600") + " " + className,
      children
    }
  );
}
function ResponsiveNavLink({
  active = false,
  className = "",
  children,
  ...props
}) {
  return /* @__PURE__ */ jsx(
    Link,
    {
      ...props,
      className: `flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${active ? "border-lucky-500 bg-lucky-50 text-lucky-700 focus:border-lucky-700 focus:bg-lucky-100 focus:text-lucky-800" : "border-transparent text-gray-600 hover:border-lucky-300 hover:bg-lucky-50 hover:text-lucky-700 focus:border-lucky-300 focus:bg-lucky-50 focus:text-lucky-700"} text-base font-bold transition duration-150 ease-in-out focus:outline-none ${className}`,
      children
    }
  );
}
function AuthenticatedLayout({ header, children }) {
  const user = usePage().props.auth.user;
  const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);
  return /* @__PURE__ */ jsxs("div", { className: "min-h-screen bg-gradient-to-br from-lucky-50 via-white to-ticket-50 confetti-bg", children: [
    /* @__PURE__ */ jsxs("nav", { className: "border-b-2 border-lucky-200 bg-white/90 backdrop-blur-sm shadow-sm", children: [
      /* @__PURE__ */ jsx("div", { className: "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8", children: /* @__PURE__ */ jsxs("div", { className: "flex h-16 justify-between", children: [
        /* @__PURE__ */ jsxs("div", { className: "flex", children: [
          /* @__PURE__ */ jsx("div", { className: "flex shrink-0 items-center", children: /* @__PURE__ */ jsx(Link, { href: "/", children: /* @__PURE__ */ jsx(ApplicationLogo, {}) }) }),
          /* @__PURE__ */ jsxs("div", { className: "hidden space-x-8 sm:-my-px sm:ms-10 sm:flex", children: [
            user && /* @__PURE__ */ jsx(NavLink, { href: route("dashboard"), active: route().current("dashboard"), children: "🎯 Dashboard" }),
            /* @__PURE__ */ jsx(NavLink, { href: route("campaigns.index"), active: route().current("campaigns.*"), children: "🏆 Campaigns" }),
            /* @__PURE__ */ jsx(NavLink, { href: route("coupons.index"), active: route().current("coupons.*"), children: "🎫 Coupons" }),
            /* @__PURE__ */ jsx(NavLink, { href: route("subscriptions.index"), active: route().current("subscriptions.*"), children: "⭐ Plans" })
          ] })
        ] }),
        /* @__PURE__ */ jsx("div", { className: "hidden sm:ms-6 sm:flex sm:items-center", children: /* @__PURE__ */ jsx("div", { className: "relative ms-3", children: user ? /* @__PURE__ */ jsxs(Dropdown, { children: [
          /* @__PURE__ */ jsx(Dropdown.Trigger, { children: /* @__PURE__ */ jsx("span", { className: "inline-flex rounded-full", children: /* @__PURE__ */ jsxs(
            "button",
            {
              type: "button",
              className: "inline-flex items-center gap-2 rounded-full border-2 border-lucky-200 bg-lucky-50 px-4 py-2 text-sm font-bold text-lucky-700 transition duration-150 ease-in-out hover:bg-lucky-100 focus:outline-none",
              children: [
                /* @__PURE__ */ jsx("span", { className: "w-6 h-6 rounded-full bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-xs font-bold", children: user.name.charAt(0).toUpperCase() }),
                user.name,
                /* @__PURE__ */ jsx(
                  "svg",
                  {
                    className: "-me-0.5 ms-1 h-4 w-4 text-lucky-400",
                    xmlns: "http://www.w3.org/2000/svg",
                    viewBox: "0 0 20 20",
                    fill: "currentColor",
                    children: /* @__PURE__ */ jsx(
                      "path",
                      {
                        fillRule: "evenodd",
                        d: "M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z",
                        clipRule: "evenodd"
                      }
                    )
                  }
                )
              ]
            }
          ) }) }),
          /* @__PURE__ */ jsxs(Dropdown.Content, { children: [
            /* @__PURE__ */ jsx(
              Dropdown.Link,
              {
                href: route("profile.edit"),
                children: "👤 Profile"
              }
            ),
            /* @__PURE__ */ jsx(
              Dropdown.Link,
              {
                href: route("logout"),
                method: "post",
                as: "button",
                children: "🚪 Log Out"
              }
            )
          ] })
        ] }) : /* @__PURE__ */ jsx("div", { className: "flex gap-3", children: /* @__PURE__ */ jsx(
          Link,
          {
            href: route("login"),
            className: "rounded-full px-4 py-2 text-sm font-bold text-lucky-700 border-2 border-lucky-300 hover:bg-lucky-50 transition-colors",
            children: "Log in / Sign up"
          }
        ) }) }) }),
        /* @__PURE__ */ jsx("div", { className: "-me-2 flex items-center sm:hidden", children: /* @__PURE__ */ jsx(
          "button",
          {
            onClick: () => setShowingNavigationDropdown(
              (previousState) => !previousState
            ),
            className: "inline-flex items-center justify-center rounded-full p-2 text-lucky-500 transition duration-150 ease-in-out hover:bg-lucky-50 hover:text-lucky-700 focus:bg-lucky-50 focus:text-lucky-700 focus:outline-none",
            children: /* @__PURE__ */ jsxs(
              "svg",
              {
                className: "h-6 w-6",
                stroke: "currentColor",
                fill: "none",
                viewBox: "0 0 24 24",
                children: [
                  /* @__PURE__ */ jsx(
                    "path",
                    {
                      className: !showingNavigationDropdown ? "inline-flex" : "hidden",
                      strokeLinecap: "round",
                      strokeLinejoin: "round",
                      strokeWidth: "2",
                      d: "M4 6h16M4 12h16M4 18h16"
                    }
                  ),
                  /* @__PURE__ */ jsx(
                    "path",
                    {
                      className: showingNavigationDropdown ? "inline-flex" : "hidden",
                      strokeLinecap: "round",
                      strokeLinejoin: "round",
                      strokeWidth: "2",
                      d: "M6 18L18 6M6 6l12 12"
                    }
                  )
                ]
              }
            )
          }
        ) })
      ] }) }),
      /* @__PURE__ */ jsxs(
        "div",
        {
          className: (showingNavigationDropdown ? "block" : "hidden") + " sm:hidden",
          children: [
            /* @__PURE__ */ jsxs("div", { className: "space-y-1 pb-3 pt-2", children: [
              user && /* @__PURE__ */ jsx(
                ResponsiveNavLink,
                {
                  href: route("dashboard"),
                  active: route().current("dashboard"),
                  children: "🎯 Dashboard"
                }
              ),
              /* @__PURE__ */ jsx(
                ResponsiveNavLink,
                {
                  href: route("campaigns.index"),
                  active: route().current("campaigns.*"),
                  children: "🏆 Campaigns"
                }
              ),
              /* @__PURE__ */ jsx(
                ResponsiveNavLink,
                {
                  href: route("coupons.index"),
                  active: route().current("coupons.*"),
                  children: "🎫 Coupons"
                }
              ),
              /* @__PURE__ */ jsx(
                ResponsiveNavLink,
                {
                  href: route("subscriptions.index"),
                  active: route().current("subscriptions.*"),
                  children: "⭐ Plans"
                }
              )
            ] }),
            user ? /* @__PURE__ */ jsxs("div", { className: "border-t border-lucky-200 pb-1 pt-4", children: [
              /* @__PURE__ */ jsxs("div", { className: "px-4 flex items-center gap-3", children: [
                /* @__PURE__ */ jsx("span", { className: "w-8 h-8 rounded-full bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-sm font-bold", children: user.name.charAt(0).toUpperCase() }),
                /* @__PURE__ */ jsxs("div", { children: [
                  /* @__PURE__ */ jsx("div", { className: "text-base font-bold text-gray-800", children: user.name }),
                  /* @__PURE__ */ jsx("div", { className: "text-sm text-gray-500", children: user.email })
                ] })
              ] }),
              /* @__PURE__ */ jsxs("div", { className: "mt-3 space-y-1", children: [
                /* @__PURE__ */ jsx(ResponsiveNavLink, { href: route("profile.edit"), children: "👤 Profile" }),
                /* @__PURE__ */ jsx(
                  ResponsiveNavLink,
                  {
                    method: "post",
                    href: route("logout"),
                    as: "button",
                    children: "🚪 Log Out"
                  }
                )
              ] })
            ] }) : /* @__PURE__ */ jsx("div", { className: "border-t border-lucky-200 pb-1 pt-4", children: /* @__PURE__ */ jsx("div", { className: "mt-3 space-y-1", children: /* @__PURE__ */ jsx(ResponsiveNavLink, { href: route("login"), children: "Log in / Sign up" }) }) })
          ]
        }
      )
    ] }),
    header && /* @__PURE__ */ jsx("header", { className: "bg-gradient-to-r from-lucky-500 via-lucky-400 to-ticket-400 shadow-lg", children: /* @__PURE__ */ jsx("div", { className: "mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8", children: /* @__PURE__ */ jsx("div", { className: "text-white font-display", children: header }) }) }),
    /* @__PURE__ */ jsx("main", { children })
  ] });
}
export {
  AuthenticatedLayout as A
};
