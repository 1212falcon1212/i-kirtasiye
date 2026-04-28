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
        return [
            Forms\Components\Hidden::make('id'),
            Forms\Components\FileUpload::make('image_path')
                ->label('Görsel')
                ->helperText('Hero banner için önerilen boyut: 1920x500px. Görsel tam ekran genişliğinde gösterilir.')
                ->image()
                ->directory('banners')
                ->required()
                ->imagePreviewHeight('200')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->columnSpanFull(),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('link_url')
                        ->label('Link (Tıklama Yönlendirmesi)')
                        ->placeholder('/market/kampanyalar')
                        ->helperText('Banner tıklandığında yönlendirilecek sayfa'),
                    Forms\Components\TextInput::make('title')
                        ->label('Başlık (Opsiyonel)')
                        ->placeholder('Sadece yönetim paneli için - sitede görünmez'),
                ]),
            Forms\Components\Section::make('Gelişmiş Ayarlar')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('subtitle')
                                ->label('Alt Başlık')
                                ->placeholder('Opsiyonel'),
                            Forms\Components\TextInput::make('badge_text')
                                ->label('Badge Metni')
                                ->placeholder('Opsiyonel'),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('button_text')
                                ->label('Buton Metni')
                                ->placeholder('Opsiyonel'),
                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sıralama')
                                ->numeric()
                                ->default(0),
                        ]),
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true),
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Başlangıç Tarihi'),
                            Forms\Components\DateTimePicker::make('ends_at')
                                ->label('Bitiş Tarihi'),
                        ]),
                ])
                ->collapsed()
                ->collapsible(),
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
