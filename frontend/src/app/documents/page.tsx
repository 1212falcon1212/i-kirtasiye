"use client";

import { useState, useEffect, useRef } from "react";
import { useRouter } from "next/navigation";
import { documentsApi, contractsApi, SellerDocument } from "@/lib/api";
import { useAuth } from "@/contexts/AuthContext";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Upload,
    Download,
    FileText,
    CheckCircle2,
    XCircle,
    Clock,
    Trash2,
    AlertTriangle,
    ExternalLink
} from "lucide-react";
import { toast } from "sonner";

const RETAILER_DOCUMENT_TYPES = [
    { value: "vergi_levhasi", label: "Vergi Levhası", required: true },
    { value: "kimlik", label: "Kimlik Fotokopisi", required: true },
    { value: "imza_sirkusu", label: "İmza Sirküleri", required: false },
];

const SELLER_DOCUMENT_TYPES = [
    { value: "vergi_levhasi", label: "Vergi Levhası", required: true },
    { value: "kimlik", label: "Kimlik Fotokopisi", required: true },
    { value: "imza_sirkusu", label: "İmza Sirküleri", required: false },
];

const STATUS_CONFIG = {
    pending: { color: "bg-yellow-100 text-yellow-800", icon: Clock, label: "Bekliyor" },
    approved: { color: "bg-[#fbeede] text-[#934f12]", icon: CheckCircle2, label: "Onaylandı" },
    rejected: { color: "bg-red-100 text-red-800", icon: XCircle, label: "Reddedildi" },
};

export default function DocumentsPage() {
    const { user, isLoading: authLoading } = useAuth();
    const router = useRouter();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const contractFileInputRef = useRef<HTMLInputElement>(null);

    const [documents, setDocuments] = useState<SellerDocument[]>([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [contractUploading, setContractUploading] = useState(false);
    const [selectedType, setSelectedType] = useState("");
    const [allApproved, setAllApproved] = useState(false);
    const [missingTypes, setMissingTypes] = useState<string[]>([]);

    const isSeller = user?.role === 'seller';
    const DOCUMENT_TYPES = isSeller ? SELLER_DOCUMENT_TYPES : RETAILER_DOCUMENT_TYPES;

    useEffect(() => {
        if (!authLoading && !user) {
            router.push("/login");
            return;
        }

        if (user) {
            loadDocuments();
        }
    }, [user, authLoading]);

    const loadDocuments = async () => {
        try {
            setLoading(true);
            const response = await documentsApi.getAll();
            if (response.data) {
                setDocuments(response.data.documents);
                setAllApproved(response.data.all_approved);
                setMissingTypes(response.data.missing_types);
            }
        } catch (error) {
            console.error("Failed to load documents:", error);
            toast.error("Belgeler yüklenirken hata oluştu");
        } finally {
            setLoading(false);
        }
    };

    const handleFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file || !selectedType) return;

        // Validate file
        const validTypes = ["application/pdf", "image/jpeg", "image/png", "image/jpg"];
        if (!validTypes.includes(file.type)) {
            toast.error("Sadece PDF, JPG ve PNG dosyaları yüklenebilir");
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toast.error("Dosya boyutu 10MB'dan küçük olmalıdır");
            return;
        }

        try {
            setUploading(true);
            const response = await documentsApi.upload(selectedType, file);
            if (response.data) {
                toast.success("Belge başarıyla yüklendi");
                loadDocuments();
                setSelectedType("");
            } else {
                toast.error(response.error || "Belge yüklenirken hata oluştu");
            }
        } catch (error) {
            toast.error("Belge yüklenirken hata oluştu");
        } finally {
            setUploading(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = "";
            }
        }
    };

    const handleDelete = async (id: number) => {
        try {
            const response = await documentsApi.delete(id);
            if (response.error) {
                toast.error(response.error);
            } else {
                toast.success("Belge silindi");
                loadDocuments();
            }
        } catch (error) {
            toast.error("Belge silinirken hata oluştu");
        }
    };

    const getDocumentByType = (type: string) => {
        return documents.find((d) => d.type === type);
    };

    const contractDoc = documents.find((d) => d.type === "sozlesme");

    const handleDownloadContract = async () => {
        try {
            const response = await contractsApi.downloadRegistration();
            if (response.blob) {
                const url = window.URL.createObjectURL(response.blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "uyelik-sozlesmesi.pdf";
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            } else {
                toast.error(response.error || "Sözleşme indirilemedi");
            }
        } catch (error) {
            toast.error("Sözleşme indirilemedi");
        }
    };

    const handleContractFileSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const validTypes = ["application/pdf", "image/jpeg", "image/png", "image/jpg"];
        if (!validTypes.includes(file.type)) {
            toast.error("Sadece PDF, JPG ve PNG dosyaları yüklenebilir");
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toast.error("Dosya boyutu 10MB'dan küçük olmalıdır");
            return;
        }

        try {
            setContractUploading(true);
            const response = await contractsApi.uploadSigned(file);
            if (response.data?.success) {
                toast.success(response.data.message || "Sözleşme başarıyla yüklendi");
                loadDocuments();
            } else {
                toast.error("Sözleşme yüklenirken hata oluştu");
            }
        } catch (error) {
            toast.error("Sözleşme yüklenirken hata oluştu");
        } finally {
            setContractUploading(false);
            if (contractFileInputRef.current) {
                contractFileInputRef.current.value = "";
            }
        }
    };

    if (authLoading || loading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#b8651a]"></div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <div className="container mx-auto px-4 max-w-4xl">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900">Evrak Yönetimi</h1>
                    <p className="text-gray-500 mt-2">
                        Platformu kullanabilmek için gerekli evrakları yükleyin ve onay sürecini takip edin.
                    </p>
                </div>

                {/* Status Alert */}
                {allApproved ? (
                    <div className="mb-6 bg-[#fbeede] border border-[#fbeede] rounded-lg p-4 flex items-center gap-3">
                        <CheckCircle2 className="h-6 w-6 text-[#b8651a]" />
                        <div>
                            <h3 className="font-medium text-[#934f12]">Evraklarınız Onaylandı</h3>
                            <p className="text-sm text-[#934f12]">Tüm gerekli evraklarınız onaylanmış. Platforma tam erişiminiz var.</p>
                        </div>
                        <Button
                            className="ml-auto"
                            onClick={() => router.push("/market/hesabim")}
                        >
                            Hesabıma Git
                        </Button>
                    </div>
                ) : (
                    <div className="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
                        <AlertTriangle className="h-6 w-6 text-amber-600 mt-0.5" />
                        <div>
                            <h3 className="font-medium text-amber-800">Evrak Onayı Gerekli</h3>
                            <p className="text-sm text-amber-700">
                                Platformu kullanabilmek için gerekli evrakları yükleyin ve onay bekleyin.
                                {missingTypes.length > 0 && (
                                    <span className="block mt-1">
                                        Eksik belgeler: {missingTypes.map((t) =>
                                            DOCUMENT_TYPES.find((d) => d.value === t)?.label
                                        ).join(", ")}
                                    </span>
                                )}
                            </p>
                        </div>
                    </div>
                )}

                {/* Registration Contract Section */}
                <Card className="mb-8 border-[#fbeede] bg-[#fbeede]/30">
                    <CardHeader>
                        <div className="flex items-center gap-3">
                            <div className="p-2 rounded-lg bg-[#fbeede]">
                                <FileText className="h-5 w-5 text-[#b8651a]" />
                            </div>
                            <div>
                                <CardTitle>Üyelik Sözleşmesi</CardTitle>
                                <CardDescription>
                                    Sözleşmeyi indirip imzaladıktan sonra taratarak veya fotoğrafını çekerek yükleyiniz.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col sm:flex-row gap-3">
                            <Button
                                variant="outline"
                                className="gap-2 border-[#fbeede] text-[#b8651a] hover:bg-[#934f12]"
                                onClick={handleDownloadContract}
                            >
                                <Download className="h-4 w-4" />
                                Sözleşmeyi İndir
                            </Button>

                            <input
                                ref={contractFileInputRef}
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={handleContractFileSelect}
                                className="hidden"
                            />

                            <Button
                                className="gap-2"
                                onClick={() => contractFileInputRef.current?.click()}
                                disabled={contractUploading}
                            >
                                <Upload className="h-4 w-4" />
                                {contractUploading ? "Yükleniyor..." : contractDoc ? "Yeniden Yükle" : "İmzalı Sözleşmeyi Yükle"}
                            </Button>
                        </div>

                        {contractDoc && (
                            <div className={`mt-4 p-4 rounded-lg border ${
                                contractDoc.status === "approved"
                                    ? "bg-[#fbeede] border-[#fbeede]"
                                    : contractDoc.status === "rejected"
                                    ? "bg-red-50 border-red-200"
                                    : "bg-white border-gray-200"
                            }`}>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className={`p-2 rounded-lg ${
                                            contractDoc.status === "approved"
                                                ? "bg-[#fbeede]"
                                                : contractDoc.status === "rejected"
                                                ? "bg-red-100"
                                                : "bg-[#fbeede]"
                                        }`}>
                                            <FileText className={`h-5 w-5 ${
                                                contractDoc.status === "approved"
                                                    ? "text-[#b8651a]"
                                                    : contractDoc.status === "rejected"
                                                    ? "text-red-600"
                                                    : "text-[#b8651a]"
                                            }`} />
                                        </div>
                                        <div>
                                            <h4 className="font-medium text-gray-900">
                                                {contractDoc.original_name || "İmzalı Sözleşme"}
                                            </h4>
                                            <p className="text-sm text-gray-500">
                                                {contractDoc.created_at
                                                    ? new Date(contractDoc.created_at).toLocaleDateString("tr-TR", {
                                                        day: "numeric",
                                                        month: "long",
                                                        year: "numeric",
                                                        hour: "2-digit",
                                                        minute: "2-digit",
                                                    })
                                                    : ""}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Badge className={STATUS_CONFIG[contractDoc.status].color}>
                                            {(() => {
                                                const StatusIcon = STATUS_CONFIG[contractDoc.status].icon;
                                                return <StatusIcon className="h-3 w-3 mr-1" />;
                                            })()}
                                            {STATUS_CONFIG[contractDoc.status].label}
                                        </Badge>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => window.open(contractDoc.file_url, "_blank")}
                                            title="Görüntüle"
                                        >
                                            <ExternalLink className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>

                                {contractDoc.status === "rejected" && contractDoc.rejection_reason && (
                                    <div className="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                                        <p className="text-sm text-red-700">
                                            <strong>Ret Sebebi:</strong> {contractDoc.rejection_reason}
                                        </p>
                                    </div>
                                )}

                                {contractDoc.status === "pending" && (
                                    <p className="mt-2 text-sm text-amber-600">
                                        Sözleşmeniz inceleme aşamasındadır. Onay sonrası bilgilendirileceksiniz.
                                    </p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Upload Section */}
                <Card className="mb-8">
                    <CardHeader>
                        <CardTitle>Yeni Belge Yükle</CardTitle>
                        <CardDescription>
                            PDF, JPG veya PNG formatında, maksimum 10MB boyutunda dosya yükleyebilirsiniz.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <Select value={selectedType} onValueChange={setSelectedType}>
                                <SelectTrigger className="w-full sm:w-64">
                                    <SelectValue placeholder="Belge tipi seçin" />
                                </SelectTrigger>
                                <SelectContent>
                                    {DOCUMENT_TYPES.map((type) => {
                                        const existing = getDocumentByType(type.value);
                                        const disabled = existing?.status === "approved";
                                        return (
                                            <SelectItem
                                                key={type.value}
                                                value={type.value}
                                                disabled={disabled}
                                            >
                                                {type.label} {type.required && "*"}
                                                {disabled && " (Onaylı)"}
                                            </SelectItem>
                                        );
                                    })}
                                </SelectContent>
                            </Select>

                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={handleFileSelect}
                                className="hidden"
                            />

                            <Button
                                onClick={() => fileInputRef.current?.click()}
                                disabled={!selectedType || uploading}
                                className="gap-2"
                            >
                                <Upload className="h-4 w-4" />
                                {uploading ? "Yükleniyor..." : "Dosya Seç ve Yükle"}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Documents List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Yüklenen Belgeler</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {DOCUMENT_TYPES.map((type) => {
                                const doc = getDocumentByType(type.value);
                                const StatusIcon = doc ? STATUS_CONFIG[doc.status].icon : null;

                                return (
                                    <div
                                        key={type.value}
                                        className={`p-4 rounded-lg border ${doc ? "bg-white" : "bg-gray-50 border-dashed"
                                            }`}
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className={`p-2 rounded-lg ${doc ? "bg-blue-100" : "bg-gray-200"}`}>
                                                    <FileText className={`h-5 w-5 ${doc ? "text-blue-600" : "text-gray-400"}`} />
                                                </div>
                                                <div>
                                                    <h4 className="font-medium text-gray-900">
                                                        {type.label}
                                                        {type.required && <span className="text-red-500 ml-1">*</span>}
                                                    </h4>
                                                    {doc ? (
                                                        <p className="text-sm text-gray-500">{doc.original_name}</p>
                                                    ) : (
                                                        <p className="text-sm text-gray-400">Henüz yüklenmedi</p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                {doc && (
                                                    <>
                                                        <Badge className={STATUS_CONFIG[doc.status].color}>
                                                            {StatusIcon && <StatusIcon className="h-3 w-3 mr-1" />}
                                                            {STATUS_CONFIG[doc.status].label}
                                                        </Badge>

                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => window.open(doc.file_url, "_blank")}
                                                        >
                                                            <ExternalLink className="h-4 w-4" />
                                                        </Button>

                                                        {doc.status !== "approved" && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDelete(doc.id)}
                                                                className="text-red-600 hover:text-red-700 hover:bg-red-50"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                    </>
                                                )}
                                            </div>
                                        </div>

                                        {doc?.status === "rejected" && doc.rejection_reason && (
                                            <div className="mt-3 p-3 bg-red-50 border border-red-100 rounded-lg">
                                                <p className="text-sm text-red-700">
                                                    <strong>Ret Sebebi:</strong> {doc.rejection_reason}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>

                {/* Info */}
                <div className="mt-8 text-center text-sm text-gray-500">
                    <p>
                        Belgeleriniz en kısa sürede incelenecektir.
                        Onay sürecinde herhangi bir sorun yaşarsanız
                        <a href="mailto:destek@i-kirtasiye.com" className="text-[#b8651a] hover:underline ml-1">
                            destek@i-kirtasiye.com
                        </a>
                        {" "}adresinden iletişime geçebilirsiniz.
                    </p>
                </div>
            </div>
        </div>
    );
}
