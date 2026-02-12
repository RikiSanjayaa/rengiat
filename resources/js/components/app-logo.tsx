import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-9 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon className="size-6 text-current" />
            </div>
            <div className="ml-2 grid flex-1 text-left leading-tight">
                <span className="truncate text-sm font-semibold">Rengiat</span>
                <span className="truncate text-[11px] text-sidebar-foreground/80">
                    Rengiat Ditres PPA & PPO
                </span>
            </div>
        </>
    );
}
