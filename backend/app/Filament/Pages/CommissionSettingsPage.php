<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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
        $this->form->fill([
            'commission_enabled' => Setting::getValue('commission.enabled', true),
            'fee_mode' => Setting::getValue('commission.fee_mode', 'flat'),
            'flat_service_fee' => Setting::getValue('commission.flat_service_fee', 50),
            'commission_percentage' => Setting::getValue('commission.commission_percentage', 10),
            'marketplace_fee_enabled' => Setting::getValue('commission.marketplace_fee_enabled', false),
            'marketplace_fee_rate' => Setting::getValue('commission.marketplace_fee_rate', 0.89),
            'withholding_tax_rate' => Setting::getValue('commission.withholding_tax_rate', 1.00),
            'min_order_amount' => Setting::getValue('commission.min_order_amount', 2000),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Hizmet Bedeli Ayarları')
                    ->description('Satıcılardan alınan komisyon/hizmet bedeli ayarları')
                    ->schema([
                        Forms\Components\Toggle::make('commission_enabled')
                            ->label('Hizmet Bedeli Sistemi Aktif')
                            ->default(true)
                            ->helperText('Devre dışı bırakılırsa hiç hizmet bedeli kesilmez'),

                        Forms\Components\Select::make('fee_mode')
                            ->label('Komisyon Modu')
                            ->options([
                                'flat' => 'Sabit Hizmet Bedeli (sipariş başına sabit ₺)',
                                'percentage' => 'Komisyon Oranı (satış tutarının %\'si)',
                                'category' => 'Kategori Bazlı Komisyon (her kategorinin kendi oranı)',
                            ])
                            ->default('flat')
                            ->live()
                            ->helperText('Satıcılardan alınacak komisyon hesaplama yöntemi'),

                        Forms\Components\TextInput::make('flat_service_fee')
                            ->label('Sabit Hizmet Bedeli')
                            ->numeric()
                            ->suffix('₺')
                            ->step(1)
                            ->minValue(0)
                            ->maxValue(500)
                            ->default(50)
                            ->helperText('Sipariş başına her satıcıdan alınan sabit hizmet bedeli')
                            ->visible(fn (Forms\Get $get): bool => ($get('fee_mode') ?? 'flat') === 'flat'),

                        Forms\Components\TextInput::make('commission_percentage')
                            ->label('Komisyon Oranı')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(10)
                            ->helperText('Satış tutarı üzerinden alınacak komisyon yüzdesi')
                            ->visible(fn (Forms\Get $get): bool => ($get('fee_mode') ?? 'flat') === 'percentage'),

                        Forms\Components\Placeholder::make('category_info')
                            ->label('Kategori Bazlı Komisyon')
                            ->content('Her kategorinin kendi komisyon oranı kullanılır. Kategori komisyon oranlarını Kategori yönetimi sayfasından düzenleyebilirsiniz.')
                            ->visible(fn (Forms\Get $get): bool => ($get('fee_mode') ?? 'flat') === 'category'),

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
                            ->label('Stopaj Oranı')
                            ->numeric()
                            ->suffix('%')
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(10)
                            ->default(1.00)
                            ->helperText('Vergi stopajı kesintisi'),

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
                    ->description('₺2.500\'lik bir satış için kesinti hesabı')
                    ->schema([
                        Forms\Components\Placeholder::make('example')
                            ->label('')
                            ->content(function (Forms\Get $get) {
                                $feeMode = $get('fee_mode') ?? 'flat';
                                $flatFee = (float) ($get('flat_service_fee') ?? 50);
                                $commissionPercentage = (float) ($get('commission_percentage') ?? 10);
                                $marketplaceEnabled = (bool) $get('marketplace_fee_enabled');
                                $marketplaceRate = (float) ($get('marketplace_fee_rate') ?? 0.89);
                                $withholdingRate = (float) ($get('withholding_tax_rate') ?? 1);

                                $saleAmount = 2500;

                                if ($feeMode === 'flat') {
                                    $feeAmount = $flatFee;
                                    $feeLabel = "Hizmet Bedeli (sabit)";
                                } elseif ($feeMode === 'percentage') {
                                    $feeAmount = $saleAmount * ($commissionPercentage / 100);
                                    $feeLabel = "Komisyon (%{$commissionPercentage})";
                                } else {
                                    $feeAmount = $saleAmount * 0.08;
                                    $feeLabel = "Kategori Komisyonu (örn. %8)";
                                }

                                $marketplaceFee = $marketplaceEnabled ? $saleAmount * ($marketplaceRate / 100) : 0;
                                $withholding = $saleAmount * ($withholdingRate / 100);
                                $total = $feeAmount + $marketplaceFee + $withholding;
                                $net = $saleAmount - $total;

                                $lines = "Satış Tutarı: ₺" . number_format($saleAmount, 2) . "\n"
                                    . "{$feeLabel}: -₺" . number_format($feeAmount, 2) . "\n";

                                if ($marketplaceEnabled) {
                                    $lines .= "Pazaryeri Hizmet Bedeli (%{$marketplaceRate}): -₺" . number_format($marketplaceFee, 2) . "\n";
                                }

                                $lines .= "Stopaj (%{$withholdingRate}): -₺" . number_format($withholding, 2) . "\n"
                                    . "Toplam Kesinti: -₺" . number_format($total, 2) . "\n"
                                    . "Net Satıcı Tutarı: ₺" . number_format($net, 2);

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
        Setting::setValue('commission.fee_mode', $data['fee_mode'] ?? 'flat');
        Setting::setValue('commission.flat_service_fee', $data['flat_service_fee'] ?? 50);
        Setting::setValue('commission.commission_percentage', $data['commission_percentage'] ?? 10);
        Setting::setValue('commission.marketplace_fee_enabled', $data['marketplace_fee_enabled'] ?? false);
        Setting::setValue('commission.marketplace_fee_rate', $data['marketplace_fee_rate'] ?? 0.89);
        Setting::setValue('commission.withholding_tax_rate', $data['withholding_tax_rate'] ?? 1.00);
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
