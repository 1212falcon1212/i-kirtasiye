import { HelpPageView } from './HelpPageView';
import { HELP_GROUP } from './help-routes';

// Yardim icerikleri her sayfa yenilenmesinde guncel cekilsin.
export const dynamic = 'force-dynamic';
export const revalidate = 0;

export const metadata = {
    title: 'Yardım Merkezi - i-kirtasiye',
    description: 'i-kirtasiye kullanım kılavuzları, satıcı ve alıcı rehberleri.',
};

export default function YardimPage() {
    return <HelpPageView slug={HELP_GROUP} />;
}
