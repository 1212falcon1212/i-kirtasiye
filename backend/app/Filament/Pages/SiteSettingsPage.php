<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Cache;

class SiteSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?string $title = 'Site Ayarları';
    protected static ?string $navigationLabel = 'Site Ayarları';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'navbar_color' => Setting::getValue('navbar_color', '#065f46'),
            'show_top_bar' => Setting::getValue('show_top_bar', false),
            'top_bar_phone' => Setting::getValue('top_bar_phone', '0850 XXX XX XX'),
            'top_bar_hours' => Setting::getValue('top_bar_hours', 'Hafta içi 09:00 - 18:00'),
            'top_bar_shipping' => Setting::getValue('top_bar_shipping', 'Türkiye geneli ücretsiz kargo'),
            'whatsapp_phone' => Setting::getValue('whatsapp_phone', ''),
            'whatsapp_message' => Setting::getValue('whatsapp_message', 'Merhaba, bilgi almak istiyorum.'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Menü Rengi')
                    ->description('Kategori navigasyon barının arka plan rengi')
                    ->schema([
                        ColorPicker::make('navbar_color')
                            ->label('Navbar Arka Plan Rengi')
                            ->default('#065f46'),
                    ])
                    ->columns(1),

                Section::make('Üst Bar (Top Bar)')
                    ->description('Site başlığının üstündeki bilgi barı ayarları')
                    ->schema([
                        Toggle::make('show_top_bar')
                            ->label('Üst Barı Göster')
                            ->helperText('Kapatırsanız telefon, çalışma saatleri ve kargo bilgisi gizlenir')
                            ->default(false),
                        TextInput::make('top_bar_phone')
                            ->label('Telefon Numarası')
                            ->placeholder('0850 XXX XX XX'),
                        TextInput::make('top_bar_hours')
                            ->label('Çalışma Saatleri')
                            ->placeholder('Hafta içi 09:00 - 18:00'),
                        TextInput::make('top_bar_shipping')
                            ->label('Kargo Bilgisi')
                            ->placeholder('Türkiye geneli ücretsiz kargo'),
                    ])
                    ->columns(1),

                Section::make('WhatsApp İletişim')
                    ->description('Siteye WhatsApp iletişim balonu ekler. Numara girilirse balon aktif olur.')
                    ->schema([
                        TextInput::make('whatsapp_phone')
                            ->label('WhatsApp Numarası')
                            ->helperText('Ülke kodu ile birlikte girin (örn: 905428482646). Boş bırakırsanız balon gizlenir.')
                            ->placeholder('905XXXXXXXXX'),
                        TextInput::make('whatsapp_message')
                            ->label('Varsayılan Mesaj')
                            ->helperText('Kullanıcı WhatsApp\'a tıkladığında otomatik yazılacak mesaj')
                            ->placeholder('Merhaba, bilgi almak istiyorum.'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        Setting::setValue('navbar_color', $data['navbar_color'], 'site', 'string');
        Setting::setValue('show_top_bar', $data['show_top_bar'], 'site', 'boolean');
        Setting::setValue('top_bar_phone', $data['top_bar_phone'], 'site', 'string');
        Setting::setValue('top_bar_hours', $data['top_bar_hours'], 'site', 'string');
        Setting::setValue('top_bar_shipping', $data['top_bar_shipping'], 'site', 'string');
        Setting::setValue('whatsapp_phone', $data['whatsapp_phone'] ?? '', 'site', 'string');
        Setting::setValue('whatsapp_message', $data['whatsapp_message'] ?? '', 'site', 'string');

        // Clear CMS layout cache so changes appear immediately
        Cache::forget('cms.layout');

        Notification::make()
            ->title('Site ayarları kaydedildi.')
            ->success()
            ->send();
    }
}
