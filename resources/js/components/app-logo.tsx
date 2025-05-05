export default function AppLogo() {
    return (
        <>
            <div className="flex items-center justify-center w-8 h-8 overflow-visible">
                <img 
                    src="/logo_no_padding.svg" 
                    alt="Samplepal Leads Logo" 
                    className="w-7 h-7 object-contain" 
                    style={{ transform: 'scale(1.8)' }}
                />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold">Samplepal Leads</span>
            </div>
        </>
    );
}
