<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.payment-settings';

    protected static ?string $navigationLabel = 'Ödeme Ayarları';

    protected static ?string $title = 'Ödeme Sistemi Ayarları';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'active_gateway' => Setting::getValue('payment.active_gateway', 'none'),
            'test_mode' => Setting::getValue('payment.test_mode', true),
            // Payout schedule
            'payout_day' => Setting::getValue('payment.payout_day', 15),
            'payout_min_amount' => Setting::getValue('payment.payout_min_amount', 100),
            'withholding_rate' => Setting::getValue('payment.withholding_rate', 1),
            'payout_hold_days' => Setting::getValue('payment.payout_hold_days', 35),
            // Iyzico
            'iyzico_api_key' => Setting::getValue('payment.iyzico_api_key', ''),
            'iyzico_secret_key' => Setting::getValue('payment.iyzico_secret_key', ''),
            'iyzico_base_url' => Setting::getValue('payment.iyzico_base_url', 'https://sandbox-api.iyzipay.com'),
            // PayTR
            'paytr_merchant_id' => Setting::getValue('payment.paytr_merchant_id', ''),
            'paytr_merchant_key' => Setting::getValue('payment.paytr_merchant_key', ''),
            'paytr_merchant_salt' => Setting::getValue('payment.paytr_merchant_salt', ''),
            // ERP - BizimHesap
            'erp_enabled' => Setting::getValue('erp.enabled', false),
            'erp_provider' => Setting::getValue('erp.provider', 'bizimhesap'),
            'bizimhesap_api_token' => Setting::getValue('erp.bizimhesap_api_token', ''),
            'erp_auto_sync' => Setting::getValue('erp.auto_sync', false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Genel Ayarlar')
                    ->description('Aktif ödeme geçidi ve test modu ayarları')
                    ->schema([
                        Forms\Components\Select::make('active_gateway')
                            ->label('Aktif Ödeme Geçidi')
                            ->options([
                                'none' => 'Ödeme Kapalı',
                                'iyzico' => 'Iyzico',
                                'paytr' => 'PayTR',
                            ])
                            ->default('none')
                            ->required()
                            ->live()
                            ->helperText('Kullanılacak ödeme sistemini seçin'),

                        Forms\Components\Toggle::make('test_mode')
                            ->label('Test Modu')
                            ->default(true)
                            ->helperText('Test modunda gerçek ödeme alınmaz'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ödeme Takvimi')
                    ->description('Satıcılara ödeme yapılma ayarları')
                    ->schema([
                        Forms\Components\TextInput::make('payout_day')
                            ->label('Ödeme Günü')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(28)
                            ->default(15)
                            ->suffix('. gün')
                            ->helperText('Her ayın kaçıncı gününde ödeme yapılacağı'),

                        Forms\Components\TextInput::make('payout_min_amount')
                            ->label('Minimum Ödeme Tutarı')
                            ->numeric()
                            ->default(100)
                            ->suffix('₺')
                            ->helperText('Ödeme için minimum bakiye'),

                        Forms\Components\TextInput::make('withholding_rate')
                            ->label('Stopaj Oranı')
                            ->numeric()
                            ->default(1)
                            ->suffix('%')
                            ->helperText('Satıcı ödemelerinden kesilecek stopaj oranı'),

                        Forms\Components\TextInput::make('payout_hold_days')
                            ->label('Hakediş Bekleme Süresi')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(365)
                            ->default(35)
                            ->suffix('gün')
                            ->helperText('Alıcı teslimat onayından kaç gün sonra satıcı bakiyesi serbest bırakılacak'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Iyzico Ayarları')
                    ->description('Iyzico API bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('iyzico_api_key')
                            ->label('API Key')
                            ->placeholder('sandbox-xxxxxxxxxxxx')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('iyzico_secret_key')
                            ->label('Secret Key')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\Select::make('iyzico_base_url')
                            ->label('API URL')
                            ->options([
                                'https://sandbox-api.iyzipay.com' => 'Sandbox (Test)',
                                'https://api.iyzipay.com' => 'Production (Canlı)',
                            ])
                            ->default('https://sandbox-api.iyzipay.com'),
                    ])
                    ->columns(3)
                    ->visible(fn(Forms\Get $get): bool => $get('active_gateway') === 'iyzico'),

                Forms\Components\Section::make('PayTR Ayarları')
                    ->description('PayTR API bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('paytr_merchant_id')
                            ->label('Merchant ID')
                            ->placeholder('123456')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('paytr_merchant_key')
                            ->label('Merchant Key')
                            ->password()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('paytr_merchant_salt')
                            ->label('Merchant Salt')
                            ->password()
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->visible(fn(Forms\Get $get): bool => $get('active_gateway') === 'paytr'),

                Forms\Components\Section::make('ERP Entegrasyonu')
                    ->description('Muhasebe/ERP sistemi entegrasyon ayarlari')
                    ->schema([
                        Forms\Components\Toggle::make('erp_enabled')
                            ->label('ERP Entegrasyonu Aktif')
                            ->default(false)
                            ->live()
                            ->helperText('Odeme ve fatura bilgilerini ERP sistemine otomatik gonder'),

                        Forms\Components\Select::make('erp_provider')
                            ->label('ERP Sistemi')
                            ->options([
                                'bizimhesap' => 'BizimHesap',
                                'parasut' => 'Parasut',
                                'entegra' => 'Entegra',
                            ])
                            ->default('bizimhesap')
                            ->visible(fn(Forms\Get $get): bool => (bool) $get('erp_enabled')),

                        Forms\Components\TextInput::make('bizimhesap_api_token')
                            ->label('BizimHesap API Key (FirmID)')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->placeholder('DBE92ABA40C24D4CA3F68F46ADE455C1')
                            ->helperText('BizimHesap panelinden alinan API Key / FirmID tokeni')
                            ->visible(fn(Forms\Get $get): bool => (bool) $get('erp_enabled') && $get('erp_provider') === 'bizimhesap'),

                        Forms\Components\Toggle::make('erp_auto_sync')
                            ->label('Otomatik Senkronizasyon')
                            ->default(false)
                            ->helperText('Odeme tamamlandiginda otomatik olarak ERP sistemine gonder')
                            ->visible(fn(Forms\Get $get): bool => (bool) $get('erp_enabled')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Save general settings
        Setting::setValue('payment.active_gateway', $data['active_gateway'], 'payment', 'string');
        Setting::setValue('payment.test_mode', $data['test_mode'], 'payment', 'boolean');

        // Save payout schedule
        Setting::setValue('payment.payout_day', $data['payout_day'], 'payment', 'integer');
        Setting::setValue('payment.payout_min_amount', $data['payout_min_amount'], 'payment', 'float');
        Setting::setValue('payment.withholding_rate', $data['withholding_rate'], 'payment', 'float');
        if (isset($data['payout_hold_days'])) {
            Setting::setValue('payment.payout_hold_days', $data['payout_hold_days'], 'payment', 'integer');
        }

        // Save Iyzico settings (encrypted) - only if visible
        if (isset($data['iyzico_api_key'])) {
            Setting::setValue('payment.iyzico_api_key', $data['iyzico_api_key'], 'payment', 'encrypted');
            Setting::setValue('payment.iyzico_secret_key', $data['iyzico_secret_key'], 'payment', 'encrypted');
            Setting::setValue('payment.iyzico_base_url', $data['iyzico_base_url'], 'payment', 'string');
        }

        // Save PayTR settings (encrypted) - only if visible
        if (isset($data['paytr_merchant_id'])) {
            Setting::setValue('payment.paytr_merchant_id', $data['paytr_merchant_id'], 'payment', 'encrypted');
            Setting::setValue('payment.paytr_merchant_key', $data['paytr_merchant_key'], 'payment', 'encrypted');
            Setting::setValue('payment.paytr_merchant_salt', $data['paytr_merchant_salt'], 'payment', 'encrypted');
        }

        // Save ERP settings
        Setting::setValue('erp.enabled', $data['erp_enabled'] ?? false, 'erp', 'boolean');
        Setting::setValue('erp.provider', $data['erp_provider'] ?? 'bizimhesap', 'erp', 'string');
        Setting::setValue('erp.auto_sync', $data['erp_auto_sync'] ?? false, 'erp', 'boolean');

        if (isset($data['bizimhesap_api_token']) && !empty($data['bizimhesap_api_token'])) {
            Setting::setValue('erp.bizimhesap_api_token', $data['bizimhesap_api_token'], 'erp', 'encrypted');
        }

        Notification::make()
            ->title('Ödeme ayarları kaydedildi')
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
