'use client';

import { useParams } from 'next/navigation';
import { HelpPageView } from '../HelpPageView';
import { helpDbSlugFromPath } from '../help-routes';

export default function YardimDynamicPage() {
    const params = useParams();
    const raw = params?.slug;
    const segments: string[] = Array.isArray(raw) ? (raw as string[]) : raw ? [raw as string] : [];

    const pathname = `/yardim/${segments.join('/')}`;
    const dbSlug = helpDbSlugFromPath(pathname);

    return <HelpPageView slug={dbSlug} />;
}
