<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Hizmet Bedeli Ayarları sayfası.
 *
 * Komisyon ve sabit hizmet bedeli oranlarını, stopaj kesintisini ve
 * fallback KDV oranını yöneticinin değiştirmesini sağlar. Kayıtlı
 * değerler `Setting` tablosunda `commission.*` anahtarları altında tutulur.
 */
class CommissionSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static string $view = 'filament.pages.commission-settings';

    protected static ?string $navigationLabel = 'Hizmet Bedeli Ayarları';

    protected static ?string $title = 'Hizmet Bedeli Ayarları';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public function mount(): void
    {
        // config('commission.default_vat_rate') decimal (0-1) tutar; UI yüzde gösterir.
        $defaultVatPercent = (float) config('commission.default_vat_rate', 0.20) * 100;

        $this->form->fill([
            'commission_enabled' => Setting::getValue('commission.enabled', true),
            'fee_mode' => Setting::getValue('commission.fee_mode', 'combined'),
            'flat_service_fee' => Setting::getValue('commission.flat_service_fee', 50),
            'commission_percentage' => Setting::getValue('commission.commission_percentage', 10),
            'marketplace_fee_enabled' => Setting::getValue('commission.marketplace_fee_enabled', false),
            'marketplace_fee_rate' => Setting::getValue('commission.marketplace_fee_rate', 0.89),
            'withholding_tax_rate' => Setting::getValue('commission.withholding_tax_rate', 1.00),
            'default_vat_rate' => Setting::getValue('commission.default_vat_rate', $defaultVatPercent),
            'min_order_amount' => Setting::getValue('commission.min_order_amount', 2000),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Hizmet Bedeli Ayarları')
                    ->description('Satıcılardan alınan komisyon, sabit hizmet bedeli ve stopaj ayarları')
                    ->schema([
                        Forms\Components\Toggle::make('commission_enabled')
                            ->label('Hizmet Bedeli Sistemi Aktif')
                            ->default(true)
                            ->helperText('Pasif bırakılırsa komisyon ve sabit hizmet bedeli kesilmez. Stopaj her durumda uygulanır.'),

                        Forms\Components\Select::make('fee_mode')
                            ->label('Hesaplama Modu')
                            ->options([
                                'combined' => 'Komisyon + Sabit Hizmet Bedeli (önerilen)',
                                'flat' => 'Sadece Sabit Hizmet Bedeli',
                                'percentage' => 'Sadece Yüzdelik Komisyon',
                                'category' => 'Kategori Bazlı Komisyon',
                            ])
                            ->default('combined')
                            ->live()
                            ->helperText('Her siparişte hangi kesintilerin uygulanacağını belirler. "Komisyon + Sabit Hizmet Bedeli" modunda her ikisi de alınır.'),

                        Forms\Components\TextInput::make('commission_percentage')
                            ->label('Komisyon Oranı (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(10)
                            ->helperText('Her siparişte uygulanacak komisyon yüzdesi. Varsayılan %10. KDV dahil satış tutarı üzerinden hesaplanır.')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('fee_mode') ?? 'combined', ['percentage', 'combined'], true)),

                        Forms\Components\TextInput::make('flat_service_fee')
                            ->label('Sabit Hizmet Bedeli (₺)')
                            ->numeric()
                            ->suffix('₺')
                            ->step(1)
                            ->minValue(0)
                            ->maxValue(500)
                            ->default(50)
                            ->helperText('Her siparişte alınan sabit hizmet bedeli. Varsayılan ₺50. Çoklu satıcılı siparişlerde her satıcı bağımsız ₺50 alır ve satıcıdaki kalem sayısına bölünerek dağıtılır.')
                            ->visible(fn (Forms\Get $get): bool => in_array($get('fee_mode') ?? 'combined', ['flat', 'combined'], true)),

                        Forms\Components\Placeholder::make('category_info')
                            ->label('Kategori Bazlı Komisyon')
                            ->content('Her kategorinin kendi komisyon oranı kullanılır. Kategori komisyon oranlarını Kategori yönetimi sayfasından düzenleyebilirsiniz.')
                            ->visible(fn (Forms\Get $get): bool => ($get('fee_mode') ?? 'combined') === 'category'),

                        Forms\Components\Toggle::make('marketplace_fee_enabled')
                            ->label('Pazaryeri Hizmet Bedeli Aktif')
                            ->default(false)
                            ->live()
                            ->helperText('Satış tutarı üzerinden ek pazaryeri hizmet bedeli kesilsin mi'),

                        Forms\Components\TextInput::make('marketplace_fee_rate')
                            ->label('Pazaryeri Hizmet Bedeli Oranı')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(10)
                            ->default(0.89)
                            ->helperText('Satış tutarı üzerinden alınan ek hizmet bedeli yüzdesi')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('marketplace_fee_enabled')),

                        Forms\Components\TextInput::make('withholding_tax_rate')
                            ->label('Stopaj Oranı (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(10)
                            ->default(1.00)
                            ->helperText('Ürünün KDV\'siz fiyatı üzerinden hesaplanır. Varsayılan %1.'),

                        Forms\Components\TextInput::make('default_vat_rate')
                            ->label('Varsayılan KDV Oranı (%)')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(20)
                            ->helperText('Ürünün veya kategorisinin KDV oranı tanımlı değilse stopaj hesabında bu oran kullanılır. Türkiye standart KDV oranı %20.'),

                        Forms\Components\TextInput::make('min_order_amount')
                            ->label('Minimum Sipariş Tutarı')
                            ->numeric()
                            ->suffix('₺')
                            ->step(100)
                            ->minValue(0)
                            ->maxValue(50000)
                            ->default(2000)
                            ->helperText('Sipariş oluşturulabilecek minimum tutar'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Hesaplama Örneği')
                    ->description('₺2.500\'lik bir satış için kesinti hesabı (KDV dahil tutar)')
                    ->schema([
                        Forms\Components\Placeholder::make('example')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $feeMode = $get('fee_mode') ?? 'combined';
                                $flatFee = (float) ($get('flat_service_fee') ?? 50);
                                $commissionPercentage = (float) ($get('commission_percentage') ?? 10);
                                $marketplaceEnabled = (bool) $get('marketplace_fee_enabled');
                                $marketplaceRate = (float) ($get('marketplace_fee_rate') ?? 0.89);
                                $withholdingRate = (float) ($get('withholding_tax_rate') ?? 1);
                                $vatRate = (float) ($get('default_vat_rate') ?? 20);

                                $saleAmount = 2500.0;
                                $netPrice = $saleAmount / (1 + $vatRate / 100);

                                $commissionAmount = 0.0;
                                $flatFeeAmount = 0.0;
                                $feeLabels = [];

                                switch ($feeMode) {
                                    case 'flat':
                                        $flatFeeAmount = $flatFee;
                                        $feeLabels[] = 'Sabit Hizmet Bedeli';
                                        break;
                                    case 'percentage':
                                        $commissionAmount = $saleAmount * ($commissionPercentage / 100);
                                        $feeLabels[] = "Komisyon (%{$commissionPercentage})";
                                        break;
                                    case 'category':
                                        $commissionAmount = $saleAmount * 0.08;
                                        $feeLabels[] = 'Kategori Komisyonu (örn. %8)';
                                        break;
                                    case 'combined':
                                    default:
                                        $commissionAmount = $saleAmount * ($commissionPercentage / 100);
                                        $flatFeeAmount = $flatFee;
                                        $feeLabels[] = "Komisyon (%{$commissionPercentage})";
                                        $feeLabels[] = 'Sabit Hizmet Bedeli';
                                        break;
                                }

                                $marketplaceFee = $marketplaceEnabled ? $saleAmount * ($marketplaceRate / 100) : 0;
                                // Stopaj KDV hariç tutar üzerinden
                                $withholding = $netPrice * ($withholdingRate / 100);
                                $totalDeduction = $commissionAmount + $flatFeeAmount + $marketplaceFee + $withholding;
                                $net = $saleAmount - $totalDeduction;

                                $lines = 'Satış Tutarı (KDV dahil): ₺'.number_format($saleAmount, 2).PHP_EOL
                                    ."KDV'siz Tutar (%{$vatRate} KDV): ₺".number_format($netPrice, 2).PHP_EOL;

                                if ($commissionAmount > 0) {
                                    $lines .= "Komisyon (%{$commissionPercentage}): -₺".number_format($commissionAmount, 2).PHP_EOL;
                                }

                                if ($flatFeeAmount > 0) {
                                    $lines .= 'Sabit Hizmet Bedeli: -₺'.number_format($flatFeeAmount, 2).PHP_EOL;
                                }

                                if ($marketplaceEnabled) {
                                    $lines .= "Pazaryeri Hizmet Bedeli (%{$marketplaceRate}): -₺".number_format($marketplaceFee, 2).PHP_EOL;
                                }

                                $lines .= "Stopaj (%{$withholdingRate}, KDV hariç tutardan): -₺".number_format($withholding, 2).PHP_EOL
                                    .'Toplam Kesinti: -₺'.number_format($totalDeduction, 2).PHP_EOL
                                    .'Net Satıcı Tutarı: ₺'.number_format($net, 2);

                                return $lines;
                            }),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setValue('commission.enabled', $data['commission_enabled'] ?? true);
        Setting::setValue('commission.fee_mode', $data['fee_mode'] ?? 'combined');
        Setting::setValue('commission.flat_service_fee', $data['flat_service_fee'] ?? 50);
        Setting::setValue('commission.commission_percentage', $data['commission_percentage'] ?? 10);
        Setting::setValue('commission.marketplace_fee_enabled', $data['marketplace_fee_enabled'] ?? false);
        Setting::setValue('commission.marketplace_fee_rate', $data['marketplace_fee_rate'] ?? 0.89);
        Setting::setValue('commission.withholding_tax_rate', $data['withholding_tax_rate'] ?? 1.00);
        Setting::setValue('commission.default_vat_rate', $data['default_vat_rate'] ?? 20);
        Setting::setValue('commission.min_order_amount', $data['min_order_amount'] ?? 2000);

        Setting::clearCache();

        Notification::make()
            ->title('Hizmet bedeli ayarları kaydedildi')
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
