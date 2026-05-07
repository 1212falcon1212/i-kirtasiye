<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Landing sayfa iceriklerini yonetir.
 * Her alan ayri bir setting key'i olarak saklanir.
 */
class LandingPageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static string $view = 'filament.pages.landing-page-settings';

    protected static ?string $navigationLabel = 'Landing Sayfa';

    protected static ?string $title = 'Landing Sayfa Ayarları';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 101;

    public ?array $data = [];

    /**
     * Tum landing setting key'leri ve varsayilan degerleri
     */
    public static function defaults(): array
    {
        return [
            // Hero
            'landing.hero_title' => "Türkiye'nin Kırtasiyelere Özel İlk & En Büyük B2B Pazaryeri!",
            'landing.hero_subtitle' => 'Onaylı tedarikçilerden defter, kalem, ofis ve okul malzemelerini en uygun fiyatlarla bulun!',
            'landing.hero_image' => null,
            'landing.hero_cta_text' => 'Hemen Üye Ol',

            // Neden i-kirtasiye - Baslik
            'landing.why_title' => 'i-kirtasiye\'yi Neden Tercih Edeceksiniz?',

            // Neden i-kirtasiye - Kart 1
            'landing.why_card1_title' => 'Güvenilir Alışveriş',
            'landing.why_card1_desc' => 'i-kirtasiye\'de alıcı da satıcı da onaylı işletmedir. Vergi no doğrulaması ve sıkı denetimlerle güvenli ticaret sağlıyoruz.',
            'landing.why_card1_image' => null,

            // Neden i-kirtasiye - Kart 2
            'landing.why_card2_title' => 'Avantajlı Fiyatlar',
            'landing.why_card2_desc' => 'Düşük hizmet bedelimiz sayesinde kırtasiyecilerimiz en uygun fiyatlı ürünleri temin ederler.',
            'landing.why_card2_image' => null,

            // Neden i-kirtasiye - Kart 3
            'landing.why_card3_title' => 'Bedava Kargo',
            'landing.why_card3_desc' => 'Tüm siparişlerde kargo ücreti satıcı tarafından karşılanır.',
            'landing.why_card3_image' => null,

            // Platform Tanitim - Baslik
            'landing.how_it_works_title' => '3 Adımda Ticarete Başlayın',

            // Platform Tanitim - Feature 1
            'landing.feature1_title' => 'Fiyatları kolayca karşılaştır!',
            'landing.feature1_desc' => 'Aynı ürünün tüm ilanlarını bir araya getirdik. Fiyat ve miat bilgilerini tek ekranda görebilirsiniz!',
            'landing.feature1_image' => null,

            // Platform Tanitim - Feature 2
            'landing.feature2_title' => 'Dilediğin ürünü kolayca satışa çıkar!',
            'landing.feature2_desc' => 'Ürünün fotoğrafı, kategorisi ve markası bellidir. Size kalan yalnızca ürünün adını seçmek ve fiyatını belirlemek!',
            'landing.feature2_image' => null,

            // Platform Tanitim - Feature 3
            'landing.feature3_title' => 'Siparişlerinizi tek ekrandan yönetin!',
            'landing.feature3_desc' => 'Tüm siparişlerinizi, iadelerinizi ve hakedişlerinizi tek panelden takip edin.',
            'landing.feature3_image' => null,

            // Musteri Yorumlari - Testimonial 1
            'landing.testimonial1_name' => 'A.Y.',
            'landing.testimonial1_title' => 'Kırtasiyeci, İstanbul',
            'landing.testimonial1_quote' => 'Stoklarımı kolayca nakde çevirebildim. Arayüz çok kullanışlı, güven veriyor.',
            'landing.testimonial1_photo' => null,

            // Musteri Yorumlari - Testimonial 2
            'landing.testimonial2_name' => 'F.D.',
            'landing.testimonial2_title' => 'Kırtasiyeci, Ankara',
            'landing.testimonial2_quote' => 'Piyasanın altında fiyatlarla tedarik yapabiliyorum. Kargo entegrasyonu da çok pratik.',
            'landing.testimonial2_photo' => null,

            // Musteri Yorumlari - Testimonial 3
            'landing.testimonial3_name' => 'M.K.',
            'landing.testimonial3_title' => 'Kırtasiyeci, İzmir',
            'landing.testimonial3_quote' => 'Güvenli ödeme sistemi sayesinde hiç sorun yaşamadım. Hakedişler anında yansıyor.',
            'landing.testimonial3_photo' => null,

            // CTA
            'landing.cta_title' => 'Hemen Ücretsiz Üye Olun!',
            'landing.cta_subtitle' => 'Vergi numaranız ile hemen kayıt olun',

            // Istatistikler
            'landing.stat1_label' => 'Kırtasiye',
            'landing.stat1_value' => '500+',
            'landing.stat2_label' => 'Ürün',
            'landing.stat2_value' => '10.000+',
            'landing.stat3_label' => 'Satıcı',
            'landing.stat3_value' => '200+',
            'landing.stat4_label' => 'Komisyon',
            'landing.stat4_value' => '%0',
        ];
    }

    public function mount(): void
    {
        $defaults = self::defaults();
        $fill = [];

        foreach ($defaults as $key => $default) {
            // Form field name: 'landing.hero_title' -> 'hero_title'
            $fieldName = str_replace('landing.', '', $key);
            $fill[$fieldName] = Setting::getValue($key, $default);
        }

        $this->form->fill($fill);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Landing')
                    ->tabs([
                        // Tab 1: Hero
                        Forms\Components\Tabs\Tab::make('Hero')
                            ->icon('heroicon-o-star')
                            ->schema([
                                Forms\Components\TextInput::make('hero_title')
                                    ->label('Başlık')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('hero_subtitle')
                                    ->label('Alt Başlık')
                                    ->required()
                                    ->maxLength(500),
                                Forms\Components\FileUpload::make('hero_image')
                                    ->label('Hero Arka Plan Görseli')
                                    ->image()
                                    ->directory('landing')
                                    ->maxSize(2048)
                                    ->helperText('Önerilen boyut: 1920x800px'),
                                Forms\Components\TextInput::make('hero_cta_text')
                                    ->label('CTA Buton Metni')
                                    ->maxLength(100),
                            ]),

                        // Tab 2: Neden i-kirtasiye?
                        Forms\Components\Tabs\Tab::make('Neden i-kirtasiye?')
                            ->icon('heroicon-o-heart')
                            ->schema([
                                Forms\Components\TextInput::make('why_title')
                                    ->label('Bölüm Başlığı')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Section::make('Kart 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('why_card1_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('why_card1_desc')
                                            ->label('Açıklama')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('why_card1_image')
                                            ->label('Görsel')
                                            ->image()
                                            ->directory('landing')
                                            ->maxSize(1024),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Kart 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('why_card2_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('why_card2_desc')
                                            ->label('Açıklama')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('why_card2_image')
                                            ->label('Görsel')
                                            ->image()
                                            ->directory('landing')
                                            ->maxSize(1024),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Kart 3')
                                    ->schema([
                                        Forms\Components\TextInput::make('why_card3_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('why_card3_desc')
                                            ->label('Açıklama')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('why_card3_image')
                                            ->label('Görsel')
                                            ->image()
                                            ->directory('landing')
                                            ->maxSize(1024),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 3: Platform Tanitim
                        Forms\Components\Tabs\Tab::make('Platform Tanıtım')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                Forms\Components\TextInput::make('how_it_works_title')
                                    ->label('Bölüm Başlığı')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Örnek: 3 Adımda Ticarete Başlayın'),

                                Forms\Components\Section::make('Feature 1')
                                    ->description('Sağda görsel gösterilir')
                                    ->schema([
                                        Forms\Components\TextInput::make('feature1_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('feature1_desc')
                                            ->label('Açıklama')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('feature1_image')
                                            ->label('Görsel')
                                            ->image()
                                            ->directory('landing')
                                            ->maxSize(1024),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Feature 2')
                                    ->description('Solda görsel gösterilir')
                                    ->schema([
                                        Forms\Components\TextInput::make('feature2_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('feature2_desc')
                                            ->label('Açıklama')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('feature2_image')
                                            ->label('Görsel')
                                            ->image()
                                            ->directory('landing')
                                            ->maxSize(1024),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Feature 3')
                                    ->description('Sağda görsel gösterilir')
                                    ->schema([
                                        Forms\Components\TextInput::make('feature3_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('feature3_desc')
                                            ->label('Açıklama')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('feature3_image')
                                            ->label('Görsel')
                                            ->image()
                                            ->directory('landing')
                                            ->maxSize(1024),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 4: Musteri Yorumlari
                        Forms\Components\Tabs\Tab::make('Müşteri Yorumları')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Section::make('Yorum 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('testimonial1_name')
                                            ->label('İsim')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('testimonial1_title')
                                            ->label('Unvan')
                                            ->helperText('Örnek: Kırtasiye Sahibi, İstanbul')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('testimonial1_quote')
                                            ->label('Yorum')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('testimonial1_photo')
                                            ->label('Fotoğraf')
                                            ->image()
                                            ->avatar()
                                            ->directory('landing')
                                            ->maxSize(512),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Yorum 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('testimonial2_name')
                                            ->label('İsim')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('testimonial2_title')
                                            ->label('Unvan')
                                            ->helperText('Örnek: Kırtasiye Sahibi, Ankara')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('testimonial2_quote')
                                            ->label('Yorum')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('testimonial2_photo')
                                            ->label('Fotoğraf')
                                            ->image()
                                            ->avatar()
                                            ->directory('landing')
                                            ->maxSize(512),
                                    ])
                                    ->collapsible(),

                                Forms\Components\Section::make('Yorum 3')
                                    ->schema([
                                        Forms\Components\TextInput::make('testimonial3_name')
                                            ->label('İsim')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('testimonial3_title')
                                            ->label('Unvan')
                                            ->helperText('Örnek: Kırtasiye Sahibi, İzmir')
                                            ->required()
                                            ->maxLength(100),
                                        Forms\Components\Textarea::make('testimonial3_quote')
                                            ->label('Yorum')
                                            ->rows(3)
                                            ->required()
                                            ->maxLength(500),
                                        Forms\Components\FileUpload::make('testimonial3_photo')
                                            ->label('Fotoğraf')
                                            ->image()
                                            ->avatar()
                                            ->directory('landing')
                                            ->maxSize(512),
                                    ])
                                    ->collapsible(),
                            ]),

                        // Tab 5: CTA & Istatistikler
                        Forms\Components\Tabs\Tab::make('CTA & İstatistikler')
                            ->icon('heroicon-o-megaphone')
                            ->schema([
                                Forms\Components\Section::make('CTA Bölümü')
                                    ->schema([
                                        Forms\Components\TextInput::make('cta_title')
                                            ->label('Başlık')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('cta_subtitle')
                                            ->label('Alt Başlık')
                                            ->required()
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\Section::make('İstatistikler')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('stat1_label')
                                                    ->label('İstatistik 1 - Etiket')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat1_value')
                                                    ->label('İstatistik 1 - Değer')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat2_label')
                                                    ->label('İstatistik 2 - Etiket')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat2_value')
                                                    ->label('İstatistik 2 - Değer')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat3_label')
                                                    ->label('İstatistik 3 - Etiket')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat3_value')
                                                    ->label('İstatistik 3 - Değer')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat4_label')
                                                    ->label('İstatistik 4 - Etiket')
                                                    ->required()
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('stat4_value')
                                                    ->label('İstatistik 4 - Değer')
                                                    ->required()
                                                    ->maxLength(50),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $defaults = self::defaults();

        foreach ($defaults as $settingKey => $default) {
            $fieldName = str_replace('landing.', '', $settingKey);
            $value = $data[$fieldName] ?? $default;

            Setting::setValue($settingKey, $value, 'landing', 'string');
        }

        Notification::make()
            ->title('Landing sayfa ayarları kaydedildi')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Kaydet')
                ->submit('save'),
        ];
    }
}
