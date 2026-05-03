'use client';

import { useState, useEffect, useRef } from 'react';
import { supportTicketsApi, SupportTicket, SupportTicketMessage, SupportTicketAttachment, ordersApi, Order } from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    MessageCircle,
    ChevronLeft,
    Send,
    Clock,
    CheckCircle2,
    AlertCircle,
    XCircle,
    Loader2,
    Package,
    Headphones,
    Paperclip,
    X,
    Image as ImageIcon,
    FileText,
    Download,
} from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';

const STATUS_COLORS: Record<string, string> = {
    open: 'bg-blue-50 text-blue-700',
    in_progress: 'bg-amber-50 text-amber-700',
    waiting: 'bg-[#ffedd5] text-[#ea580c]',
    resolved: 'bg-accent-soft text-accent-hover',
    closed: 'bg-[#faf8f6] text-[#6b7280]',
};

const STATUS_DOT_COLORS: Record<string, string> = {
    open: 'bg-blue-500',
    in_progress: 'bg-amber-500',
    waiting: 'bg-[#1e3a8a]',
    resolved: 'bg-[#1e3a8a]',
    closed: 'bg-[#6b7280]',
};

const CATEGORY_COLORS: Record<string, string> = {
    order: 'bg-purple-50 text-purple-700',
    payment: 'bg-[#ffedd5] text-[#ea580c]',
    shipping: 'bg-sky-50 text-sky-700',
    product: 'bg-amber-50 text-amber-700',
    account: 'bg-accent-soft text-accent-hover',
    other: 'bg-[#faf8f6] text-[#6b7280]',
};

const STATUS_ICONS: Record<string, React.ReactNode> = {
    open: <AlertCircle className="w-3.5 h-3.5" />,
    in_progress: <Clock className="w-3.5 h-3.5" />,
    waiting: <Clock className="w-3.5 h-3.5" />,
    resolved: <CheckCircle2 className="w-3.5 h-3.5" />,
    closed: <XCircle className="w-3.5 h-3.5" />,
};

const CATEGORY_OPTIONS = [
    { value: 'order', label: 'Sipariş' },
    { value: 'payment', label: 'Ödeme' },
    { value: 'shipping', label: 'Kargo' },
    { value: 'product', label: 'Ürün' },
    { value: 'account', label: 'Hesap' },
    { value: 'other', label: 'Diğer' },
];

const ACCEPTED_FILE_TYPES = '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx';
const MAX_FILES = 5;
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatShortDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('tr-TR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function isImageMime(mime: string): boolean {
    return mime.startsWith('image/');
}

// --- File Picker Component ---
function FilePicker({ files, onChange, disabled }: { files: File[]; onChange: (files: File[]) => void; disabled?: boolean }) {
    const inputRef = useRef<HTMLInputElement>(null);

    const handleAdd = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selected = Array.from(e.target.files || []);
        const remaining = MAX_FILES - files.length;
        const toAdd = selected.slice(0, remaining);

        const oversized = toAdd.filter(f => f.size > MAX_FILE_SIZE);
        if (oversized.length > 0) {
            toast.error(`Dosya boyutu en fazla 5MB olabilir: ${oversized.map(f => f.name).join(', ')}`);
        }

        const valid = toAdd.filter(f => f.size <= MAX_FILE_SIZE);
        if (valid.length > 0) {
            onChange([...files, ...valid]);
        }

        if (inputRef.current) inputRef.current.value = '';
    };

    const handleRemove = (index: number) => {
        onChange(files.filter((_, i) => i !== index));
    };

    return (
        <div>
            <input
                ref={inputRef}
                type="file"
                accept={ACCEPTED_FILE_TYPES}
                multiple
                onChange={handleAdd}
                className="hidden"
                disabled={disabled}
            />
            <div className="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={files.length >= MAX_FILES || disabled}
                    onClick={() => inputRef.current?.click()}
                    className="text-xs gap-1.5"
                >
                    <Paperclip className="w-3.5 h-3.5" />
                    Dosya Ekle
                </Button>
                {files.length > 0 && (
                    <span className="text-xs text-[#6b7280]">{files.length}/{MAX_FILES}</span>
                )}
            </div>
            {files.length > 0 && (
                <div className="flex flex-wrap gap-2 mt-2">
                    {files.map((file, i) => (
                        <div key={i} className="flex items-center gap-1.5 bg-[#faf8f6] border border-[#f0eceb] rounded-xl px-2.5 py-1.5 text-xs text-[#1a1a1a]">
                            {file.type.startsWith('image/') ? (
                                <ImageIcon className="w-3.5 h-3.5 text-blue-500 shrink-0" />
                            ) : (
                                <FileText className="w-3.5 h-3.5 text-[#ea580c] shrink-0" />
                            )}
                            <span className="truncate max-w-[120px]">{file.name}</span>
                            <span className="text-[#6b7280]">({formatFileSize(file.size)})</span>
                            {!disabled && (
                                <button type="button" onClick={() => handleRemove(i)} className="text-[#6b7280] hover:text-red-500 ml-0.5">
                                    <X className="w-3.5 h-3.5" />
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

// --- Attachment Display Component ---
function AttachmentList({ attachments, isStaff }: { attachments: SupportTicketAttachment[]; isStaff: boolean }) {
    return (
        <div className="flex flex-wrap gap-1.5 mt-2">
            {attachments.map((att, i) => {
                if (isImageMime(att.mime) && att.url) {
                    return (
                        <a
                            key={i}
                            href={att.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="block rounded-xl overflow-hidden border border-[#f0eceb] hover:opacity-80 transition"
                        >
                            <img
                                src={att.url}
                                alt={att.name}
                                className="w-28 h-28 object-cover"
                            />
                        </a>
                    );
                }
                return (
                    <a
                        key={i}
                        href={att.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`flex items-center gap-1.5 rounded-xl px-2.5 py-1.5 text-xs transition border ${
                            isStaff
                                ? 'bg-white border-[#f0eceb] text-[#1a1a1a] hover:border-[#ffedd5]'
                                : 'bg-[#faf8f6] border-[#f0eceb] text-[#1a1a1a] hover:border-[#ffedd5]'
                        }`}
                    >
                        <FileText className="w-3.5 h-3.5 shrink-0" />
                        <span className="truncate max-w-[100px]">{att.name}</span>
                        <Download className="w-3 h-3 shrink-0" />
                    </a>
                );
            })}
        </div>
    );
}

// --- Ticket List ---
function TicketList({ onSelectTicket }: { onSelectTicket: (id: number) => void }) {
    const [tickets, setTickets] = useState<SupportTicket[]>([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    useEffect(() => {
        loadTickets();
    }, [page]);

    const loadTickets = async () => {
        setLoading(true);
        try {
            const res = await supportTicketsApi.getAll(page, 10);
            if (res.data?.data) {
                setTickets(res.data.data);
                setTotalPages(res.data.pagination?.last_page || 1);
            }
        } catch {
            toast.error('Talepler yüklenirken hata oluştu.');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="space-y-3">
                {[1, 2, 3].map(i => <Skeleton key={i} className="h-24 w-full rounded-xl" />)}
            </div>
        );
    }

    if (tickets.length === 0) {
        return (
            <div className="text-center py-16 bg-[#faf8f6] rounded-2xl border border-[#f0eceb]">
                <Headphones className="w-12 h-12 text-[#6b7280]/40 mx-auto mb-3" />
                <p className="text-sm text-[#6b7280]">Henüz destek talebiniz bulunmuyor.</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {tickets.map(ticket => (
                <button
                    key={ticket.id}
                    onClick={() => onSelectTicket(ticket.id)}
                    className="w-full text-left bg-white border border-[#f0eceb] rounded-2xl p-4 hover:border-[#ffedd5] transition-colors"
                >
                    <div className="flex items-center gap-3">
                        {/* Status dot */}
                        <div className="shrink-0 self-start mt-1.5">
                            <div className={`w-2 h-2 rounded-full ${STATUS_DOT_COLORS[ticket.status] || 'bg-[#6b7280]'}`} />
                        </div>
                        {/* Content */}
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-1">
                                <h3 className="font-medium text-[#1a1a1a] text-sm truncate">{ticket.subject}</h3>
                            </div>
                            <div className="flex items-center gap-1.5 flex-wrap mb-1.5">
                                <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold ${CATEGORY_COLORS[ticket.category] || ''}`}>
                                    {ticket.category_label}
                                </span>
                                <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold flex items-center gap-1 ${STATUS_COLORS[ticket.status] || ''}`}>
                                    {ticket.status_label}
                                </span>
                                {ticket.order && (
                                    <span className="text-[10px] text-[#6b7280] flex items-center gap-1">
                                        <Package className="w-3 h-3" />
                                        #{ticket.order.order_number}
                                    </span>
                                )}
                                <span className="text-[10px] text-[#6b7280]">
                                    {formatShortDate(ticket.created_at)}
                                </span>
                            </div>
                            {ticket.last_message && (
                                <p className="text-xs text-[#6b7280] truncate">
                                    {ticket.last_message.is_staff_reply && (
                                        <span className="text-[#ea580c] font-medium">Destek: </span>
                                    )}
                                    {ticket.last_message.message}
                                </p>
                            )}
                        </div>
                        {/* Chevron */}
                        <ChevronLeft className="w-4 h-4 text-[#6b7280] shrink-0 rotate-180" />
                    </div>
                </button>
            ))}

            {totalPages > 1 && (
                <div className="flex justify-center gap-2 pt-4">
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={page === 1}
                        onClick={() => setPage(p => p - 1)}
                    >
                        Önceki
                    </Button>
                    <span className="flex items-center text-xs text-[#6b7280] px-2">
                        {page} / {totalPages}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        disabled={page === totalPages}
                        onClick={() => setPage(p => p + 1)}
                    >
                        Sonraki
                    </Button>
                </div>
            )}
        </div>
    );
}

// --- Ticket Detail (Chat View) ---
function TicketDetail({ ticketId, onBack }: { ticketId: number; onBack: () => void }) {
    const { user } = useAuth();
    const [ticket, setTicket] = useState<SupportTicket | null>(null);
    const [loading, setLoading] = useState(true);
    const [newMessage, setNewMessage] = useState('');
    const [messageFiles, setMessageFiles] = useState<File[]>([]);
    const [sending, setSending] = useState(false);
    const [closing, setClosing] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        loadTicket();
    }, [ticketId]);

    useEffect(() => {
        const el = messagesEndRef.current;
        if (el?.parentElement) {
            el.parentElement.scrollTop = el.parentElement.scrollHeight;
        }
    }, [ticket?.messages]);

    const loadTicket = async () => {
        setLoading(true);
        try {
            const res = await supportTicketsApi.get(ticketId);
            if (res.data?.data) {
                setTicket(res.data.data);
            }
        } catch {
            toast.error('Talep detayı yüklenemedi.');
        } finally {
            setLoading(false);
        }
    };

    const handleSendMessage = async () => {
        if ((!newMessage.trim() && messageFiles.length === 0) || sending) return;
        setSending(true);
        try {
            const res = await supportTicketsApi.addMessage(
                ticketId,
                newMessage.trim() || (messageFiles.length > 0 ? `${messageFiles.length} dosya gönderildi` : ''),
                messageFiles.length > 0 ? messageFiles : undefined
            );
            if (res.data?.data) {
                setTicket(prev => prev ? {
                    ...prev,
                    status: prev.status === 'waiting' ? 'open' : prev.status,
                    status_label: prev.status === 'waiting' ? 'Açık' : prev.status_label,
                    messages: [...(prev.messages || []), res.data!.data],
                } : prev);
                setNewMessage('');
                setMessageFiles([]);
            }
        } catch {
            toast.error('Mesaj gönderilemedi.');
        } finally {
            setSending(false);
        }
    };

    const handleClose = async () => {
        if (closing) return;
        setClosing(true);
        try {
            await supportTicketsApi.close(ticketId);
            toast.success('Talep kapatıldı.');
            setTicket(prev => prev ? {
                ...prev,
                status: 'closed',
                status_label: 'Kapatıldı',
            } : prev);
        } catch {
            toast.error('Talep kapatılamadı.');
        } finally {
            setClosing(false);
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selected = Array.from(e.target.files || []);
        const remaining = MAX_FILES - messageFiles.length;
        const toAdd = selected.slice(0, remaining);
        const oversized = toAdd.filter(f => f.size > MAX_FILE_SIZE);
        if (oversized.length > 0) {
            toast.error(`Dosya boyutu en fazla 5MB olabilir.`);
        }
        const valid = toAdd.filter(f => f.size <= MAX_FILE_SIZE);
        if (valid.length > 0) {
            setMessageFiles(prev => [...prev, ...valid]);
        }
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    if (loading) {
        return (
            <div className="space-y-3">
                <Skeleton className="h-16 w-full rounded-xl" />
                <Skeleton className="h-64 w-full rounded-xl" />
            </div>
        );
    }

    if (!ticket) {
        return (
            <div className="text-center py-12">
                <p className="text-sm text-[#6b7280]">Talep bulunamadı.</p>
                <Button variant="outline" size="sm" onClick={onBack} className="mt-3 rounded-xl">Geri Dön</Button>
            </div>
        );
    }

    const isClosed = ticket.status === 'closed' || ticket.status === 'resolved';

    return (
        <div className="space-y-4">
            {/* Header */}
            <div className="flex items-center gap-3">
                <Button variant="ghost" size="sm" onClick={onBack} className="shrink-0">
                    <ChevronLeft className="w-4 h-4 mr-1" />
                    Geri
                </Button>
                <div className="flex-1 min-w-0">
                    <h3 className="text-lg font-black text-[#1a1a1a] truncate">{ticket.subject}</h3>
                    <div className="flex items-center gap-2 mt-1">
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold ${CATEGORY_COLORS[ticket.category] || ''}`}>
                            {ticket.category_label}
                        </span>
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-semibold flex items-center gap-1 ${STATUS_COLORS[ticket.status] || ''}`}>
                            {STATUS_ICONS[ticket.status]}
                            {ticket.status_label}
                        </span>
                        <span className="text-xs text-[#6b7280]">{formatDate(ticket.created_at)}</span>
                    </div>
                </div>
                {!isClosed && (
                    <Button variant="outline" size="sm" onClick={handleClose} disabled={closing} className="shrink-0 text-xs">
                        {closing ? <Loader2 className="w-3 h-3 animate-spin mr-1" /> : <XCircle className="w-3 h-3 mr-1" />}
                        Kapat
                    </Button>
                )}
            </div>

            {/* Order Info */}
            {ticket.order && (
                <div className="bg-[#faf8f6] border border-[#f0eceb] rounded-2xl p-3 flex items-center gap-2 text-xs text-[#6b7280]">
                    <Package className="w-4 h-4 text-[#6b7280]" />
                    <span>İlgili Sipariş: <strong className="text-[#1a1a1a]">#{ticket.order.order_number}</strong></span>
                </div>
            )}

            {/* Messages */}
            <div className="min-h-[300px] max-h-[500px] overflow-y-auto">
                {ticket.messages && ticket.messages.length > 0 ? (
                    ticket.messages.map(msg => (
                        <div
                            key={msg.id}
                            className={`py-4 border-b border-[#f0eceb] last:border-0 ${
                                msg.is_staff_reply ? 'bg-[#faf8f6] rounded-xl p-3 -mx-1 my-1' : ''
                            }`}
                        >
                            <div className="flex items-center justify-between mb-1">
                                {msg.is_staff_reply ? (
                                    <div className="flex items-center gap-1.5">
                                        <Headphones className="w-3.5 h-3.5 text-[#ea580c]" />
                                        <span className="text-sm font-bold text-[#1a1a1a]">Destek Ekibi</span>
                                    </div>
                                ) : (
                                    <span className="text-sm font-bold text-[#1a1a1a]">Siz</span>
                                )}
                                <span className="text-xs text-[#6b7280]">{formatDate(msg.created_at)}</span>
                            </div>
                            <p className="text-sm text-[#374151] mt-2 whitespace-pre-wrap break-words">{msg.message}</p>
                            {msg.attachments && msg.attachments.length > 0 && (
                                <AttachmentList attachments={msg.attachments} isStaff={msg.is_staff_reply} />
                            )}
                        </div>
                    ))
                ) : (
                    <p className="text-center text-[#6b7280] text-sm py-8">Henüz mesaj yok.</p>
                )}
                <div ref={messagesEndRef} />
            </div>

            {/* Message Input */}
            {!isClosed ? (
                <div className="space-y-2">
                    {/* Attached files preview */}
                    {messageFiles.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {messageFiles.map((file, i) => (
                                <div key={i} className="flex items-center gap-1.5 bg-[#faf8f6] border border-[#f0eceb] rounded-xl px-2.5 py-1.5 text-xs text-[#1a1a1a]">
                                    {file.type.startsWith('image/') ? (
                                        <ImageIcon className="w-3.5 h-3.5 text-blue-500 shrink-0" />
                                    ) : (
                                        <FileText className="w-3.5 h-3.5 text-[#ea580c] shrink-0" />
                                    )}
                                    <span className="truncate max-w-[100px]">{file.name}</span>
                                    <button type="button" onClick={() => setMessageFiles(prev => prev.filter((_, idx) => idx !== i))} className="text-[#6b7280] hover:text-red-500">
                                        <X className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            ))}
                        </div>
                    )}
                    <div className="flex gap-2">
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept={ACCEPTED_FILE_TYPES}
                            multiple
                            onChange={handleFileSelect}
                            className="hidden"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="shrink-0 h-auto min-h-[60px]"
                            disabled={messageFiles.length >= MAX_FILES || sending}
                            onClick={() => fileInputRef.current?.click()}
                            title="Dosya ekle"
                        >
                            <Paperclip className="w-4 h-4" />
                        </Button>
                        <Textarea
                            value={newMessage}
                            onChange={(e) => setNewMessage(e.target.value)}
                            placeholder="Mesajınızı yazın..."
                            rows={2}
                            className="flex-1 resize-none text-sm"
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    handleSendMessage();
                                }
                            }}
                        />
                        <Button
                            onClick={handleSendMessage}
                            disabled={(!newMessage.trim() && messageFiles.length === 0) || sending}
                            className="shrink-0 bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl h-auto min-h-[60px]"
                        >
                            {sending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="bg-[#faf8f6] border border-[#f0eceb] rounded-2xl p-3 text-center text-xs text-[#6b7280]">
                    Bu talep kapatılmıştır. Yeni bir sorunuz varsa yeni talep oluşturabilirsiniz.
                </div>
            )}
        </div>
    );
}

// --- New Ticket Form ---
function NewTicketForm({ onCreated }: { onCreated: () => void }) {
    const [subject, setSubject] = useState('');
    const [category, setCategory] = useState('other');
    const [description, setDescription] = useState('');
    const [orderId, setOrderId] = useState<number | null>(null);
    const [orders, setOrders] = useState<Order[]>([]);
    const [loadingOrders, setLoadingOrders] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const [files, setFiles] = useState<File[]>([]);

    useEffect(() => {
        loadOrders();
    }, []);

    const loadOrders = async () => {
        try {
            const res = await ordersApi.getAll({ per_page: 50 });
            if (res.data?.orders) {
                setOrders(res.data.orders);
            }
        } catch {
            // non-critical
        } finally {
            setLoadingOrders(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!subject.trim() || !description.trim() || submitting || submitted) return;

        setSubmitting(true);
        try {
            const res = await supportTicketsApi.create(
                {
                    subject: subject.trim(),
                    category,
                    description: description.trim(),
                    order_id: orderId,
                },
                files.length > 0 ? files : undefined
            );
            if (res.data?.success) {
                setSubmitted(true);
                toast.success('Destek talebiniz oluşturuldu.');
                onCreated();
            }
        } catch (err: unknown) {
            const errorMsg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            toast.error(errorMsg || 'Talep oluşturulurken hata oluştu.');
        } finally {
            setSubmitting(false);
        }
    };

    if (submitted) {
        return (
            <div className="text-center py-16 max-w-md mx-auto">
                <div className="w-16 h-16 mx-auto bg-[#ffedd5] rounded-2xl flex items-center justify-center mb-4">
                    <CheckCircle2 className="w-8 h-8 text-[#ea580c]" />
                </div>
                <h3 className="text-lg font-black text-[#1a1a1a] mb-2">Talebiniz Oluşturuldu</h3>
                <p className="text-sm text-[#6b7280] mb-6">
                    Destek ekibimiz en kısa sürede talebinizi inceleyecektir.
                </p>
                <Button
                    onClick={() => {
                        setSubmitted(false);
                        setSubject('');
                        setCategory('other');
                        setDescription('');
                        setOrderId(null);
                        setFiles([]);
                    }}
                    variant="outline"
                    className="mr-3 rounded-xl"
                >
                    Yeni Talep Oluştur
                </Button>
                <Button onClick={onCreated} className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl">
                    Taleplerime Git
                </Button>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4 max-w-2xl">
            <div>
                <Label htmlFor="ticket-subject" className="text-sm font-semibold text-[#1a1a1a]">Konu *</Label>
                <Input
                    id="ticket-subject"
                    value={subject}
                    onChange={e => setSubject(e.target.value)}
                    placeholder="Sorununuzu kısaca özetleyin"
                    maxLength={255}
                    required
                    className="mt-1.5"
                />
            </div>

            <div>
                <Label htmlFor="ticket-category" className="text-sm font-semibold text-[#1a1a1a]">Kategori *</Label>
                <select
                    id="ticket-category"
                    value={category}
                    onChange={e => setCategory(e.target.value)}
                    className="mt-1.5 w-full rounded-xl border border-[#f0eceb] bg-white px-3 py-2 text-sm text-[#1a1a1a] focus:outline-none focus:ring-2 focus:ring-[#ffedd5] focus:border-[#ffedd5]"
                    required
                >
                    {CATEGORY_OPTIONS.map(opt => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
            </div>

            <div>
                <Label htmlFor="ticket-order" className="text-sm font-semibold text-[#1a1a1a]">İlgili Sipariş (Opsiyonel)</Label>
                <select
                    id="ticket-order"
                    value={orderId ?? ''}
                    onChange={e => setOrderId(e.target.value ? Number(e.target.value) : null)}
                    className="mt-1.5 w-full rounded-xl border border-[#f0eceb] bg-white px-3 py-2 text-sm text-[#1a1a1a] focus:outline-none focus:ring-2 focus:ring-[#ffedd5] focus:border-[#ffedd5]"
                    disabled={loadingOrders}
                >
                    <option value="">Sipariş seçin (opsiyonel)</option>
                    {orders.map(order => (
                        <option key={order.id} value={order.id}>
                            #{order.order_number} - {new Date(order.created_at).toLocaleDateString('tr-TR')}
                        </option>
                    ))}
                </select>
            </div>

            <div>
                <Label htmlFor="ticket-description" className="text-sm font-semibold text-[#1a1a1a]">Açıklama *</Label>
                <Textarea
                    id="ticket-description"
                    value={description}
                    onChange={e => setDescription(e.target.value)}
                    placeholder="Sorununuzu detaylı bir şekilde açıklayın..."
                    rows={5}
                    maxLength={5000}
                    required
                    className="mt-1.5"
                />
                <p className="text-xs text-[#6b7280] mt-1">{description.length}/5000</p>
            </div>

            <div>
                <Label className="text-sm font-semibold text-[#1a1a1a]">Dosya Ekle (Opsiyonel)</Label>
                <p className="text-xs text-[#6b7280] mt-0.5 mb-2">
                    Görsel, PDF veya belge ekleyebilirsiniz. Maks. 5 dosya, her biri en fazla 5MB.
                </p>
                <FilePicker files={files} onChange={setFiles} disabled={submitting} />
            </div>

            <Button
                type="submit"
                disabled={!subject.trim() || !description.trim() || submitting}
                className="bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl w-full font-semibold"
            >
                {submitting ? (
                    <>
                        <Loader2 className="w-4 h-4 animate-spin mr-2" />
                        Gönderiliyor...
                    </>
                ) : (
                    <>
                        <Send className="w-4 h-4 mr-2" />
                        Talep Oluştur
                    </>
                )}
            </Button>
        </form>
    );
}

// --- Main Export ---
export function SupportTicketsContent({ subNav, onSubNavChange }: { subNav: string; onSubNavChange?: (subId: string) => void }) {
    const [selectedTicketId, setSelectedTicketId] = useState<number | null>(null);
    const [listKey, setListKey] = useState(0);

    const goToTicketList = () => {
        setListKey(k => k + 1);
        onSubNavChange?.('taleplerim');
    };

    if (subNav === 'yeni-talep') {
        return (
            <NewTicketForm onCreated={goToTicketList} />
        );
    }

    // taleplerim
    if (selectedTicketId) {
        return (
            <TicketDetail
                ticketId={selectedTicketId}
                onBack={() => setSelectedTicketId(null)}
            />
        );
    }

    return (
        <TicketList
            key={listKey}
            onSelectTicket={setSelectedTicketId}
        />
    );
}
