const fontCache = new Map<string, string>();

/**
 * Fetches a font stylesheet, downloads the font files, and embeds them as data URIs.
 * This ensures that when html2canvas renders, the fonts are available synchronously.
 * @param url The URL of the font stylesheet (e.g., from Google Fonts).
 * @returns A string containing the CSS with embedded font data.
 */
export async function getEmbeddedFontCSS(url: string): Promise<string> {
    if (fontCache.has(url)) {
        return fontCache.get(url)!;
    }

    try {
        const cssResponse = await fetch(url);
        if (!cssResponse.ok) {
            throw new Error(`Failed to fetch font CSS: ${cssResponse.statusText}`);
        }
        const css = await cssResponse.text();

        const fontURLs = css.match(/url\((https:\/\/[^)]+)\)/g) || [];
        
        const promises = fontURLs.map(fontUrlWithParens => {
            const urlMatch = fontUrlWithParens.match(/url\(([^)]+)\)/);
            if (!urlMatch) {
                return Promise.resolve({ original: fontUrlWithParens, dataUri: fontUrlWithParens });
            }

            const fontFileUrl = urlMatch[1];
            return fetch(fontFileUrl)
                .then(res => {
                    if (!res.ok) throw new Error(`Failed to fetch font file: ${fontFileUrl}`);
                    return res.blob();
                })
                .then(blob => new Promise<string>((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result as string);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                }))
                .then(dataUri => ({ original: fontUrlWithParens, dataUri: `url(${dataUri})` }));
        });

        const dataUris = await Promise.all(promises);

        let embeddedCss = css;
        for (const { original, dataUri } of dataUris) {
            embeddedCss = embeddedCss.replace(original, dataUri);
        }

        fontCache.set(url, embeddedCss);
        return embeddedCss;

    } catch (error) {
        console.error("Error embedding fonts:", error);
        // Fallback to returning an empty string if something goes wrong
        return '';
    }
}