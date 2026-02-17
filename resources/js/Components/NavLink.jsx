import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center border-b-3 px-1 pt-1 text-sm font-bold leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                (active
                    ? 'border-lucky-500 text-lucky-700 focus:border-lucky-700'
                    : 'border-transparent text-gray-500 hover:border-lucky-300 hover:text-lucky-600 focus:border-lucky-300 focus:text-lucky-600') +
                ' ' + className
            }
        >
            {children}
        </Link>
    );
}
