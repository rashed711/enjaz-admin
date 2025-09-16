// Use a simple flag to ensure we only load scripts once.
let scriptsLoaded = false;

function loadScript(src: string): Promise<void> {
    return new Promise((resolve, reject) => {
        // Check if the script is already on the page
        if (document.querySelector(`script[src="${src}"]`)) {
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
        document.head.appendChild(script);
    });
}

export async function ensurePdfLibsLoaded() {
    if (scriptsLoaded) {
        return;
    }

    try {
        // jspdf is loaded via importmap, so it's a module.
        // We need to make it global for the plugins (autotable, fonts).
        if (!(window as any).jspdf || !(window as any).jsPDF) {
            const jspdfModule = await import('jspdf');
            (window as any).jspdf = jspdfModule;
            (window as any).jsPDF = jspdfModule.jsPDF;
        }

        // Load plugins sequentially as they depend on the global jspdf object.
        await loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf-autotable.umd.min.js');
        await loadScript('/Cairo-Regular.js');
        await loadScript('/Cairo-Bold.js');

        scriptsLoaded = true;
    } catch (error) {
        console.error("Failed to load PDF generation libraries:", error);
        // Re-throw the error so the calling function knows something went wrong.
        throw new Error("Could not load PDF libraries. Please check the network connection and console for errors.");
    }
}