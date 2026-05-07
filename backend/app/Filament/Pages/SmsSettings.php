<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SmsSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $title = 'SMS Ayarları';

    protected static string $view = 'filament.pages.sms-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'netgsm_user' => SystemSetting::where('key', 'netgsm_user')->value('value'),
            'netgsm_password' => SystemSetting::where('key', 'netgsm_password')->value('value'),
            'netgsm_header' => SystemSetting::where('key', 'netgsm_header')->value('value'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        TextInput::make('netgsm_user')
                            ->label('Netgsm Kullanıcı Adı (850...)')
                            ->required(),
                        TextInput::make('netgsm_password')
                            ->label('Netgsm Şifre')
                            ->password()
                            ->required(),
                        TextInput::make('netgsm_header')
                            ->label('SMS Başlığı (Netgsm Panelinde Tanımlı)')
                            ->required()
                            ->placeholder('KIRTASIYE...'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Notification::make()
            ->title('SMS Ayarları kaydedildi.')
            ->success()
            ->send();
    }
}
