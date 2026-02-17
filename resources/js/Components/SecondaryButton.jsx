export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            type={type}
            className={
                `inline-flex items-center rounded-full border-2 border-lucky-300 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-lucky-700 shadow-sm transition duration-150 ease-in-out hover:bg-lucky-50 hover:border-lucky-400 focus:outline-none focus:ring-2 focus:ring-lucky-400 focus:ring-offset-2 disabled:opacity-25 ${
                    disabled && 'opacity-25'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
