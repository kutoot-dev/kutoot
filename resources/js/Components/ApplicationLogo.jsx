export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            src="/images/kutoot-name-logo.svg"
            alt="Kutoot"
            className={`h-10 w-auto ${className}`}
            {...props}
        />
    );
}
