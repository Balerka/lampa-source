import AppLogoIcon from '@/components/app-logo-icon';
import { usePage } from '@inertiajs/react';

export default function AppLogo() {
    const { props } = usePage<{ name?: string }>();
    const projectName = props.name || 'Cub';

    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-2xl border border-white/10 bg-[linear-gradient(160deg,_#07111c_0%,_#12263f_45%,_#e2b14a_170%)] shadow-[0_12px_30px_rgba(0,0,0,0.25)]">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold text-white">
                    {projectName}
                </span>
                <span className="truncate text-[11px] uppercase tracking-[0.24em] text-[#89a2bc]">
                    Lampa Backend
                </span>
            </div>
        </>
    );
}
