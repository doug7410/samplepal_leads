export default function AppLogo() {
    return (
        <>
            <div className="flex h-8 w-8 items-center justify-center overflow-visible">
                <img src="/logo_no_padding.svg" alt="Samplepal Leads Logo" className="h-7 w-7 object-contain" style={{ transform: 'scale(1.8)' }} />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">Samplepal Leads</span>
            </div>
        </>
    );
}
