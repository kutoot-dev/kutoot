import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="min-h-screen bg-gradient-to-br from-lucky-50 via-white to-ticket-50 confetti-bg">
            <nav className="border-b-2 border-lucky-200 bg-white/90 backdrop-blur-sm shadow-sm">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {user && (
                                    <NavLink href={route('dashboard')} active={route().current('dashboard')}>
                                        🎯 Dashboard
                                    </NavLink>
                                )}
                                {user && (
                                    <NavLink href={route('transactions.index')} active={route().current('transactions.*')}>
                                        💳 Transactions
                                    </NavLink>
                                )}
                                <NavLink href={route('campaigns.index')} active={route().current('campaigns.*')}>
                                    🏆 Campaigns
                                </NavLink>
                                <NavLink href={route('coupons.index')} active={route().current('coupons.*')}>
                                    🎫 Coupons
                                </NavLink>
                                <NavLink href={route('subscriptions.index')} active={route().current('subscriptions.*')}>
                                    ⭐ Plans
                                </NavLink>
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative ms-3">
                                {user ? (
                                    <Dropdown>
                                        <Dropdown.Trigger>
                                            <span className="inline-flex rounded-full">
                                                <button
                                                    type="button"
                                                    className="inline-flex items-center gap-2 rounded-full border-2 border-lucky-200 bg-lucky-50 px-4 py-2 text-sm font-bold text-lucky-700 transition duration-150 ease-in-out hover:bg-lucky-100 focus:outline-none"
                                                >
                                                    <span className="w-6 h-6 rounded-full bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-xs font-bold">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </span>
                                                    {user.name}

                                                    <svg
                                                        className="-me-0.5 ms-1 h-4 w-4 text-lucky-400"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fillRule="evenodd"
                                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                            clipRule="evenodd"
                                                        />
                                                    </svg>
                                                </button>
                                            </span>
                                        </Dropdown.Trigger>

                                        <Dropdown.Content>
                                            <Dropdown.Link
                                                href={route('profile.edit')}
                                            >
                                                👤 Profile
                                            </Dropdown.Link>
                                            <Dropdown.Link
                                                href={route('logout')}
                                                method="post"
                                                as="button"
                                            >
                                                🚪 Log Out
                                            </Dropdown.Link>
                                        </Dropdown.Content>
                                    </Dropdown>
                                ) : (
                                    <div className="flex gap-3">
                                        <Link
                                            href={route('login')}
                                            className="rounded-full px-4 py-2 text-sm font-bold text-lucky-700 border-2 border-lucky-300 hover:bg-lucky-50 transition-colors"
                                        >
                                            Log in / Sign up
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-full p-2 text-lucky-500 transition duration-150 ease-in-out hover:bg-lucky-50 hover:text-lucky-700 focus:bg-lucky-50 focus:text-lucky-700 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {user && (
                            <ResponsiveNavLink
                                href={route('dashboard')}
                                active={route().current('dashboard')}
                            >
                                🎯 Dashboard
                            </ResponsiveNavLink>
                        )}
                        {user && (
                            <ResponsiveNavLink
                                href={route('transactions.index')}
                                active={route().current('transactions.*')}
                            >
                                💳 Transactions
                            </ResponsiveNavLink>
                        )}
                        <ResponsiveNavLink
                            href={route('campaigns.index')}
                            active={route().current('campaigns.*')}
                        >
                            🏆 Campaigns
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('coupons.index')}
                            active={route().current('coupons.*')}
                        >
                            🎫 Coupons
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('subscriptions.index')}
                            active={route().current('subscriptions.*')}
                        >
                            ⭐ Plans
                        </ResponsiveNavLink>
                    </div>

                    {user ? (
                        <div className="border-t border-lucky-200 pb-1 pt-4">
                            <div className="px-4 flex items-center gap-3">
                                <span className="w-8 h-8 rounded-full bg-gradient-to-br from-lucky-400 to-ticket-400 flex items-center justify-center text-white text-sm font-bold">
                                    {user.name.charAt(0).toUpperCase()}
                                </span>
                                <div>
                                    <div className="text-base font-bold text-gray-800">
                                        {user.name}
                                    </div>
                                    <div className="text-sm text-gray-500">
                                        {user.email}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('profile.edit')}>
                                    👤 Profile
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                >
                                    🚪 Log Out
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    ) : (
                        <div className="border-t border-lucky-200 pb-1 pt-4">
                            <div className="mt-3 space-y-1">
                                <ResponsiveNavLink href={route('login')}>
                                    Log in / Sign up
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    )}
                </div>
            </nav>

            {header && (
                <header className="bg-gradient-to-r from-lucky-500 via-lucky-400 to-ticket-400 shadow-lg">
                    <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        <div className="text-white font-display">{header}</div>
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
