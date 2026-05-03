<?php

namespace App\Filament\Pages;

use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BannerManagerPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Banner Yönetimi';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Banner Yönetimi';

    protected static string $view = 'filament.pages.banner-manager';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data['location'] = 'home_hero';
        $this->loadBanners();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('location')
                    ->label('Konum')
                    ->options(Banner::locationOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->loadBanners())
                    ->columnSpanFull(),

                Forms\Components\Repeater::make('banners')
                    ->label('')
                    ->schema($this->getBannerSchema())
                    ->collapsible()
                    ->collapsed()
                    ->cloneable()
                    ->reorderableWithButtons()
                    ->itemLabel(fn (array $state): string => $state['title'] ?: 'Banner (Sadece Görsel)')
                    ->addActionLabel('Banner Ekle')
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getBannerSchema(): array
    {
        $isHero = ($this->data['location'] ?? 'home_hero') === 'home_hero';

        return [
            Forms\Components\Hidden::make('id'),
            Forms\Components\FileUpload::make('image_path')
                ->label('Sağ Panel Görseli')
                ->helperText($isHero
                    ? 'Hero banner tam genişlikte gösterilir. Önerilen boyut: 1600x400px (4:1 oran). Tüm metni ve butonu görsel içinde tasarlayabilirsiniz. Birden fazla banner eklerseniz otomatik döner.'
                    : 'Görsel yükleyin.')
                ->image()
                ->directory('banners')
                ->required()
                ->imagePreviewHeight('200')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->columnSpanFull(),
            Forms\Components\Section::make($isHero ? 'Sol Panel Metni (Opsiyonel)' : 'Metin Alanları (Opsiyonel)')
                ->description($isHero
                    ? 'Sol turuncu panelde gösterilecek metinler. Boş bırakırsanız sadece sağ görsel görünür.'
                    : null)
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('badge_text')
                                ->label($isHero ? 'Üst Etiket (Eyebrow)' : 'Badge Metni')
                                ->placeholder($isHero ? 'örn. Hafta sonuna özel' : 'Opsiyonel')
                                ->helperText($isHero ? 'Başlığın üstünde küçük rozet' : null)
                                ->maxLength(50),
                            Forms\Components\TextInput::make('button_text')
                                ->label('Buton Metni')
                                ->placeholder($isHero ? 'örn. Alışverişe başla' : 'Opsiyonel')
                                ->maxLength(50),
                        ]),
                    Forms\Components\TextInput::make('title')
                        ->label($isHero ? 'Ana Başlık' : 'Başlık')
                        ->placeholder($isHero ? 'örn. OKULA DÖNÜŞ %25 İNDİRİM' : 'Opsiyonel')
                        ->maxLength(255),
                    Forms\Components\Textarea::make('subtitle')
                        ->label($isHero ? 'Açıklama' : 'Alt Başlık')
                        ->placeholder($isHero ? 'örn. Defter, kalem, çanta ve daha fazlası — bayilere özel toptan fiyatlarla.' : 'Opsiyonel')
                        ->rows(2)
                        ->maxLength(500),
                    Forms\Components\TextInput::make('link_url')
                        ->label('Link URL (Buton + Görsel Tıklaması)')
                        ->placeholder('/market/kampanyalar')
                        ->helperText('Buton ve görsele tıklandığında gidilecek sayfa')
                        ->maxLength(255),
                ])
                ->collapsible()
                ->collapsed(false),
            Forms\Components\Section::make('Yayın Ayarları')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sıralama')
                                ->numeric()
                                ->default(0),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Başlangıç Tarihi'),
                            Forms\Components\DateTimePicker::make('ends_at')
                                ->label('Bitiş Tarihi'),
                        ]),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    public function loadBanners(): void
    {
        $location = $this->data['location'] ?? 'home_hero';
        $banners = Banner::where('location', $location)->ordered()->get();

        $this->data['banners'] = $banners->map(fn (Banner $b) => $this->bannerToArray($b))->values()->toArray();
    }

    protected function bannerToArray(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'image_path' => $banner->image_path ? [$banner->image_path] : [],
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'badge_text' => $banner->badge_text,
            'link_url' => $banner->link_url,
            'button_text' => $banner->button_text,
            'is_active' => $banner->is_active,
            'sort_order' => $banner->sort_order,
            'starts_at' => $banner->starts_at?->format('Y-m-d H:i:s'),
            'ends_at' => $banner->ends_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $location = $data['location'];

        DB::beginTransaction();
        try {
            $existingIds = Banner::where('location', $location)->pluck('id')->toArray();
            $processedIds = [];

            foreach ($data['banners'] ?? [] as $index => $bannerData) {
                $id = $this->saveBanner($bannerData, $location, $index, $existingIds);
                $processedIds[] = $id;
            }

            // Kaldırılan bannerları sil
            $toDelete = array_diff($existingIds, $processedIds);
            if (! empty($toDelete)) {
                Banner::whereIn('id', $toDelete)->delete();
            }

            // İlgili cache'leri temizle
            Cache::forget("cms.banners.{$location}");
            Cache::forget('cms.homepage');

            DB::commit();

            Notification::make()
                ->title('Bannerlar başarıyla kaydedildi')
                ->success()
                ->send();

            $this->loadBanners();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Hata oluştu')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function saveBanner(array $data, string $location, int $sortIndex, array $existingIds): int
    {
        $imagePath = $data['image_path'];
        if (is_array($imagePath)) {
            $imagePath = array_values($imagePath)[0] ?? null;
        }

        $attrs = [
            'location' => $location,
            'tab_name' => null,
            'bg_color' => null,
            'image_path' => $imagePath,
            'title' => $data['title'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'badge_text' => $data['badge_text'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? $sortIndex,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
        ];

        $bannerId = $data['id'] ?? null;

        if ($bannerId && in_array($bannerId, $existingIds)) {
            Banner::where('id', $bannerId)->update($attrs);

            return $bannerId;
        }

        return Banner::create($attrs)->id;
    }
}
