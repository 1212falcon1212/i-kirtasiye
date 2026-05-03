'use client';

import { useState, useEffect } from 'react';
import Image from 'next/image';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { UserIntegration, IntegrationCredentials, integrationsApi } from '@/lib/api';
import { Loader2, RefreshCw, Trash2, CheckCircle2, AlertCircle, ChevronDown, Save, Eye, EyeOff, Settings2 } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface IntegrationCardProps {
    id: string;
    name: string;
    description: string;
    logo?: string;
    integration?: UserIntegration;
    onUpdate: () => void;
}

// ERP-specific field configuration
const ERP_CONFIG: Record<string, {
    fields: {
        key: string;
        label: string;
        required?: boolean;
        type?: string;
        placeholder?: string;
    }[];
    description?: string;
}> = {
    entegra: {
        fields: [
            { key: 'api_key', label: 'Kullanıcı Adı / Email', required: true, placeholder: 'ornek@email.com' },
            { key: 'api_secret', label: 'Şifre', required: true, type: 'password', placeholder: '••••••••' },
        ],
    },
    parasut: {
        fields: [
            { key: 'api_key', label: 'Client ID', required: true, placeholder: 'Client ID' },
            { key: 'api_secret', label: 'Client Secret', required: true, type: 'password', placeholder: '••••••••' },
            { key: 'app_id', label: 'Firma No (Company ID)', required: true, placeholder: '123456' },
            { key: 'username', label: 'E-posta', required: true, type: 'email', placeholder: 'ornek@email.com' },
            { key: 'password', label: 'Şifre', required: true, type: 'password', placeholder: '••••••••' },
        ],
    },
    sentos: {
        fields: [
            { key: 'api_key', label: 'API Key', required: true, placeholder: 'API Key' },
            { key: 'api_secret', label: 'API Secret', required: true, type: 'password', placeholder: '••••••••' },
            { key: 'app_id', label: 'Mağaza Adı (Subdomain)', required: true, placeholder: 'mağazaadı' },
        ],
    },
    bizimhesap: {
        fields: [
            { key: 'api_key', label: 'Token', required: true, placeholder: 'Token' },
            { key: 'api_secret', label: 'Key (BZMHB...)', required: true, placeholder: 'BZMHB...' },
        ],
    },
    stockmount: {
        fields: [
            { key: 'username', label: 'Kullanıcı Adı', required: true, placeholder: 'Kullanıcı adı' },
            { key: 'password', label: 'Şifre', required: true, type: 'password', placeholder: '••••••••' },
        ],
    },
    dopigo: {
        fields: [
            { key: 'username', label: 'Kullanıcı Adı', required: true, placeholder: 'Kullanıcı adı' },
            { key: 'password', label: 'Şifre', required: true, type: 'password', placeholder: '••••••••' },
        ],
    },
    kolaysoft: {
        fields: [
            { key: 'username', label: 'Kullanıcı Adı', required: true, placeholder: 'Kullanıcı adı' },
            { key: 'password', label: 'Şifre', required: true, type: 'password', placeholder: '••••••••' },
            { key: 'test_mode', label: 'Test Modu', type: 'switch' },
        ],
    },
};

export function IntegrationCard({ id, name, description, logo, integration, onUpdate }: IntegrationCardProps) {
    const [saving, setSaving] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [testing, setTesting] = useState(false);
    const [expanded, setExpanded] = useState(false);
    const [editMode, setEditMode] = useState(false);
    const [formData, setFormData] = useState<Record<string, string | boolean>>({});

    const isConnected = integration?.is_configured;
    const isActive = integration?.status === 'active';
    const hasError = integration?.status === 'error';
    const isPending = integration?.status === 'pending';
    const credentials = integration?.credentials;

    const config = ERP_CONFIG[id] || {
        fields: [
            { key: 'api_key', label: 'API Key', required: true },
            { key: 'api_secret', label: 'API Secret', required: true, type: 'password' },
        ],
    };

    // Initialize form with empty values
    useEffect(() => {
        const initialData: Record<string, string | boolean> = {};
        config.fields.forEach(field => {
            initialData[field.key] = field.type === 'switch' ? false : '';
        });
        setFormData(initialData);
    }, [id]);

    const handleSave = async () => {
        setSaving(true);
        try {
            const payload: Record<string, any> = { erp_type: id };

            Object.entries(formData).forEach(([key, value]) => {
                if (value !== '' && value !== false) {
                    payload[key] = value;
                }
            });

            await integrationsApi.save(payload as any);
            toast.success(`${name} baglantisi başarıyla kaydedildi`);
            onUpdate();

            // Clear form after save
            const clearedData: Record<string, string | boolean> = {};
            config.fields.forEach(field => {
                clearedData[field.key] = field.type === 'switch' ? false : '';
            });
            setFormData(clearedData);
        } catch (error: any) {
            toast.error(error.message || 'Kaydetme hatası oluştu');
        } finally {
            setSaving(false);
        }
    };

    const handleSync = async () => {
        setSyncing(true);
        try {
            await integrationsApi.sync(id);
            toast.success('Senkronizasyon işlemi başlatıldı.');
        } catch (error: any) {
            toast.error('Senkronizasyon hatası: ' + error.message);
        } finally {
            setSyncing(false);
        }
    };

    const handleDelete = async () => {
        if (!confirm('Bu entegrasyonu kaldırmak istediğinize emin misiniz?')) return;
        setDeleting(true);
        try {
            await integrationsApi.delete(id);
            toast.success('Entegrasyon kaldırıldı.');
            onUpdate();
        } catch (error: any) {
            toast.error('Kaldırma hatası: ' + error.message);
        } finally {
            setDeleting(false);
        }
    };

    const handleTestConnection = async () => {
        setTesting(true);
        try {
            const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/settings/integrations/${id}/test`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            if (data.status === 'success') {
                toast.success('Bağlantı başarılı!');
                onUpdate();
            } else {
                toast.error(data.message || 'Baglanti başarısız');
            }
        } catch (error: any) {
            toast.error('Bağlantı testi hatası: ' + error.message);
        } finally {
            setTesting(false);
        }
    };

    const updateField = (key: string, value: string | boolean) => {
        setFormData(prev => ({ ...prev, [key]: value }));
    };

    // Get display value for a credential field
    const getCredentialDisplay = (key: string): string | null => {
        if (!credentials) return null;
        const value = credentials[key as keyof IntegrationCredentials];
        if (typeof value === 'boolean') return value ? 'Evet' : 'Hayır';
        return value as string | null;
    };

    return (
        <div className="border border-slate-200 rounded-xl bg-white overflow-hidden">
            {/* Header */}
            <button
                onClick={() => setExpanded(!expanded)}
                className="w-full flex items-center justify-between p-4 hover:bg-slate-50 transition-colors"
            >
                <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                        {logo ? (
                            <Image src={logo} alt={name} width={40} height={40} className="object-contain" />
                        ) : (
                            <span className="text-lg font-bold text-slate-400">{name.charAt(0)}</span>
                        )}
                    </div>
                    <div className="text-left">
                        <h3 className="font-semibold text-slate-900">{name}</h3>
                        <p className="text-sm text-slate-500">{description}</p>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    {isConnected && (
                        <Badge
                            variant="outline"
                            className={cn(
                                "gap-1",
                                isActive && "bg-[#ffedd5] text-[#ea580c] border-[#ffedd5]",
                                hasError && "bg-red-50 text-red-700 border-red-200",
                                !isActive && !hasError && "bg-amber-50 text-amber-700 border-amber-200"
                            )}
                        >
                            <CheckCircle2 className="w-3 h-3" />
                            {isActive ? 'Aktif' : hasError ? 'Hata' : 'Pasif'}
                        </Badge>
                    )}
                    <ChevronDown className={cn("w-5 h-5 text-slate-400 transition-transform duration-200", expanded && "rotate-180")} />
                </div>
            </button>

            {/* Expanded Content */}
            {expanded && (
                <div className="border-t border-slate-200 p-4 bg-slate-50">
                    {isConnected && !editMode ? (
                        /* Connected State - Show status, credentials and actions */
                        <div className="space-y-4">
                            {/* Status Row */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div className="bg-white rounded-lg p-3 border border-slate-200">
                                    <span className="text-slate-500 block mb-1">Durum</span>
                                    <span className={cn(
                                        "font-medium",
                                        isActive && "text-[#ea580c]",
                                        hasError && "text-red-600",
                                        isPending && "text-amber-600",
                                        !isActive && !hasError && !isPending && "text-slate-600"
                                    )}>
                                        {isActive ? 'Aktif' : hasError ? 'Hata' : isPending ? 'Beklemede' : 'Pasif'}
                                    </span>
                                </div>
                                <div className="bg-white rounded-lg p-3 border border-slate-200">
                                    <span className="text-slate-500 block mb-1">Son Senkronizasyon</span>
                                    <span className="font-medium text-slate-900">
                                        {integration?.last_sync_at
                                            ? new Date(integration.last_sync_at).toLocaleString('tr-TR')
                                            : '-'
                                        }
                                    </span>
                                </div>
                            </div>

                            {/* Saved Credentials */}
                            {credentials && (
                                <div className="bg-white rounded-lg p-4 border border-slate-200">
                                    <h4 className="text-sm font-medium text-slate-700 mb-3 flex items-center gap-2">
                                        <Settings2 className="w-4 h-4" />
                                        Kayıtlı Bilgiler
                                    </h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                        {config.fields.map((field) => {
                                            const value = getCredentialDisplay(field.key);
                                            if (!value) return null;
                                            return (
                                                <div key={field.key} className={field.type === 'switch' ? 'sm:col-span-2' : ''}>
                                                    <span className="text-slate-500 block text-xs mb-0.5">{field.label}</span>
                                                    <span className="font-mono text-slate-900">
                                                        {field.type === 'switch'
                                                            ? (credentials?.test_mode ? 'Açık' : 'Kapalı')
                                                            : value
                                                        }
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}

                            {integration?.error_message && (
                                <div className="bg-red-50 text-red-700 p-3 rounded-lg text-sm flex gap-2 items-start border border-red-200">
                                    <AlertCircle className="w-4 h-4 mt-0.5 flex-shrink-0" />
                                    <span>{integration.error_message}</span>
                                </div>
                            )}

                            <div className="flex flex-wrap gap-2 pt-2">
                                <Button variant="outline" size="sm" onClick={handleTestConnection} disabled={testing}>
                                    {testing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle2 className="mr-2 h-4 w-4" />}
                                    Bağlantı Test Et
                                </Button>
                                <Button variant="outline" size="sm" onClick={handleSync} disabled={syncing}>
                                    {syncing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
                                    Senkronize Et
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => setEditMode(true)}>
                                    <Settings2 className="mr-2 h-4 w-4" />
                                    Düzenle
                                </Button>
                                <Button variant="destructive" size="sm" onClick={handleDelete} disabled={deleting}>
                                    {deleting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Kaldır
                                </Button>
                            </div>
                        </div>
                    ) : (
                        /* Not Connected State OR Edit Mode - Show inline form */
                        <div className="space-y-4">
                            {editMode && (
                                <div className="bg-amber-50 text-amber-700 p-3 rounded-lg text-sm border border-amber-200">
                                    Bilgileri güncellemek için yeni değerleri girin. Boş bırakılan alanlar değişmez.
                                </div>
                            )}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {config.fields.map((field) => (
                                    <div key={field.key} className={field.type === 'switch' ? 'sm:col-span-2' : ''}>
                                        {field.type === 'switch' ? (
                                            <div className="flex items-center justify-between bg-white rounded-lg p-3 border border-slate-200">
                                                <Label htmlFor={`${id}-${field.key}`} className="text-sm font-medium text-slate-700">
                                                    {field.label}
                                                </Label>
                                                <Switch
                                                    id={`${id}-${field.key}`}
                                                    checked={formData[field.key] as boolean}
                                                    onCheckedChange={(checked) => updateField(field.key, checked)}
                                                />
                                            </div>
                                        ) : (
                                            <div className="space-y-1.5">
                                                <Label htmlFor={`${id}-${field.key}`} className="text-sm font-medium text-slate-700">
                                                    {field.label} {field.required && !editMode && <span className="text-red-500">*</span>}
                                                </Label>
                                                <Input
                                                    id={`${id}-${field.key}`}
                                                    type={field.type || 'text'}
                                                    value={formData[field.key] as string || ''}
                                                    onChange={(e) => updateField(field.key, e.target.value)}
                                                    placeholder={editMode ? `Mevcut: ${getCredentialDisplay(field.key) || '-'}` : (field.placeholder || field.label)}
                                                    className="bg-white"
                                                />
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>

                            <div className="flex justify-end gap-2 pt-2">
                                {editMode && (
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setEditMode(false);
                                            // Reset form
                                            const clearedData: Record<string, string | boolean> = {};
                                            config.fields.forEach(field => {
                                                clearedData[field.key] = field.type === 'switch' ? false : '';
                                            });
                                            setFormData(clearedData);
                                        }}
                                    >
                                        İptal
                                    </Button>
                                )}
                                <Button
                                    onClick={async () => {
                                        await handleSave();
                                        setEditMode(false);
                                    }}
                                    disabled={saving}
                                    className="bg-[#1e3a8a] hover:bg-[#1e40af]"
                                >
                                    {saving ? (
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    ) : (
                                        <Save className="mr-2 h-4 w-4" />
                                    )}
                                    {editMode ? 'Güncelle' : 'Kaydet ve Baglan'}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
