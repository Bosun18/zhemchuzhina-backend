<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageNotificationSettings extends Page
{
    protected string $view = 'filament.pages.manage-notification-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Уведомления';

    protected static ?string $title = 'Настройки уведомлений';

    protected static \UnitEnum|string|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'notification_emails_registration' => Setting::get('notification_emails_registration', []),
            'notification_emails_booking' => Setting::get('notification_emails_booking', []),
            'notification_emails_review' => Setting::get('notification_emails_review', []),
            'notification_emails_critical' => Setting::get('notification_emails_critical', []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Email-адреса для уведомлений')
                        ->description('Укажите адреса, на которые будут приходить уведомления. Можно добавить несколько.')
                        ->schema([
                            TagsInput::make('notification_emails_registration')
                                ->label('Регистрация нового пользователя')
                                ->placeholder('Добавить email...')
                                ->nestedRecursiveRules(['email'])
                                ->columnSpanFull(),
                            TagsInput::make('notification_emails_booking')
                                ->label('Новое бронирование')
                                ->placeholder('Добавить email...')
                                ->nestedRecursiveRules(['email'])
                                ->columnSpanFull(),
                            TagsInput::make('notification_emails_review')
                                ->label('Новый отзыв')
                                ->placeholder('Добавить email...')
                                ->nestedRecursiveRules(['email'])
                                ->columnSpanFull(),
                        ]),
                    Section::make('Критические уведомления')
                        ->description('Адреса для оповещения о сбоях системы и критических ошибках. Как правило, почта разработчика.')
                        ->schema([
                            TagsInput::make('notification_emails_critical')
                                ->label('Системные ошибки и сбои')
                                ->placeholder('Добавить email...')
                                ->nestedRecursiveRules(['email'])
                                ->columnSpanFull(),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Сохранить')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('notification_emails_registration', $data['notification_emails_registration'] ?? []);
        Setting::set('notification_emails_booking', $data['notification_emails_booking'] ?? []);
        Setting::set('notification_emails_review', $data['notification_emails_review'] ?? []);
        Setting::set('notification_emails_critical', $data['notification_emails_critical'] ?? []);

        Notification::make()
            ->success()
            ->title('Настройки сохранены')
            ->send();
    }
}
