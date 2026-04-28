<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\ShippingRate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ShippingSettingsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static string $view = 'filament.pages.shipping-settings';

    protected static ?string $navigationLabel = 'Kargo Ayarları';

    protected static ?string $title = 'Kargo Ayarları';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'flat_rate' => Setting::getValue('shipping.flat_rate', 29.90),
            'free_threshold' => Setting::getValue('shipping.free_threshold', 2500),
            'default_provider' => Setting::getValue('shipping.default_provider', 'none'),
            // Aras
            'aras_enabled' => Setting::getValue('shipping.aras_enabled', false),
            'aras_test_mode' => Setting::getValue('shipping.aras_test_mode', true),
            'aras_customer_code' => Setting::getValue('shipping.aras_customer_code', ''),
            'aras_username' => Setting::getValue('shipping.aras_username', ''),
            'aras_password' => Setting::getValue('shipping.aras_password', ''),
            'aras_configuration_id' => Setting::getValue('shipping.aras_configuration_id', ''),
            'aras_sender_name' => Setting::getValue('shipping.aras_sender_name', ''),
            'aras_sender_phone' => Setting::getValue('shipping.aras_sender_phone', ''),
            'aras_sender_address' => Setting::getValue('shipping.aras_sender_address', ''),
            'aras_sender_city' => Setting::getValue('shipping.aras_sender_city', ''),
            'aras_sender_district' => Setting::getValue('shipping.aras_sender_district', ''),
            'aras_tracking_account_id' => Setting::getValue('shipping.aras_tracking_account_id', ''),
            'aras_tracking_username' => Setting::getValue('shipping.aras_tracking_username', ''),
            'aras_tracking_password' => Setting::getValue('shipping.aras_tracking_password', ''),
            // Yurtiçi
            'yurtici_enabled' => Setting::getValue('shipping.yurtici_enabled', false),
            'yurtici_test_mode' => Setting::getValue('shipping.yurtici_test_mode', true),
            'yurtici_username' => Setting::getValue('shipping.yurtici_username', ''),
            'yurtici_password' => Setting::getValue('shipping.yurtici_password', ''),
            'yurtici_customer_id' => Setting::getValue('shipping.yurtici_customer_id', ''),
            'yurtici_project_code' => Setting::getValue('shipping.yurtici_project_code', ''),
            // MNG
            'mng_enabled' => Setting::getValue('shipping.mng_enabled', false),
            'mng_username' => Setting::getValue('shipping.mng_username', ''),
            'mng_password' => Setting::getValue('shipping.mng_password', ''),
            // Sendeo
            'sendeo_enabled' => Setting::getValue('shipping.sendeo_enabled', false),
            'sendeo_customer_code' => Setting::getValue('shipping.sendeo_customer_code', ''),
            'sendeo_password' => Setting::getValue('shipping.sendeo_password', ''),
            // Hepsijet
            'hepsijet_enabled' => Setting::getValue('shipping.hepsijet_enabled', false),
            'hepsijet_api_key' => Setting::getValue('shipping.hepsijet_api_key', ''),
            'hepsijet_api_secret' => Setting::getValue('shipping.hepsijet_api_secret', ''),
            // PTT
            'ptt_enabled' => Setting::getValue('shipping.ptt_enabled', false),
            'ptt_username' => Setting::getValue('shipping.ptt_username', ''),
            'ptt_password' => Setting::getValue('shipping.ptt_password', ''),
            'ptt_customer_id' => Setting::getValue('shipping.ptt_customer_id', ''),
            // Sürat
            'surat_enabled' => Setting::getValue('shipping.surat_enabled', false),
            'surat_username' => Setting::getValue('shipping.surat_username', ''),
            'surat_password' => Setting::getValue('shipping.surat_password', ''),
            // Kolaygelsin
            'kolaygelsin_enabled' => Setting::getValue('shipping.kolaygelsin_enabled', false),
            'kolaygelsin_customer_code' => Setting::getValue('shipping.kolaygelsin_customer_code', ''),
            'kolaygelsin_password' => Setting::getValue('shipping.kolaygelsin_password', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Genel Kargo Ayarları')
                    ->description('Kargo ücretlendirme kuralları')
                    ->schema([
                        Forms\Components\TextInput::make('flat_rate')
                            ->label('Sabit Kargo Ücreti')
                            ->numeric()
                            ->suffix('₺')
                            ->default(29.90),

                        Forms\Components\TextInput::make('free_threshold')
                            ->label('Ücretsiz Kargo Limiti (Satıcı Bazlı)')
                            ->numeric()
                            ->suffix('₺')
                            ->default(2500)
                            ->helperText('Satıcı alt toplamı bu tutarı aşarsa müşteriye ücretsiz kargo. 0 = devre dışı'),

                        Forms\Components\Select::make('default_provider')
                            ->label('Varsayılan Kargo Firması')
                            ->options([
                                'none' => 'Seçilmedi',
                                'aras' => 'Aras Kargo',
                                'yurtici' => 'Yurtiçi Kargo',
                                'mng' => 'MNG Kargo',
                                'sendeo' => 'Sendeo',
                                'hepsijet' => 'Hepsijet',
                                'ptt' => 'PTT Kargo',
                                'surat' => 'Sürat Kargo',
                                'kolaygelsin' => 'Kolaygelsin',
                            ])
                            ->default('none'),
                    ])
                    ->columns(3),

                // Aras Kargo
                Forms\Components\Section::make('Aras Kargo')
                    ->schema([
                        Forms\Components\Toggle::make('aras_enabled')
                            ->label('Aktif')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('aras_test_mode')
                            ->label('Test Modu')
                            ->helperText('Açık: Test ortamı URL kullanılır. Canlıya geçmeden önce kapatın.')
                            ->default(true)
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('aras_test_credentials_help')
                            ->label('Test Modu Referans Bilgileri')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div style="background:#fef3c7;border:1px solid #fcd34d;padding:10px;border-radius:6px;font-size:13px;line-height:1.7">
                                    <strong>SetOrder (Sevkiyat) test kullanıcısı:</strong><br>
                                    Kullanıcı: <code>neodyum</code> — Şifre: <code>nd2580</code><br>
                                    <strong>GetQuery (Sorgulama) test kullanıcısı:</strong><br>
                                    Kullanıcı: <code>test</code> — Şifre: <code>123</code> — Müşteri Kodu: <code>1317538011316</code><br>
                                    <em>Canlı credentials için test senaryoları başarılı olduktan sonra Aras BT ekibine başvurun.</em>
                                </div>'
                            ))
                            ->columnSpanFull()
                            ->visible(fn ($get) => (bool) $get('aras_test_mode')),
                        Forms\Components\TextInput::make('aras_customer_code')
                            ->label('Müşteri Kodu')
                            ->helperText('Aras Kargo müşteri kodunuz'),
                        Forms\Components\TextInput::make('aras_username')
                            ->label('Kullanıcı Adı'),
                        Forms\Components\TextInput::make('aras_password')
                            ->label('Şifre')
                            ->password(),
                        Forms\Components\TextInput::make('aras_configuration_id')
                            ->label('Konfigürasyon ID')
                            ->helperText('Aras Kargo tarafından verilen anlaşma ID'),

                        Forms\Components\Fieldset::make('Gönderici Bilgileri')
                            ->schema([
                                Forms\Components\TextInput::make('aras_sender_name')
                                    ->label('Gönderici Adı')
                                    ->placeholder('Firma Adınız'),
                                Forms\Components\TextInput::make('aras_sender_phone')
                                    ->label('Gönderici Telefon')
                                    ->placeholder('05XX XXX XX XX'),
                                Forms\Components\TextInput::make('aras_sender_address')
                                    ->label('Gönderici Adres')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('aras_sender_city')
                                    ->label('İl')
                                    ->placeholder('İstanbul'),
                                Forms\Components\TextInput::make('aras_sender_district')
                                    ->label('İlçe')
                                    ->placeholder('Kadıköy'),
                            ])
                            ->columns(2),

                        Forms\Components\Fieldset::make('Kargo Takip Web Sayfası')
                            ->schema([
                                Forms\Components\TextInput::make('aras_tracking_account_id')
                                    ->label('Account ID')
                                    ->helperText('esasweb panelinden Kargo Takip üyeliği sonrası verilen sabit kod'),
                                Forms\Components\TextInput::make('aras_tracking_username')
                                    ->label('Takip Kullanıcı Adı'),
                                Forms\Components\TextInput::make('aras_tracking_password')
                                    ->label('Takip Şifre')
                                    ->password(),
                            ])
                            ->columns(2),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // Yurtiçi Kargo
                Forms\Components\Section::make('Yurtiçi Kargo')
                    ->description('Pazaryeri Yurtiçi sözleşme bilgileri. Gönderici bilgileri her satıcının kendi profilinden alınır.')
                    ->schema([
                        Forms\Components\Toggle::make('yurtici_enabled')
                            ->label('Aktif')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('yurtici_test_mode')
                            ->label('Test Modu')
                            ->helperText('Açık: testws.yurticikargo.com kullanılır. Canlıya geçmeden önce kapatın.')
                            ->default(true)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('yurtici_customer_id')
                            ->label('Müşteri ID')
                            ->helperText('Sözleşmeli ödeyecek müşteri kodu (invCustId)'),
                        Forms\Components\TextInput::make('yurtici_project_code')
                            ->label('Proje Kodu')
                            ->helperText('Etiket üzerinde gözükecek proje kodu (örn: 412918)'),
                        Forms\Components\TextInput::make('yurtici_username')
                            ->label('Kullanıcı Adı'),
                        Forms\Components\TextInput::make('yurtici_password')
                            ->label('Şifre')
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // MNG Kargo
                Forms\Components\Section::make('MNG Kargo')
                    ->schema([
                        Forms\Components\Toggle::make('mng_enabled')->label('Aktif'),
                        Forms\Components\TextInput::make('mng_username')->label('Kullanıcı Adı'),
                        Forms\Components\TextInput::make('mng_password')->label('Şifre')->password(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // Sendeo
                Forms\Components\Section::make('Sendeo')
                    ->schema([
                        Forms\Components\Toggle::make('sendeo_enabled')->label('Aktif'),
                        Forms\Components\TextInput::make('sendeo_customer_code')->label('Müşteri Kodu'),
                        Forms\Components\TextInput::make('sendeo_password')->label('Şifre')->password(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // Hepsijet
                Forms\Components\Section::make('Hepsijet')
                    ->schema([
                        Forms\Components\Toggle::make('hepsijet_enabled')->label('Aktif'),
                        Forms\Components\TextInput::make('hepsijet_api_key')->label('API Key'),
                        Forms\Components\TextInput::make('hepsijet_api_secret')->label('API Secret')->password(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // PTT Kargo
                Forms\Components\Section::make('PTT Kargo')
                    ->schema([
                        Forms\Components\Toggle::make('ptt_enabled')->label('Aktif'),
                        Forms\Components\TextInput::make('ptt_customer_id')->label('Müşteri ID'),
                        Forms\Components\TextInput::make('ptt_username')->label('Kullanıcı Adı'),
                        Forms\Components\TextInput::make('ptt_password')->label('Şifre')->password(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // Sürat Kargo
                Forms\Components\Section::make('Sürat Kargo')
                    ->schema([
                        Forms\Components\Toggle::make('surat_enabled')->label('Aktif'),
                        Forms\Components\TextInput::make('surat_username')->label('Cari Kodu'),
                        Forms\Components\TextInput::make('surat_password')->label('Şifre')->password(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                // Kolaygelsin
                Forms\Components\Section::make('Kolaygelsin')
                    ->schema([
                        Forms\Components\Toggle::make('kolaygelsin_enabled')->label('Aktif'),
                        Forms\Components\TextInput::make('kolaygelsin_customer_code')->label('Müşteri Kodu'),
                        Forms\Components\TextInput::make('kolaygelsin_password')->label('Şifre')->password(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // General settings
        Setting::setValue('shipping.flat_rate', $data['flat_rate']);
        Setting::setValue('shipping.free_threshold', $data['free_threshold']);
        Setting::setValue('shipping.default_provider', $data['default_provider']);

        // Aras
        Setting::setValue('shipping.aras_enabled', $data['aras_enabled']);
        Setting::setValue('shipping.aras_test_mode', $data['aras_test_mode']);
        Setting::setValue('shipping.aras_customer_code', $data['aras_customer_code']);
        Setting::setValue('shipping.aras_username', $data['aras_username']);
        Setting::setValue('shipping.aras_password', $data['aras_password']);
        Setting::setValue('shipping.aras_configuration_id', $data['aras_configuration_id']);
        Setting::setValue('shipping.aras_sender_name', $data['aras_sender_name'] ?? '');
        Setting::setValue('shipping.aras_sender_phone', $data['aras_sender_phone'] ?? '');
        Setting::setValue('shipping.aras_sender_address', $data['aras_sender_address'] ?? '');
        Setting::setValue('shipping.aras_sender_city', $data['aras_sender_city'] ?? '');
        Setting::setValue('shipping.aras_sender_district', $data['aras_sender_district'] ?? '');
        Setting::setValue('shipping.aras_tracking_account_id', $data['aras_tracking_account_id'] ?? '');
        Setting::setValue('shipping.aras_tracking_username', $data['aras_tracking_username'] ?? '');
        Setting::setValue('shipping.aras_tracking_password', $data['aras_tracking_password'] ?? '');

        // Yurtiçi
        Setting::setValue('shipping.yurtici_enabled', $data['yurtici_enabled']);
        Setting::setValue('shipping.yurtici_test_mode', $data['yurtici_test_mode'] ?? true);
        Setting::setValue('shipping.yurtici_customer_id', $data['yurtici_customer_id']);
        Setting::setValue('shipping.yurtici_project_code', $data['yurtici_project_code'] ?? '');
        Setting::setValue('shipping.yurtici_username', $data['yurtici_username']);
        Setting::setValue('shipping.yurtici_password', $data['yurtici_password']);

        // MNG
        Setting::setValue('shipping.mng_enabled', $data['mng_enabled']);
        Setting::setValue('shipping.mng_username', $data['mng_username']);
        Setting::setValue('shipping.mng_password', $data['mng_password']);

        // Sendeo
        Setting::setValue('shipping.sendeo_enabled', $data['sendeo_enabled']);
        Setting::setValue('shipping.sendeo_customer_code', $data['sendeo_customer_code']);
        Setting::setValue('shipping.sendeo_password', $data['sendeo_password']);

        // Hepsijet
        Setting::setValue('shipping.hepsijet_enabled', $data['hepsijet_enabled']);
        Setting::setValue('shipping.hepsijet_api_key', $data['hepsijet_api_key']);
        Setting::setValue('shipping.hepsijet_api_secret', $data['hepsijet_api_secret']);

        // PTT
        Setting::setValue('shipping.ptt_enabled', $data['ptt_enabled']);
        Setting::setValue('shipping.ptt_customer_id', $data['ptt_customer_id']);
        Setting::setValue('shipping.ptt_username', $data['ptt_username']);
        Setting::setValue('shipping.ptt_password', $data['ptt_password']);

        // Sürat
        Setting::setValue('shipping.surat_enabled', $data['surat_enabled']);
        Setting::setValue('shipping.surat_username', $data['surat_username']);
        Setting::setValue('shipping.surat_password', $data['surat_password']);

        // Kolaygelsin
        Setting::setValue('shipping.kolaygelsin_enabled', $data['kolaygelsin_enabled']);
        Setting::setValue('shipping.kolaygelsin_customer_code', $data['kolaygelsin_customer_code']);
        Setting::setValue('shipping.kolaygelsin_password', $data['kolaygelsin_password']);

        Setting::clearCache();

        Notification::make()
            ->title('Kargo ayarları kaydedildi')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Kaydet')
                ->submit('save'),
            Forms\Components\Actions\Action::make('testAras')
                ->label('Aras Bağlantısını Test Et')
                ->color('info')
                ->icon('heroicon-o-bolt')
                ->action('testArasConnection'),
        ];
    }

    /**
     * Aras Kargo test bağlantısı — test modunda mock sipariş gönder ve iptal et
     */
    public function testArasConnection(): void
    {
        $this->save();

        if (! Setting::getValue('shipping.aras_enabled', false)) {
            Notification::make()
                ->title('Aras Kargo entegrasyonu aktif değil')
                ->body('Önce "Aktif" toggle\'ını açıp ayarları kaydedin.')
                ->warning()
                ->send();

            return;
        }

        try {
            $provider = app(\App\Services\Shipping\ArasProvider::class);

            // Test bağlantısı için QueryType=10 (şube listesi) çağır — sipariş oluşturmadan doğrulama
            $reflection = new \ReflectionClass($provider);
            $queryMethod = $reflection->getMethod('query');
            $queryMethod->setAccessible(true);

            $result = $queryMethod->invoke($provider, 10, []);

            if (is_array($result) && count($result) > 0) {
                Notification::make()
                    ->title('Bağlantı başarılı')
                    ->body('Sorgulama servisi çalışıyor — '.count($result).' şube bilgisi alındı.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Bağlantı kuruldu fakat veri gelmedi')
                    ->body('Credentials doğru ama sorgu sonucu boş. Canlı ortama geçmeden önce Aras BT ile görüşün.')
                    ->warning()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Bağlantı başarısız')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ShippingRate::query())
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->label('Kargo Firması')
                    ->formatStateUsing(fn ($state) => ShippingRate::PROVIDER_LABELS[$state] ?? $state)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_desi')
                    ->label('Min Desi')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_desi')
                    ->label('Max Desi')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Fiyat')
                    ->money('TRY')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Kargo Firması')
                    ->options(ShippingRate::PROVIDER_LABELS),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Desi Fiyatı Ekle')
                    ->form([
                        Forms\Components\Select::make('provider')
                            ->label('Kargo Firması')
                            ->options(ShippingRate::PROVIDER_LABELS)
                            ->required(),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('min_desi')
                                ->label('Min Desi')
                                ->numeric()
                                ->default(0)
                                ->required(),
                            Forms\Components\TextInput::make('max_desi')
                                ->label('Max Desi')
                                ->numeric()
                                ->required(),
                        ]),
                        Forms\Components\TextInput::make('price')
                            ->label('Fiyat')
                            ->numeric()
                            ->suffix('₺')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('provider')
                            ->label('Kargo Firması')
                            ->options(ShippingRate::PROVIDER_LABELS)
                            ->required(),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('min_desi')
                                ->label('Min Desi')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('max_desi')
                                ->label('Max Desi')
                                ->numeric()
                                ->required(),
                        ]),
                        Forms\Components\TextInput::make('price')
                            ->label('Fiyat')
                            ->numeric()
                            ->suffix('₺')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif'),
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('provider');
    }
}
