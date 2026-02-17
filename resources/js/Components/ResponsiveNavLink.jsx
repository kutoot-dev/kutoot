import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={`flex w-full items-start border-l-4 py-2 pe-4 ps-3 ${
                active
                    ? 'border-lucky-500 bg-lucky-50 text-lucky-700 focus:border-lucky-700 focus:bg-lucky-100 focus:text-lucky-800'
                    : 'border-transparent text-gray-600 hover:border-lucky-300 hover:bg-lucky-50 hover:text-lucky-700 focus:border-lucky-300 focus:bg-lucky-50 focus:text-lucky-700'
            } text-base font-bold transition duration-150 ease-in-out focus:outline-none ${className}`}
        >
            {children}
        </Link>
    );
}
