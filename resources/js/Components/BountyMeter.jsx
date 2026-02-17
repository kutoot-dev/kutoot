export default function BountyMeter({ percentage = 0, size = 'md' }) {
    const clamped = Math.min(Math.max(percentage, 0), 100);

    const sizeClasses = {
        sm: { bar: 'h-2', text: 'text-xs', wrapper: '' },
        md: { bar: 'h-4', text: 'text-xs', wrapper: '' },
        lg: { bar: 'h-5', text: 'text-sm', wrapper: '' },
    };

    const s = sizeClasses[size] || sizeClasses.md;

    const meterColor =
        clamped >= 80
            ? 'from-green-400 to-emerald-500'
            : clamped >= 50
              ? 'from-lucky-400 to-lucky-600'
              : clamped >= 25
                ? 'from-yellow-400 to-amber-500'
                : 'from-orange-400 to-red-400';

    return (
        <div className={s.wrapper}>
            <div className="flex items-center justify-between mb-1">
                <span className={`font-bold text-lucky-700 ${s.text}`}>🔥 Bounty</span>
                <span className={`font-bold text-lucky-600 ${s.text}`}>{clamped}%</span>
            </div>
            <div className={`overflow-hidden ${s.bar} rounded-full bg-lucky-100 border border-lucky-200`}>
                <div
                    style={{ width: `${clamped}%` }}
                    className={`h-full rounded-full bg-gradient-to-r ${meterColor} transition-all duration-700 ease-out`}
                />
            </div>
        </div>
    );
}
