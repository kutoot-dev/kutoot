import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gradient-to-br from-lucky-50 via-white to-ticket-50 confetti-bg pt-6 sm:justify-center sm:pt-0">
            {/* Floating decorations */}
            <div className="absolute top-10 right-20 w-12 h-12 bg-lucky-200 rounded-full opacity-20 animate-float" />
            <div className="absolute bottom-20 left-20 w-8 h-8 bg-ticket-200 rounded-full opacity-20 animate-float" style={{ animationDelay: '1s' }} />

            <div className="relative z-10">
                <Link href="/">
                    <ApplicationLogo />
                </Link>
            </div>

            <div className="relative z-10 mt-6 w-full overflow-hidden bg-lucky-50/95 backdrop-blur-sm px-8 py-6 shadow-xl border-2 border-dashed border-lucky-200 sm:max-w-md sm:rounded-2xl">
                {/* Ticket notches */}
                <div className="absolute -left-3 top-1/2 w-6 h-6 bg-gradient-to-br from-lucky-50 to-ticket-50 rounded-full" />
                <div className="absolute -right-3 top-1/2 w-6 h-6 bg-gradient-to-br from-lucky-50 to-ticket-50 rounded-full" />
                {children}
            </div>

            <p className="mt-6 text-sm text-gray-400 relative z-10">🎟️ Your luck starts here!</p>
        </div>
    );
}
