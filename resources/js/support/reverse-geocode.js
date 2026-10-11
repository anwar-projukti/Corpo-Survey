/**
 * Turns a captured latitude/longitude into a human-readable place name via
 * OpenStreetMap's Nominatim (free, no API key — the same service already
 * backing the "view on map" links in My Responses). Nominatim's usage
 * policy caps this at ~1 request/second and asks for no more than
 * necessary, so results are cached in-memory per rounded coordinate
 * (~11m precision) for the lifetime of the page — the same spot geocoded
 * twice (e.g. reopening a response) never re-fetches.
 *
 * Best-effort only: a slow, blocked, or failed lookup just leaves the
 * caller showing raw coordinates instead, which is why this never throws.
 */
const cache = new Map();

function cacheKey(latitude, longitude) {
    return `${Number(latitude).toFixed(4)},${Number(longitude).toFixed(4)}`;
}

/**
 * A short "neighborhood, city" label built from the most specific parts of
 * Nominatim's address breakdown — the full formatted address is too long
 * for an inline badge.
 */
function shortLabelFrom(address) {
    if (!address) return null;

    const locality = address.suburb || address.neighbourhood || address.village || address.town || address.city_district;
    const city = address.city || address.town || address.state_district || address.state;

    return [locality, city].filter(Boolean).join(', ') || null;
}

export default async function reverseGeocode(latitude, longitude) {
    if (latitude === null || latitude === undefined || longitude === null || longitude === undefined) {
        return null;
    }

    const key = cacheKey(latitude, longitude);
    if (cache.has(key)) {
        return cache.get(key);
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}&zoom=14&addressdetails=1`,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            return null;
        }

        const data = await response.json();
        const label = shortLabelFrom(data.address) || data.display_name?.split(',').slice(0, 2).join(',').trim() || null;

        cache.set(key, label);
        return label;
    } catch {
        return null;
    }
}
