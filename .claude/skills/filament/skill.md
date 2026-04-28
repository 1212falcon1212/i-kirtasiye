---
name: filament
description: Filament admin panel for settings pages, resources, tables, and forms
user-invocable: true
---

# Filament Module

> Admin panel development with Filament PHP

**Activates on:** admin, filament, panel, settings page, resource, admin panel

**Collaborates with:** `laravel-api` for backend logic

---

## Project Structure

```
backend/app/Filament/
├── Pages/
│   ├── SeoTextSettingsPage.php      # Custom settings page
│   ├── FooterSettingsPage.php       # Footer settings
│   └── PaymentSettingsPage.php      # Payment config
├── Resources/
│   ├── OrderResource.php            # Order CRUD + table
│   ├── UserResource.php             # User management
│   ├── BannerResource.php           # Banner management
│   └── ProductResource.php          # Product management
└── Widgets/                         # Dashboard widgets

backend/resources/views/filament/pages/
├── seo-text-settings.blade.php
├── footer-settings.blade.php
└── payment-settings.blade.php
```

---

## Settings Page Pattern

This project uses `Setting::getValue/setValue` for key-value settings, NOT Filament's built-in `Settings` plugin.

```php
<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class MySettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?string $title = 'Ayarlar';
    protected static string $view = 'filament.pages.my-settings';

    public ?array $data = [];

    // Default values as constants (single source of truth)
    public const DEFAULT_TITLE = 'Varsayilan Baslik';

    public function mount(): void
    {
        $this->form->fill([
            'title' => Setting::getValue('group.title', self::DEFAULT_TITLE),
            'content' => Setting::getValue('group.content', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Baslik')->schema([
                    TextInput::make('title')
                        ->label('Baslik')
                        ->required(),
                    RichEditor::make('content')
                        ->label('Icerik')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline',
                            'h2', 'h3',
                            'bulletList', 'orderedList',
                            'link', 'blockquote',
                        ]),
                ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        Setting::setValue('group.title', $data['title'] ?? '', 'group', 'string');
        Setting::setValue('group.content', $data['content'] ?? '', 'group', 'text');

        Cache::forget('cms.related.cache.key');

        Notification::make()
            ->title('Ayarlar kaydedildi')
            ->success()
            ->send();
    }
}
```

### Blade View

```blade
{{-- resources/views/filament/pages/my-settings.blade.php --}}
<x-filament-panels::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
```

---

## Resource Pattern (Table + Form)

```php
<?php

namespace App\Filament\Resources;

use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Siparisler';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Siparis No')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.business_name')
                    ->label('Eczane'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Toplam')
                    ->money('TRY'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'confirmed',
                        'info' => 'processing',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Order::STATUS_LABELS),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
```

---

## Common Form Components

```php
// TextInput
TextInput::make('name')->label('Ad')->required()->maxLength(255);

// Number
TextInput::make('price')->label('Fiyat')->numeric()->prefix('₺')->step(0.01);

// Select
Select::make('status')->label('Durum')->options(Order::STATUS_LABELS)->required();

// Toggle
Toggle::make('is_active')->label('Aktif');

// RichEditor
RichEditor::make('content')->label('Icerik');

// FileUpload
FileUpload::make('image')->label('Gorsel')->image()->directory('uploads');

// Repeater
Repeater::make('items')->schema([
    TextInput::make('title'),
    TextInput::make('url'),
]);

// KeyValue
KeyValue::make('metadata')->label('Ek Bilgiler');
```

---

## Cache Invalidation Pattern

```php
// After saving settings
Cache::forget('cms.homepage.seo_text');
Cache::forget('cms.homepage.sections');

// In observers
private function clearHomepageCache(): void
{
    Cache::forget('cms.homepage.sections');
    Cache::forget('cms.featured_sections');
}
```

---

## Production Gotcha's

```bash
# Filament route/view cache sorunlari icin:
php artisan filament:optimize-clear
php artisan optimize:clear

# Yeni page/resource ekledikten sonra production'da:
php artisan filament:optimize-clear
```

---

## Checklist

```
[ ] Page/Resource sinifi olusturuldu
[ ] Blade view olusturuldu (settings page icin)
[ ] NavigationGroup ve icon atandi
[ ] Form validation eklendi
[ ] Cache invalidation eklendi
[ ] Production'da filament:optimize-clear calistirildi
```
