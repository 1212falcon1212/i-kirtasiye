@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-4">
    @php
        $documentTypes = [
            'ruhsat' => 'Kırtasiye Ruhsatı',
            'oda_kaydi' => 'Oda Kayıt Belgesi',
            'vergi_levhasi' => 'Vergi Levhası',
            'kimlik' => 'Kimlik Fotokopisi',
            'imza_sirkusu' => 'İmza Sirküleri',
        ];

        $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
        ];

        $statusLabels = [
            'pending' => 'Beklemede',
            'approved' => 'Onaylandı',
            'rejected' => 'Reddedildi',
        ];
    @endphp

    @if($documents->count() > 0)
        @foreach($documents as $document)
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="font-semibold text-gray-900">
                                {{ $documentTypes[$document->type] ?? $document->type }}
                            </p>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$document->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$document->status] ?? $document->status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">{{ $document->original_name }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            Yüklenme: {{ $document->created_at->format('d.m.Y H:i') }}
                            @if($document->file_size)
                                | Boyut: {{ number_format($document->file_size / 1024, 1) }} KB
                            @endif
                        </p>
                        @if($document->rejection_reason)
                            <p class="mt-2 text-sm text-red-600">
                                <strong>Ret sebebi:</strong> {{ $document->rejection_reason }}
                            </p>
                        @endif
                    </div>
                    <a href="{{ Storage::url($document->file_path) }}" target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        Görüntüle
                    </a>
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2">Henüz belge yüklenmemiş</p>
        </div>
    @endif
</div>
