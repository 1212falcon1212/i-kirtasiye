<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class FooterSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?string $title = 'Footer Ayarları';

    protected static ?string $navigationLabel = 'Footer Ayarları';

    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.footer-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'description' => Setting::getValue('footer.description', "Türkiye'nin en güvenilir B2B tedarik platformu. Güvenli ve hızlı ürün tedarikiniz için tek adres."),
            'phone' => Setting::getValue('footer.phone', '0850 123 45 67'),
            'phone_raw' => Setting::getValue('footer.phone_raw', '08501234567'),
            'email' => Setting::getValue('footer.email', 'info@i-kirtasiye.com'),
            'address' => Setting::getValue('footer.address', 'İstanbul, Türkiye'),
            'hours_weekday' => Setting::getValue('footer.hours_weekday', '09:00 - 18:00'),
            'hours_saturday' => Setting::getValue('footer.hours_saturday', '10:00 - 14:00'),
            'hours_sunday' => Setting::getValue('footer.hours_sunday', 'Kapalı'),
            'copyright' => Setting::getValue('footer.copyright', 'i-kirtasiye.com. Tüm hakları saklıdır.'),
            'pharmacist_note' => Setting::getValue('footer.pharmacist_note', 'Sadece kayıtlı kırtasiyeciler içindir'),
            'facebook_url' => Setting::getValue('footer.facebook_url', ''),
            'twitter_url' => Setting::getValue('footer.twitter_url', ''),
            'instagram_url' => Setting::getValue('footer.instagram_url', ''),
            'linkedin_url' => Setting::getValue('footer.linkedin_url', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Marka & İletişim')
                    ->description('Footer üst kısmındaki marka açıklaması ve iletişim bilgileri')
                    ->schema([
                        Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->placeholder("Turkiye'nin en guvenilir B2B tedarik platformu..."),
                        TextInput::make('phone')
                            ->label('Telefon (Görünen)')
                            ->placeholder('0850 123 45 67'),
                        TextInput::make('phone_raw')
                            ->label('Telefon (Link)')
                            ->helperText('tel: linki için kullanılır (boşluksuz)')
                            ->placeholder('08501234567'),
                        TextInput::make('email')
                            ->label('E-posta')
                            ->email()
                            ->placeholder('info@i-kirtasiye.com'),
                        TextInput::make('address')
                            ->label('Adres')
                            ->placeholder('İstanbul, Türkiye')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Çalışma Saatleri')
                    ->description('İletişim sayfasında gösterilir')
                    ->schema([
                        TextInput::make('hours_weekday')
                            ->label('Hafta İçi (Pzt-Cum)')
                            ->placeholder('09:00 - 18:00'),
                        TextInput::make('hours_saturday')
                            ->label('Cumartesi')
                            ->placeholder('10:00 - 14:00'),
                        TextInput::make('hours_sunday')
                            ->label('Pazar')
                            ->placeholder('Kapalı'),
                    ])
                    ->columns(3),

                Section::make('Alt Bar')
                    ->description('Footer en alt kısmındaki telif hakkı ve not')
                    ->schema([
                        TextInput::make('copyright')
                            ->label('Telif Hakkı')
                            ->placeholder('i-kirtasiye.com. Tum haklari saklidir.'),
                        TextInput::make('pharmacist_note')
                            ->label('Footer Üye Notu')
                            ->placeholder('Sadece kayitli kirtasiyeciler icindir'),
                    ])
                    ->columns(2),

                Section::make('Sosyal Medya')
                    ->description('Boş bırakılan alanlar footer\'da gösterilmez')
                    ->schema([
                        TextInput::make('facebook_url')
                            ->label('Facebook URL')
                            ->url()
                            ->placeholder('https://facebook.com/...'),
                        TextInput::make('twitter_url')
                            ->label('Twitter / X URL')
                            ->url()
                            ->placeholder('https://twitter.com/...'),
                        TextInput::make('instagram_url')
                            ->label('Instagram URL')
                            ->url()
                            ->placeholder('https://instagram.com/...'),
                        TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->url()
                            ->placeholder('https://linkedin.com/...'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        Setting::setValue('footer.description', $data['description'], 'footer', 'string');
        Setting::setValue('footer.phone', $data['phone'], 'footer', 'string');
        Setting::setValue('footer.phone_raw', $data['phone_raw'], 'footer', 'string');
        Setting::setValue('footer.email', $data['email'], 'footer', 'string');
        Setting::setValue('footer.address', $data['address'] ?? '', 'footer', 'string');
        Setting::setValue('footer.hours_weekday', $data['hours_weekday'] ?? '', 'footer', 'string');
        Setting::setValue('footer.hours_saturday', $data['hours_saturday'] ?? '', 'footer', 'string');
        Setting::setValue('footer.hours_sunday', $data['hours_sunday'] ?? '', 'footer', 'string');
        Setting::setValue('footer.copyright', $data['copyright'], 'footer', 'string');
        Setting::setValue('footer.pharmacist_note', $data['pharmacist_note'], 'footer', 'string');
        Setting::setValue('footer.facebook_url', $data['facebook_url'] ?? '', 'footer', 'string');
        Setting::setValue('footer.twitter_url', $data['twitter_url'] ?? '', 'footer', 'string');
        Setting::setValue('footer.instagram_url', $data['instagram_url'] ?? '', 'footer', 'string');
        Setting::setValue('footer.linkedin_url', $data['linkedin_url'] ?? '', 'footer', 'string');

        Cache::forget('cms.layout');

        Notification::make()
            ->title('Footer ayarları kaydedildi.')
            ->success()
            ->send();
    }
}
