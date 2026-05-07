/**
 * Yardim sayfasi URL <-> CMS slug donusumleri.
 *
 * URL convention:
 *   /yardim                           -> slug "yardim"
 *   /yardim/baslarken                 -> slug "yardim-baslarken"
 *   /yardim/satici-rehberi/x          -> slug "yardim-satici-x"
 *   /yardim/alici-rehberi/y           -> slug "yardim-alici-y"
 *
 * URL'lerin "satici-rehberi/" / "alici-rehberi/" kismi UX icin korunur;
 * DB slug ise duz "yardim-satici-x" formundadir.
 */

export const HELP_GROUP = 'yardim';

type SectionPrefix = 'baslarken' | 'satici' | 'alici';

interface HelpSectionDef {
    title: string;
    icon: 'book' | 'store' | 'cart';
    prefix: SectionPrefix;
    /** UX URL'inde kullanilan dizin adi; null ise dizin yok (baslarken duz). */
    urlSegment: string | null;
}

export const HELP_SECTIONS: HelpSectionDef[] = [
    { title: 'Başlarken', icon: 'book', prefix: 'baslarken', urlSegment: null },
    { title: 'Satıcı Rehberi', icon: 'store', prefix: 'satici', urlSegment: 'satici-rehberi' },
    { title: 'Alıcı Rehberi', icon: 'cart', prefix: 'alici', urlSegment: 'alici-rehberi' },
];

/**
 * URL pathname'i CMS DB slug'ina cevirir.
 * "/yardim"                          -> "yardim"
 * "/yardim/baslarken"                -> "yardim-baslarken"
 * "/yardim/satici-rehberi/urun-ekleme" -> "yardim-satici-urun-ekleme"
 * "/yardim/alici-rehberi/sepet-odeme"  -> "yardim-alici-sepet-odeme"
 */
export function helpDbSlugFromPath(pathname: string): string {
    const clean = pathname.replace(/\/+$/, '');
    if (clean === '/yardim' || clean === '') return HELP_GROUP;

    const after = clean.replace(/^\/yardim\/?/, '');
    if (!after) return HELP_GROUP;

    const parts = after.split('/').filter(Boolean);

    // Section dizinleri
    for (const section of HELP_SECTIONS) {
        if (section.urlSegment && parts[0] === section.urlSegment && parts[1]) {
            return `${HELP_GROUP}-${section.prefix}-${parts[1]}`;
        }
    }

    // Baslarken (urlSegment yok) - tek parca: "/yardim/baslarken"
    if (parts.length === 1) {
        return `${HELP_GROUP}-${parts[0]}`;
    }

    // Bilinmeyen format: parts'lari "-" ile birlestir
    return `${HELP_GROUP}-${parts.join('-')}`;
}

/**
 * CMS DB slug'inden UX URL'i uretir.
 * "yardim"                       -> "/yardim"
 * "yardim-baslarken"             -> "/yardim/baslarken"
 * "yardim-satici-urun-ekleme"    -> "/yardim/satici-rehberi/urun-ekleme"
 * "yardim-alici-sepet-odeme"     -> "/yardim/alici-rehberi/sepet-odeme"
 */
export function helpUrlFromDbSlug(slug: string): string {
    if (slug === HELP_GROUP) return '/yardim';

    if (!slug.startsWith(`${HELP_GROUP}-`)) return '/yardim';

    const rest = slug.slice(HELP_GROUP.length + 1); // "satici-urun-ekleme" / "baslarken"

    for (const section of HELP_SECTIONS) {
        if (section.urlSegment && rest.startsWith(`${section.prefix}-`)) {
            const tail = rest.slice(section.prefix.length + 1);
            return `/yardim/${section.urlSegment}/${tail}`;
        }
    }

    // Section disinda kalan slug'lar (baslarken vb.)
    return `/yardim/${rest}`;
}
