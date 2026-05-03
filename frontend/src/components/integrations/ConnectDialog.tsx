'use client';

import { useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { integrationsApi } from '@/lib/api';
import { toast } from 'sonner';
import { Loader2 } from 'lucide-react';

interface ConnectDialogProps {
    erpType: string;
    erpName: string;
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    onSuccess: () => void;
}

// ERP-specific configuration
const ERP_CONFIG: Record<string, {
    fields: {
        api_key?: { label: string; required?: boolean; type?: string };
        api_secret?: { label: string; required?: boolean; type?: string };
        app_id?: { label: string; required?: boolean };
        username?: { label: string; required?: boolean; type?: string };
        password?: { label: string; required?: boolean; type?: string };
        test_mode?: { label: string };
    };
    description?: string;
}> = {
    entegra: {
        fields: {
            api_key: { label: 'Kullanici Adi / Email', required: true },
            api_secret: { label: 'Sifre', required: true, type: 'password' },
        },
        description: 'Entegra panelindeki giris bilgilerinizi girin.',
    },
    parasut: {
        fields: {
            api_key: { label: 'Client ID', required: true },
            api_secret: { label: 'Client Secret', required: true, type: 'password' },
            app_id: { label: 'Firma No (Company ID)', required: true },
            username: { label: 'E-posta', required: true, type: 'email' },
            password: { label: 'Sifre', required: true, type: 'password' },
        },
        description: 'Parasut API ayarlarindan bilgileri alin.',
    },
    sentos: {
        fields: {
            api_key: { label: 'API Key', required: true },
            api_secret: { label: 'API Secret', required: true, type: 'password' },
            app_id: { label: 'Magaza Adi (Subdomain)', required: true },
        },
        description: 'Sentos panelinizden API bilgilerini alin.',
    },
    bizimhesap: {
        fields: {
            api_key: { label: 'Token', required: true },
            api_secret: { label: 'Key (BZMHB...)', required: true },
        },
        description: 'BizimHesap API bilgilerinizi girin.',
    },
    stockmount: {
        fields: {
            username: { label: 'Kullanici Adi', required: true },
            password: { label: 'Sifre', required: true, type: 'password' },
        },
        description: 'StockMount giris bilgilerinizi girin. Alternatif olarak ApiKey/ApiPassword da kullanabilirsiniz.',
    },
    dopigo: {
        fields: {
            username: { label: 'Kullanici Adi', required: true },
            password: { label: 'Sifre', required: true, type: 'password' },
        },
        description: 'Dopigo panel giris bilgilerinizi girin.',
    },
    kolaysoft: {
        fields: {
            username: { label: 'Kullanici Adi', required: true },
            password: { label: 'Sifre', required: true, type: 'password' },
            test_mode: { label: 'Test Modu' },
        },
        description: 'KolaySoft E-Fatura entegrasyonu. Test modunda canli fatura kesilmez.',
    },
};

export function ConnectDialog({ erpType, erpName, isOpen, onOpenChange, onSuccess }: ConnectDialogProps) {
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState<Record<string, string | boolean>>({
        api_key: '',
        api_secret: '',
        app_id: '',
        username: '',
        password: '',
        test_mode: false,
    });

    const config = ERP_CONFIG[erpType] || {
        fields: {
            api_key: { label: 'API Key', required: true },
            api_secret: { label: 'API Secret', required: true, type: 'password' },
        },
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        try {
            const payload: Record<string, any> = {
                erp_type: erpType,
            };

            // Add only non-empty fields
            Object.entries(formData).forEach(([key, value]) => {
                if (value !== '' && value !== false) {
                    payload[key] = value;
                }
            });

            await integrationsApi.save(payload as any);

            toast.success(`${erpName} baglantisi basariyla kuruldu`);
            onSuccess();
            onOpenChange(false);

            // Reset form
            setFormData({
                api_key: '',
                api_secret: '',
                app_id: '',
                username: '',
                password: '',
                test_mode: false,
            });
        } catch (error: any) {
            toast.error(error.message || 'Baglanti hatasi olustu');
        } finally {
            setLoading(false);
        }
    };

    const renderField = (fieldKey: string, fieldConfig: { label: string; required?: boolean; type?: string }) => {
        if (fieldKey === 'test_mode') {
            return (
                <div key={fieldKey} className="flex items-center justify-between py-2">
                    <Label htmlFor={fieldKey}>{fieldConfig.label}</Label>
                    <Switch
                        id={fieldKey}
                        checked={formData[fieldKey] as boolean}
                        onCheckedChange={(checked) => setFormData({ ...formData, [fieldKey]: checked })}
                    />
                </div>
            );
        }

        return (
            <div key={fieldKey} className="space-y-2">
                <Label htmlFor={fieldKey}>{fieldConfig.label}</Label>
                <Input
                    id={fieldKey}
                    type={fieldConfig.type || 'text'}
                    value={formData[fieldKey] as string}
                    onChange={(e) => setFormData({ ...formData, [fieldKey]: e.target.value })}
                    required={fieldConfig.required}
                    placeholder={fieldConfig.label}
                />
            </div>
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[450px]">
                <DialogHeader>
                    <DialogTitle>{erpName} Entegrasyonu</DialogTitle>
                    <DialogDescription>
                        {config.description || `${erpName} entegrasyon bilgilerini giriniz.`}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4 py-4">
                    {Object.entries(config.fields).map(([key, fieldConfig]) =>
                        renderField(key, fieldConfig)
                    )}

                    <DialogFooter className="pt-4">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Iptal
                        </Button>
                        <Button type="submit" disabled={loading} className="bg-[#1e3a8a] hover:bg-[#1e40af]">
                            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Baglan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
