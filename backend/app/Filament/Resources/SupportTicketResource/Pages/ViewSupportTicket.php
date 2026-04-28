<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Yanıtla')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->visible(fn() => !in_array($this->record->status, ['closed', 'resolved']))
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('Mesaj')
                        ->required()
                        ->rows(4)
                        ->placeholder('Kullanıcıya yanıtınızı yazın...'),
                ])
                ->action(function (array $data) {
                    SupportTicketMessage::create([
                        'ticket_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'message' => $data['message'],
                        'is_staff_reply' => true,
                    ]);

                    if ($this->record->status === 'open') {
                        $this->record->update([
                            'status' => 'waiting',
                            'assigned_to' => $this->record->assigned_to ?? auth()->id(),
                        ]);
                    }

                    Notification::make()
                        ->title('Yanıt gönderildi')
                        ->success()
                        ->send();

                    $this->refreshFormData(['messages']);
                }),

            Actions\Action::make('changeStatus')
                ->label('Durum Güncelle')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Yeni Durum')
                        ->options(SupportTicket::STATUS_LABELS)
                        ->default(fn() => $this->record->status)
                        ->required(),
                    Forms\Components\Textarea::make('admin_note')
                        ->label('Admin Notu (Opsiyonel)')
                        ->rows(2)
                        ->default(fn() => $this->record->admin_note),
                ])
                ->action(function (array $data) {
                    $updates = [
                        'status' => $data['status'],
                        'admin_note' => $data['admin_note'],
                        'assigned_to' => $this->record->assigned_to ?? auth()->id(),
                    ];
                    if ($data['status'] === 'resolved') {
                        $updates['resolved_at'] = now();
                    } elseif ($data['status'] === 'closed') {
                        $updates['closed_at'] = now();
                    }
                    $this->record->update($updates);

                    Notification::make()
                        ->title('Durum güncellendi')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'admin_note']);
                }),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            SupportTicketResource\Widgets\TicketMessagesWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
