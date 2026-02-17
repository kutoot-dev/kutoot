export default function Checkbox({ className = '', ...props }) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'rounded border-lucky-300 text-lucky-600 shadow-sm focus:ring-lucky-500 ' +
                className
            }
        />
    );
}
